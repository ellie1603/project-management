<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * "Set your new password" after signing in with an admin-issued reset code.
 */
class SetPasswordController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.set-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->must_change_password, 403);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
            'password_reset_expires_at' => null,
        ])->save();

        $request->session()->regenerate();

        app(AuditLogger::class)->record($request, 'password_set', 'users', $user->id, "{$user->name} set a new password after an administrator reset.");

        return redirect()->route('dashboard')->with('status', 'Your new password is set.');
    }
}
