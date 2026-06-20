<?php

namespace App\Http\Middleware;

use App\Services\PageCacheService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CachePublicPage
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! PageCacheService::enabled()
            || $request->user()
            || ! $request->isMethod('GET')
            || $request->ajax()
            || $request->expectsJson()) {
            return $next($request);
        }

        $key = PageCacheService::key($request);

        if (Cache::has($key)) {
            $cached = Cache::get($key);

            return response($cached['content'], 200, $cached['headers']);
        }

        $response = $next($request);

        if ($response->isSuccessful()
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            Cache::put($key, [
                'content' => $response->getContent(),
                'headers' => $this->storableHeaders($response),
            ], PageCacheService::ttl());
        }

        return $response;
    }

    /** @return array<string, string> */
    protected function storableHeaders(Response $response): array
    {
        $keep = ['Content-Type', 'Cache-Control'];

        return collect($response->headers->all())
            ->only($keep)
            ->map(fn (array $values) => $values[0] ?? '')
            ->all();
    }
}
