<?php

namespace App\Http\Middleware;

use App\Support\TnfSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPdfCallbackSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = TnfSetting::get('pdf_callback_secret');

        if (! $expected) {
            abort(503, 'PDF callback secret is not configured.');
        }

        $provided = $request->header('X-Callback-Secret')
            ?? $request->header('X-PDF-Callback-Secret');

        if (! hash_equals($expected, (string) $provided)) {
            abort(401, 'Invalid PDF callback secret.');
        }

        return $next($request);
    }
}
