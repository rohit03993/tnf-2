<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PhoneAuthService;
use App\Services\PhoneOtpService;
use App\Services\WhatsAppCloudService;
use App\Support\WhatsAppOtpReadiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request, WhatsAppCloudService $whatsApp): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect(auth()->user()->homeUrl());
        }

        if (! config('tnf.allow_public_registration')) {
            abort(403, 'Public registration is currently disabled.');
        }

        if ($request->boolean('reset')) {
            $request->session()->forget(['phone_login_phone', 'phone_login_name']);
        }

        return view('auth.register', [
            'whatsappReady' => WhatsAppOtpReadiness::ready($whatsApp),
            'whatsappHint' => WhatsAppOtpReadiness::hint($whatsApp),
            'step' => $request->session()->has('phone_login_phone') ? 'otp' : 'phone',
            'phone' => $request->session()->get('phone_login_phone'),
            'name' => $request->session()->get('phone_login_name'),
        ]);
    }

    public function store(Request $request, PhoneOtpService $otp): RedirectResponse
    {
        if (! config('tnf.allow_public_registration')) {
            abort(403, 'Public registration is currently disabled.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = $otp->requestLoginOtp($request->string('phone')->toString(), $request->ip());

        $request->session()->put('phone_login_phone', $phone);
        $request->session()->put('phone_login_name', $request->string('name')->trim()->toString());

        return redirect()
            ->route('register')
            ->with('status', 'We sent a 6-digit code on WhatsApp. Enter it to finish registration.');
    }

    public function verify(Request $request, PhoneOtpService $otp, PhoneAuthService $auth): RedirectResponse
    {
        if (! config('tnf.allow_public_registration')) {
            abort(403, 'Public registration is currently disabled.');
        }

        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'string', 'size:6'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $phone = $otp->verifyLoginOtp(
            $request->string('phone')->toString(),
            $request->string('otp')->toString(),
        );

        $name = $request->string('name')->toString()
            ?: (string) $request->session()->get('phone_login_name');

        $user = $auth->findOrCreateByVerifiedPhone($phone, filled($name) ? $name : null);

        if (! $user->is_active) {
            return back()->withErrors(['otp' => 'This account is inactive.']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->forget(['phone_login_phone', 'phone_login_name']);

        return redirect()->intended($user->homeUrl());
    }
}
