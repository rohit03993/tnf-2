<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAppFromAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->attributes->get('tnf_app')
            && $request->is('admin', 'admin/*')) {
            $destination = auth()->check() ? route('account') : route('login');

            return redirect()
                ->to($destination)
                ->with('status', 'Editorial CMS is available on desktop only.');
        }

        return $next($request);
    }
}
