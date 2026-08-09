<?php

namespace App\Services;

use App\Models\OtpChallenge;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PhoneOtpService
{
    public function __construct(
        protected WhatsAppCloudService $whatsApp,
    ) {}

    public function requestLoginOtp(string $rawPhone, ?string $ip = null): string
    {
        $phone = PhoneNumber::normalize($rawPhone);

        if ($phone === null) {
            throw ValidationException::withMessages([
                'phone' => 'Enter a valid mobile number with country code (or 10-digit Indian number).',
            ]);
        }

        $recent = OtpChallenge::query()
            ->where('phone', $phone)
            ->where('purpose', 'login')
            ->where('created_at', '>=', now()->subMinute())
            ->exists();

        if ($recent) {
            throw ValidationException::withMessages([
                'phone' => 'Please wait a minute before requesting another code.',
            ]);
        }

        $hourly = OtpChallenge::query()
            ->where('phone', $phone)
            ->where('purpose', 'login')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($hourly >= 5) {
            throw ValidationException::withMessages([
                'phone' => 'Too many OTP requests. Try again later.',
            ]);
        }

        $code = (string) random_int(100000, 999999);

        OtpChallenge::query()->create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'purpose' => 'login',
            'expires_at' => now()->addMinutes(10),
            'ip_address' => $ip,
        ]);

        $sent = false;

        if ($this->whatsApp->isEnabled()) {
            $sent = $this->whatsApp->sendAuthenticationOtp($phone, $code);
        }

        if (! $sent) {
            Log::info('TNF phone OTP (WhatsApp not sent)', [
                'phone' => $phone,
                'code' => app()->environment('local', 'testing') ? $code : '[hidden]',
            ]);

            if (! app()->environment('local', 'testing')) {
                throw ValidationException::withMessages([
                    'phone' => 'Could not send WhatsApp OTP. Check WhatsApp API settings or try again.',
                ]);
            }
        }

        return $phone;
    }

    public function verifyLoginOtp(string $rawPhone, string $code): string
    {
        $phone = PhoneNumber::normalize($rawPhone);

        if ($phone === null) {
            throw ValidationException::withMessages([
                'phone' => 'Enter a valid mobile number.',
            ]);
        }

        $challenge = OtpChallenge::query()
            ->where('phone', $phone)
            ->where('purpose', 'login')
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $challenge || $challenge->isExpired()) {
            throw ValidationException::withMessages([
                'otp' => 'OTP expired. Request a new code.',
            ]);
        }

        if ($challenge->attempts >= 5) {
            throw ValidationException::withMessages([
                'otp' => 'Too many incorrect attempts. Request a new code.',
            ]);
        }

        $challenge->increment('attempts');

        if (! Hash::check($code, $challenge->code_hash)) {
            throw ValidationException::withMessages([
                'otp' => 'Incorrect OTP. Try again.',
            ]);
        }

        $challenge->forceFill(['consumed_at' => now()])->save();

        return $phone;
    }
}
