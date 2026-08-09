<?php

namespace App\Filament\Concerns;

use App\Services\ContentPublishService;
use App\Services\WhatsAppCloudService;
use App\Support\TnfSetting;
use Filament\Forms\Components\Toggle;

trait RequestsWhatsAppBroadcast
{
    protected function captureWhatsAppBroadcastFlag(): void
    {
        if (! empty($this->data['send_whatsapp_broadcast'])) {
            ContentPublishService::requestWhatsAppBroadcast(true);
        }
    }

    public static function whatsAppBroadcastToggle(string $settingKey, bool $default = false): Toggle
    {
        return Toggle::make('send_whatsapp_broadcast')
            ->label('Send WhatsApp alert to opted-in users')
            ->helperText('Uses your Meta templates and only goes to users who opted in (phone OTP / profile).')
            ->default(fn (): bool => TnfSetting::bool($settingKey, $default))
            ->dehydrated(false)
            ->visible(fn (): bool => app(WhatsAppCloudService::class)->isConfigured());
    }
}
