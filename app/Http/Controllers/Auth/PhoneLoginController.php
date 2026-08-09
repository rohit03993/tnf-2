<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PhoneAuthService;
use App\Services\PhoneOtpService;
use App\Services\WhatsAppCloudService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PhoneLoginController extends Controller
{
    public function create(Request $request, WhatsAppCloudService $whatsApp): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect(auth()->user()->homeUrl());
        }

        if ($request->boolean('reset')) {
            $request->session()->forget('phone_login_phone');
        }

        return view('auth.phone-login', [
            'whatsappReady' => $whatsApp->isEnabled() || app()->environment('local', 'testing'),
            'step' => $request->session()->has('phone_login_phone') ? 'otp' : 'phone',
            'phone' => $request->session()->get('phone_login_phone'),
        ]);
    }

    public function requestOtp(Request $request, PhoneOtpService $otp): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = $otp->requestLoginOtp($request->string('phone')->toString(), $request->ip());

        $request->session()->put('phone_login_phone', $phone);

        return redirect()
            ->route('login.phone')
            ->with('status', 'We sent a 6-digit code on WhatsApp.');
    }

    public function verify(Request $request, PhoneOtpService $otp, PhoneAuthService $auth): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $phone = $otp->verifyLoginOtp(
            $request->string('phone')->toString(),
            $request->string('otp')->toString(),
        );

        $user = $auth->findOrCreateByVerifiedPhone($phone);

        if (! $user->is_active) {
            return back()->withErrors(['otp' => 'This account is inactive.']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->forget('phone_login_phone');

        return redirect()->intended($user->homeUrl());
    }
}
