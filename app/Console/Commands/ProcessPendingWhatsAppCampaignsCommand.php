<?php

namespace App\Console\Commands;

use App\Enums\WhatsAppCampaignStatus;
use App\Jobs\RunWhatsAppCampaignJob;
use App\Models\WhatsappCampaign;
use Illuminate\Console\Command;

class ProcessPendingWhatsAppCampaignsCommand extends Command
{
    protected $signature = 'whatsapp:process-pending-campaigns';

    protected $description = 'Re-queue stuck WhatsApp campaigns that still have pending recipients';

    public function handle(): int
    {
        $campaigns = WhatsappCampaign::query()
            ->whereIn('status', [
                WhatsAppCampaignStatus::Queued->value,
                WhatsAppCampaignStatus::Running->value,
            ])
            ->whereHas('recipients', function ($query): void {
                $query->whereIn('status', ['pending', 'processing']);
            })
            ->where(function ($query): void {
                $query->whereNull('started_at')
                    ->orWhere('updated_at', '<', now()->subMinutes(5));
            })
            ->limit(20)
            ->get();

        foreach ($campaigns as $campaign) {
            RunWhatsAppCampaignJob::dispatch($campaign->id);
            $this->line('Re-queued campaign #'.$campaign->id);
        }

        return self::SUCCESS;
    }
}
