<?php

namespace App\Services;

use App\Support\FrontendUrl;
use App\Support\TnfSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    public function isConfigured(): bool
    {
        return filled(TnfSetting::get('onesignal_app_id'))
            && filled(TnfSetting::get('onesignal_rest_key'));
    }

    public function isEnabled(): bool
    {
        return TnfSetting::bool('push_enabled', false) && $this->isConfigured();
    }

    public function send(string $title, string $message, string $url): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic '.TnfSetting::get('onesignal_rest_key'),
            'Content-Type' => 'application/json',
        ])->post('https://onesignal.com/api/v1/notifications', [
            'app_id' => TnfSetting::get('onesignal_app_id'),
            'included_segments' => ['All'],
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
            'url' => FrontendUrl::to($url),
        ]);

        if (! $response->successful()) {
            Log::warning('OneSignal push failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    public function sendTest(): bool
    {
        return $this->send(
            'TNF Today — test notification',
            'Push notifications are configured correctly.',
            FrontendUrl::route('home'),
        );
    }
}
