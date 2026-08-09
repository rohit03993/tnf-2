<?php

namespace App\Support;

use App\Services\WhatsAppCloudService;

class WhatsAppOtpReadiness
{
    public static function ready(WhatsAppCloudService $whatsApp): bool
    {
        return $whatsApp->isEnabled() || app()->environment('local', 'testing');
    }

    public static function hint(WhatsAppCloudService $whatsApp): ?string
    {
        if (self::ready($whatsApp)) {
            return null;
        }

        if (! $whatsApp->isConfigured()) {
            return 'Save Access token + Phone number ID in Admin → Settings → WhatsApp API.';
        }

        if (! TnfSetting::bool('whatsapp_enabled', false)) {
            return 'Turn ON “Enable WhatsApp integration” in Admin → Settings → WhatsApp API, then Save.';
        }

        if (! filled(TnfSetting::get('whatsapp_otp_live_campaign_id')) && ! filled(TnfSetting::get('whatsapp_otp_template'))) {
            return 'Create a Live OTP campaign (Go live) and select it under WhatsApp API → OTP live campaign.';
        }

        return 'WhatsApp OTP is not ready. Check Admin → Settings → WhatsApp API.';
    }
}
