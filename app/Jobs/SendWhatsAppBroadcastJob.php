<?php

namespace App\Jobs;

use App\Services\WhatsAppCampaignService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendWhatsAppBroadcastJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $url,
        public string $kind = 'news',
    ) {}

    public function handle(WhatsAppCampaignService $campaigns): void
    {
        try {
            $campaign = $campaigns->createContentBroadcast($this->title, $this->url, $this->kind);

            if ($campaign) {
                Log::info('WhatsApp content broadcast queued as campaign', [
                    'campaign_id' => $campaign->id,
                    'kind' => $this->kind,
                    'recipients' => $campaign->total_recipients,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('WhatsApp content broadcast campaign failed', [
                'kind' => $this->kind,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
