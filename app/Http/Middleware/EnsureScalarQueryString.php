<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureScalarQueryString
{
    /**
     * No page takes a list in its query string, and every search box and
     * filter reads its value as one string or number, so a crafted
     * `?search[]=` is refused here instead of reaching a query.
     */
    public function handle(Request $request, Closure $next): Response
    {
        foreach ($request->query() as $value) {
            abort_if(is_array($value), 400);
        }

        return $next($request);
    }
}
