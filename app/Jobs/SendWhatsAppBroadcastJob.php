<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\WhatsAppCloudService;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWhatsAppBroadcastJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $url,
        public string $kind = 'news',
    ) {}

    public function handle(WhatsAppCloudService $whatsApp): void
    {
        if (! $whatsApp->isEnabled()) {
            return;
        }

        User::query()
            ->where('whatsapp_opt_in', true)
            ->whereNotNull('phone')
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($whatsApp): void {
                foreach ($users as $user) {
                    $phone = PhoneNumber::normalize($user->phone);
                    if ($phone === null) {
                        continue;
                    }

                    $whatsApp->sendContentAlert($phone, $this->title, $this->url, $this->kind);
                }
            });
    }
}
