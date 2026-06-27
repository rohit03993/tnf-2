<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('tnf.security_headers_enabled', true)) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        if ($request->secure() && config('tnf.hsts_enabled', false)) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    protected function contentSecurityPolicy(): string
    {
        // Filament/Livewire/Alpine need eval in non-CSP-safe mode (see livewire csp_safe config).
        $scriptSrc = "'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com";
        $connectSrc = "'self'";

        if (app()->environment('local')) {
            $scriptSrc .= ' http://127.0.0.1:5173 http://localhost:5173';
            $connectSrc .= ' ws://127.0.0.1:5173 ws://localhost:5173 http://127.0.0.1:5173 http://localhost:5173';
        }

        $imgSrc = "'self' data: blob: https:";

        if (app()->environment('local')) {
            $imgSrc .= ' http://127.0.0.1:8000 http://localhost:8000';
        }

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "img-src {$imgSrc}",
            "font-src 'self' https://fonts.gstatic.com data:",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "script-src {$scriptSrc}",
            "connect-src {$connectSrc}",
            "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com",
        ];

        return implode('; ', $directives).';';
    }
}
