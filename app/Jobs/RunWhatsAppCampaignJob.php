<?php

namespace App\Jobs;

use App\Services\WhatsAppCampaignRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunWhatsAppCampaignJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 3;

    public function __construct(
        public int $campaignId,
    ) {}

    public function handle(WhatsAppCampaignRunner $runner): void
    {
        $runner->run($this->campaignId);
    }
}
