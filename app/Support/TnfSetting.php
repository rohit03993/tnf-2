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
        'whatsapp_access_token' => 'tnf.whatsapp_access_token',
        'whatsapp_phone_number_id' => 'tnf.whatsapp_phone_number_id',
        'whatsapp_business_account_id' => 'tnf.whatsapp_business_account_id',
        'whatsapp_app_id' => 'tnf.whatsapp_app_id',
        'whatsapp_app_secret' => 'tnf.whatsapp_app_secret',
        'whatsapp_webhook_verify_token' => 'tnf.whatsapp_webhook_verify_token',
        'whatsapp_graph_version' => 'tnf.whatsapp_graph_version',
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
