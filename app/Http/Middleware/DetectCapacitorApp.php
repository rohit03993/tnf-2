<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectCapacitorApp
{
    public function handle(Request $request, Closure $next): Response
    {
        $isApp = str_contains($request->userAgent() ?? '', 'TNFTodayCapacitor')
            || $request->query('tnf_app') === '1'
            || $request->cookie('tnf_app') === '1';

        if ($request->query('tnf_app') === '1') {
            cookie()->queue('tnf_app', '1', 60 * 24 * 30);
        }

        $request->attributes->set('tnf_app', $isApp);
        view()->share('isApp', $isApp);

        return $next($request);
    }
}
