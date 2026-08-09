<?php

namespace App\Services;

use App\Enums\WhatsAppCampaignStatus;
use App\Enums\WhatsAppRecipientStatus;
use App\Jobs\RunWhatsAppCampaignJob;
use App\Models\WhatsappCampaign;
use App\Models\WhatsappCampaignRecipient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsAppCampaignRunner
{
    public function __construct(
        protected WhatsAppCloudService $whatsApp,
        protected WhatsAppTemplateParamResolver $paramResolver,
    ) {}

    public function run(int $campaignId, ?int $batchSize = null): void
    {
        $campaign = WhatsappCampaign::query()
            ->with(['template', 'shotBy'])
            ->find($campaignId);

        if (! $campaign) {
            return;
        }

        $batchSize = max(1, min(50, $batchSize ?: (int) config('whatsapp.batch_size', 10)));

        if ($campaign->status === WhatsAppCampaignStatus::Completed
            || $campaign->status === WhatsAppCampaignStatus::Paused) {
            return;
        }

        if ($campaign->status === WhatsAppCampaignStatus::Draft) {
            $campaign->update(['status' => WhatsAppCampaignStatus::Queued]);
        }

        if (! $campaign->started_at) {
            $campaign->update(['started_at' => now()]);
        }

        $campaign->update(['status' => WhatsAppCampaignStatus::Running]);

        if (! $this->whatsApp->isConfigured()) {
            $this->failPendingRecipients($campaign, 'WhatsApp is not configured.');
            $this->finalizeIfDone($campaign);

            return;
        }

        $template = $campaign->template;
        if (! $template) {
            $this->failPendingRecipients($campaign, 'Template missing.');
            $this->finalizeIfDone($campaign);

            return;
        }

        $template->ensureParamMappings();
        $sources = $template->paramSources();
        $paramCount = max((int) $template->param_count, count($sources));

        $claimedIds = $this->claimBatchRecipientIds($campaign->id, $batchSize);

        foreach (WhatsappCampaignRecipient::query()->whereIn('id', $claimedIds)->cursor() as $recipient) {
            $recipient->loadMissing('user');
            $user = $recipient->user;

            $params = $this->paramResolver->resolveAll($sources, $user, $campaign);
            while (count($params) < $paramCount) {
                $params[] = '—';
            }
            $params = array_slice(array_map(
                static fn ($v) => trim((string) $v) === '' ? '—' : (string) $v,
                $params,
            ), 0, $paramCount);

            if ($paramCount > 0 && ! $this->paramResolver->hasResolvableParams(
                array_map(static fn ($v) => $v === '—' ? '' : $v, $params),
            ) && collect($sources)->contains(fn ($s) => str_starts_with((string) $s, 'manual'))) {
                // Only hard-fail empty manual-required params that stayed blank after resolve.
            }

            $ok = $this->whatsApp->sendTemplateBodyParams(
                phone: $recipient->phone,
                template: $template->name,
                language: $template->language ?: 'en',
                bodyParams: $params,
                bodyPreview: $this->paramResolver->buildPreview($template->body, $params),
                type: 'campaign',
            );

            $recipient->template_params = $params;
            $recipient->message_sent = $this->paramResolver->buildPreview($template->body, $params);

            if ($ok) {
                $recipient->status = WhatsAppRecipientStatus::Sent;
                $recipient->error_message = null;
                $recipient->wamid = \App\Models\WhatsappMessage::query()
                    ->where('phone', $recipient->phone)
                    ->where('template_name', $template->name)
                    ->orderByDesc('id')
                    ->value('wamid');
            } else {
                $recipient->status = WhatsAppRecipientStatus::Failed;
                $recipient->error_message = \App\Models\WhatsappMessage::query()
                    ->where('phone', $recipient->phone)
                    ->where('template_name', $template->name)
                    ->orderByDesc('id')
                    ->value('error_message') ?: 'Send failed';
            }

            $recipient->save();
        }

        $campaign->update([
            'sent_count' => $campaign->recipients()->where('status', WhatsAppRecipientStatus::Sent)->count(),
            'failed_count' => $campaign->recipients()->where('status', WhatsAppRecipientStatus::Failed)->count(),
        ]);

        $remaining = $campaign->recipients()
            ->whereIn('status', [
                WhatsAppRecipientStatus::Pending->value,
                WhatsAppRecipientStatus::Processing->value,
            ])
            ->exists();

        if ($remaining && $campaign->fresh()?->status !== WhatsAppCampaignStatus::Paused) {
            $delay = max(0, (int) config('whatsapp.next_batch_delay_seconds', 2));
            RunWhatsAppCampaignJob::dispatch($campaign->id)->delay(now()->addSeconds($delay));

            return;
        }

        $this->finalizeIfDone($campaign);
    }

    /**
     * @return list<int>
     */
    protected function claimBatchRecipientIds(int $campaignId, int $batchSize): array
    {
        return DB::transaction(function () use ($campaignId, $batchSize): array {
            // Reset stale processing (>15 min)
            WhatsappCampaignRecipient::query()
                ->where('whatsapp_campaign_id', $campaignId)
                ->where('status', WhatsAppRecipientStatus::Processing)
                ->where('updated_at', '<', now()->subMinutes(15))
                ->update(['status' => WhatsAppRecipientStatus::Pending->value]);

            $ids = WhatsappCampaignRecipient::query()
                ->where('whatsapp_campaign_id', $campaignId)
                ->where('status', WhatsAppRecipientStatus::Pending)
                ->orderBy('id')
                ->limit($batchSize)
                ->lockForUpdate()
                ->pluck('id')
                ->all();

            if ($ids !== []) {
                WhatsappCampaignRecipient::query()
                    ->whereIn('id', $ids)
                    ->update(['status' => WhatsAppRecipientStatus::Processing->value]);
            }

            return $ids;
        });
    }

    protected function failPendingRecipients(WhatsappCampaign $campaign, string $error): void
    {
        WhatsappCampaignRecipient::query()
            ->where('whatsapp_campaign_id', $campaign->id)
            ->whereIn('status', [
                WhatsAppRecipientStatus::Pending->value,
                WhatsAppRecipientStatus::Processing->value,
            ])
            ->update([
                'status' => WhatsAppRecipientStatus::Failed->value,
                'error_message' => $error,
            ]);

        Log::warning('WhatsApp campaign failed pending recipients', [
            'campaign_id' => $campaign->id,
            'error' => $error,
        ]);
    }

    protected function finalizeIfDone(WhatsappCampaign $campaign): void
    {
        $campaign->refresh();

        $remaining = $campaign->recipients()
            ->whereIn('status', [
                WhatsAppRecipientStatus::Pending->value,
                WhatsAppRecipientStatus::Processing->value,
            ])
            ->exists();

        if ($remaining) {
            return;
        }

        $campaign->update([
            'status' => WhatsAppCampaignStatus::Completed,
            'finished_at' => now(),
            'sent_count' => $campaign->recipients()->where('status', WhatsAppRecipientStatus::Sent)->count(),
            'failed_count' => $campaign->recipients()->where('status', WhatsAppRecipientStatus::Failed)->count(),
        ]);
    }
}
