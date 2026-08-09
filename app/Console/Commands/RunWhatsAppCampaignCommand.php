<?php

namespace App\Console\Commands;

use App\Enums\WhatsAppCampaignStatus;
use App\Jobs\RunWhatsAppCampaignJob;
use App\Models\WhatsappCampaign;
use App\Services\WhatsAppCampaignRunner;
use Illuminate\Console\Command;

class RunWhatsAppCampaignCommand extends Command
{
    protected $signature = 'whatsapp:run-campaign {campaign : Campaign ID} {--batch= : Max recipients per run}';

    protected $description = 'Send pending WhatsApp messages for a campaign via Meta Cloud API';

    public function handle(WhatsAppCampaignRunner $runner): int
    {
        $batch = $this->option('batch');
        $runner->run((int) $this->argument('campaign'), filled($batch) ? (int) $batch : null);
        $this->info('Campaign batch processed.');

        return self::SUCCESS;
    }
}
