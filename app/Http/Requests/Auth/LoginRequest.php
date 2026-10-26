<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\AdminPasswordReset;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [...$this->only('email', 'password'), 'is_active' => true];

        // Someone whose password the admin reset signs in with the one-time
        // code, which matches however it's typed (case, dash, spaces).
        $resetUser = User::query()->where('email', $this->string('email')->toString())->where('must_change_password', true)->first();
        if ($resetUser !== null) {
            $credentials['password'] = AdminPasswordReset::normalize((string) $credentials['password']);

            if (AdminPasswordReset::isExpired($resetUser) && Hash::check($credentials['password'], $resetUser->password)) {
                throw ValidationException::withMessages([
                    'email' => 'This reset code has expired. Ask your administrator to reset your password again.',
                ]);
            }
        }

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            $remaining = RateLimiter::remaining($this->throttleKey(), $this->maxAttempts());

            // The last allowed attempt locks sign-in, so say so now rather than on the next try.
            throw ValidationException::withMessages([
                'email' => $remaining > 0
                    ? trans('auth.failed').' '.trans_choice(
                        '{1} :count attempt left before sign-in is locked.|[2,*] :count attempts left before sign-in is locked.',
                        $remaining,
                    )
                    : $this->lockoutMessage(),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $this->maxAttempts())) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'email' => $this->lockoutMessage(),
        ]);
    }

    /**
     * Failed sign-ins allowed for one email + IP address before it is locked.
     */
    private function maxAttempts(): int
    {
        return config('rate_limits.login_attempts');
    }

    private function lockoutMessage(): string
    {
        $seconds = RateLimiter::availableIn($this->throttleKey());

        return trans('auth.throttle', [
            'seconds' => $seconds,
            'minutes' => ceil($seconds / 60),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
