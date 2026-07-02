<?php

namespace App\Support;

use App\Models\Setting;

final class SiteContact
{
    public static function email(): string
    {
        return (string) Setting::get(
            'contact_email',
            Setting::get('disclaimer_email', config('tnf.contact_email', 'contact@tnftoday.com')),
        );
    }

    public static function phone(): ?string
    {
        $phone = trim((string) Setting::get('contact_phone', config('tnf.contact_phone', '')));

        return $phone !== '' ? $phone : null;
    }

    public static function phoneTel(): ?string
    {
        $phone = static::phone();

        if ($phone === null) {
            return null;
        }

        $normalized = preg_replace('/[^\d+]/', '', $phone);

        return filled($normalized) ? $normalized : null;
    }

    public static function company(): string
    {
        return (string) Setting::get(
            'contact_company',
            config('tnf.contact_company', 'TNF Today Media Network Pvt Ltd'),
        );
    }

    public static function address(): ?string
    {
        $address = trim((string) Setting::get('contact_address', config('tnf.contact_address', '')));

        return $address !== '' ? $address : null;
    }
}
