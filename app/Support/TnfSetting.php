<?php

namespace App\Support;

use App\Models\Setting;

class TnfSetting
{
    /** @var array<string, string> */
    private const CONFIG_KEYS = [
        'pdf_service_url' => 'tnf.pdf_service_url',
        'pdf_service_secret' => 'tnf.pdf_service_secret',
        'pdf_callback_secret' => 'tnf.pdf_callback_secret',
        'pdf_use_queue' => 'tnf.pdf_use_queue',
        'onesignal_app_id' => 'tnf.onesignal_app_id',
        'onesignal_rest_key' => 'tnf.onesignal_rest_key',
        'frontend_url' => 'tnf.frontend_url',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $fallback = $default ?? config(self::CONFIG_KEYS[$key] ?? "tnf.{$key}");

        return Setting::get($key, $fallback);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        return filter_var(static::get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }
}
