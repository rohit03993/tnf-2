<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPublicCacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user() || ! $request->isMethod('GET') || ! $response->isSuccessful()) {
            return $response;
        }

        $maxAge = (int) config('tnf.browser_cache_max_age', 60);

        $response->headers->set(
            'Cache-Control',
            "public, max-age={$maxAge}, must-revalidate, stale-while-revalidate=30",
        );

        return $response;
    }
}
