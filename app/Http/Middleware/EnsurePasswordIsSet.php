<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * After signing in with an admin-issued reset code, the user must set a new
 * password before using anything else.
 */
class EnsurePasswordIsSet
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password && ! $request->routeIs('password.setup', 'password.setup.update', 'logout')) {
            return redirect()->route('password.setup');
        }

        return $next($request);
    }
}
