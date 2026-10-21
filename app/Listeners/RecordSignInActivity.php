<?php

namespace App\Listeners;

use App\Models\User;
use App\Models\UserSignIn;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Str;

/**
 * Keeps the sign-in history shown on the System Usage page.
 */
class RecordSignInActivity
{
    public function handleLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->record('login', $event->user, $event->user->email);
            User::query()->whereKey($event->user->getKey())->update(['last_active_at' => now()]);
        }
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->record('logout', $event->user, $event->user->email);
        }
    }

    public function handleFailed(Failed $event): void
    {
        $email = isset($event->credentials['email']) ? Str::limit((string) $event->credentials['email'], 250, '') : null;

        $this->record('failed', $event->user instanceof User ? $event->user : null, $email);
    }

    private function record(string $event, ?User $user, ?string $email): void
    {
        $request = request();

        UserSignIn::create([
            'user_id' => $user?->id,
            'email' => $email,
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, '') ?: null,
        ]);
    }
}
