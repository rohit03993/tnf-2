<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalize to digits-only E.164 without leading +.
     * Indian 10-digit numbers become 91XXXXXXXXXX.
     */
    public static function normalize(?string $input, string $defaultCountryCode = '91'): ?string
    {
        if ($input === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $input) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && $defaultCountryCode !== '') {
            $digits = $defaultCountryCode.$digits;
        }

        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }

    public static function formatDisplay(?string $e164Digits): ?string
    {
        $digits = static::normalize($e164Digits);

        if ($digits === null) {
            return null;
        }

        if (str_starts_with($digits, '91') && strlen($digits) === 12) {
            return '+91 '.substr($digits, 2, 5).' '.substr($digits, 7);
        }

        return '+'.$digits;
    }
}
