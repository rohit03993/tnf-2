<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\WhatsAppCloudService;
use App\Support\WhatsAppOtpReadiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Default public login is WhatsApp OTP (mobile). Staff can use /login/email.
     */
    public function create(Request $request, WhatsAppCloudService $whatsApp): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect(auth()->user()->homeUrl());
        }

        $redirectTo = $request->query('redirect_to');

        if (is_string($redirectTo) && str_starts_with($redirectTo, '/') && ! str_starts_with($redirectTo, '//')) {
            session(['url.intended' => $redirectTo]);
        }

        if ($request->boolean('reset')) {
            $request->session()->forget(['phone_login_phone', 'phone_login_name']);
        }

        return view('auth.phone-login', [
            'whatsappReady' => WhatsAppOtpReadiness::ready($whatsApp),
            'whatsappHint' => WhatsAppOtpReadiness::hint($whatsApp),
            'step' => $request->session()->has('phone_login_phone') ? 'otp' : 'phone',
            'phone' => $request->session()->get('phone_login_phone'),
        ]);
    }

    public function createEmail(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect(auth()->user()->homeUrl());
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(auth()->user()->homeUrl());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
