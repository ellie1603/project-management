<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Password reset done by an administrator for someone who forgot theirs.
 * The admin gets a one-time code to pass on in person; the user signs in with
 * their email and that code, then must set a new password before anything
 * else. Only the code's hash is stored, and it expires after a day.
 */
class AdminPasswordReset
{
    public const VALID_HOURS = 24;

    /** No 0/O, 1/I/L, so the code can be read aloud or copied by hand. */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * Replaces the user's password with a new one-time code, signs them out
     * everywhere, and returns the code formatted for display (e.g. K7M2-Q9XD).
     */
    public function issue(User $user): string
    {
        $raw = '';
        for ($i = 0; $i < 8; $i++) {
            $raw .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        $user->forceFill([
            'password' => $raw,
            'must_change_password' => true,
            'password_reset_expires_at' => now()->addHours(self::VALID_HOURS),
            'remember_token' => null,
        ])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        return substr($raw, 0, 4).'-'.substr($raw, 4);
    }

    /**
     * How a typed code is compared: case and the dash don't matter.
     */
    public static function normalize(string $typed): string
    {
        return strtoupper(preg_replace('/[\s-]/', '', $typed) ?? '');
    }

    public static function isExpired(User $user): bool
    {
        return $user->must_change_password
            && $user->password_reset_expires_at !== null
            && $user->password_reset_expires_at->isPast();
    }
}
