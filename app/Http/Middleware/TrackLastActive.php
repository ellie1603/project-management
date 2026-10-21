<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records when each signed-in user was last active, for the System Usage
 * report. Written at most once every few minutes per user so it adds no
 * noticeable load.
 */
class TrackLastActive
{
    private const INTERVAL_MINUTES = 5;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ($user->last_active_at === null || $user->last_active_at->lt(now()->subMinutes(self::INTERVAL_MINUTES)))) {
            // A direct update: no model events, no audit noise, no updated_at change.
            User::query()->whereKey($user->getKey())->update(['last_active_at' => now()]);
            $user->setAttribute('last_active_at', now());
        }

        return $next($request);
    }
}
