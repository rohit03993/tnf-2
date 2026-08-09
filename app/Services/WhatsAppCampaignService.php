<?php

namespace App\Services;

use App\Enums\WhatsAppAudienceType;
use App\Enums\WhatsAppCampaignStatus;
use App\Enums\WhatsAppRecipientStatus;
use App\Jobs\RunWhatsAppCampaignJob;
use App\Models\User;
use App\Models\WhatsappCampaign;
use App\Models\WhatsappCampaignRecipient;
use App\Models\WhatsappTemplate;
use App\Support\FrontendUrl;
use App\Support\PhoneNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WhatsAppCampaignService
{
    public function __construct(
        protected WhatsAppCloudService $whatsApp,
    ) {}

    /**
     * @param  array{
     *   name: string,
     *   whatsapp_template_id: int,
     *   audience_type?: string,
     *   campaign_variables?: array<string, mixed>|null,
     *   user_ids?: list<int>|null
     * }  $data
     */
    public function createCampaign(array $data, ?User $creator = null): WhatsappCampaign
    {
        $template = WhatsappTemplate::query()->findOrFail($data['whatsapp_template_id']);
        $audienceType = WhatsAppAudienceType::tryFrom((string) ($data['audience_type'] ?? ''))
            ?? WhatsAppAudienceType::OptedIn;

        $users = $this->audienceUsers($data, $audienceType);

        $campaign = WhatsappCampaign::query()->create([
            'whatsapp_template_id' => $template->id,
            'name' => (string) $data['name'],
            'status' => WhatsAppCampaignStatus::Draft,
            'audience_type' => $audienceType,
            'campaign_variables' => $this->normalizeCampaignVariables($data['campaign_variables'] ?? null),
            'total_recipients' => $users->count(),
            'created_by' => $creator?->id,
        ]);

        foreach ($users as $user) {
            $phone = PhoneNumber::normalize($user->phone);
            if ($phone === null) {
                continue;
            }

            WhatsappCampaignRecipient::query()->create([
                'whatsapp_campaign_id' => $campaign->id,
                'user_id' => $user->id,
                'phone' => $phone,
                'status' => WhatsAppRecipientStatus::Pending,
            ]);
        }

        $campaign->update([
            'total_recipients' => $campaign->recipients()->count(),
        ]);

        return $campaign->fresh(['template', 'recipients']) ?? $campaign;
    }

    /**
     * Create + queue a news/ePaper broadcast campaign (used by publish auto-share).
     */
    public function createContentBroadcast(string $title, string $url, string $kind = 'news', ?User $creator = null): ?WhatsappCampaign
    {
        if (! $this->whatsApp->isEnabled()) {
            return null;
        }

        $templateKey = $kind === 'epaper' ? 'whatsapp_epaper_template' : 'whatsapp_news_template';
        $langKey = $kind === 'epaper' ? 'whatsapp_epaper_template_lang' : 'whatsapp_news_template_lang';
        $defaultName = $kind === 'epaper' ? 'tnf_epaper_alert' : 'tnf_news_alert';

        $templateName = trim((string) \App\Support\TnfSetting::get($templateKey, $defaultName));
        $language = trim((string) \App\Support\TnfSetting::get($langKey, 'en'));

        $template = WhatsappTemplate::query()
            ->where('name', $templateName)
            ->where('language', $language)
            ->where('status', 'APPROVED')
            ->first();

        if (! $template) {
            $template = WhatsappTemplate::query()
                ->where('name', $templateName)
                ->where('status', 'APPROVED')
                ->orderBy('id')
                ->first();
        }

        if (! $template) {
            // Fall back: ensure a local template row exists so campaigns still work.
            $template = WhatsappTemplate::query()->updateOrCreate(
                ['name' => $templateName, 'language' => $language !== '' ? $language : 'en'],
                [
                    'status' => 'APPROVED',
                    'param_count' => 2,
                    'param_mappings' => ['campaign.title', 'campaign.url'],
                    'body' => '{{1}}'."\n".'{{2}}',
                    'is_active' => true,
                    'synced_at' => now(),
                ],
            );
        }

        $absoluteUrl = FrontendUrl::to($url);
        $shortTitle = Str::limit(trim($title), 60, '…');

        $campaign = $this->createCampaign([
            'name' => ($kind === 'epaper' ? 'ePaper' : 'News').' · '.$shortTitle,
            'whatsapp_template_id' => $template->id,
            'audience_type' => WhatsAppAudienceType::OptedIn->value,
            'campaign_variables' => [
                'title' => $shortTitle,
                'url' => $absoluteUrl,
                'kind' => $kind,
            ],
        ], $creator);

        if ($campaign->total_recipients < 1) {
            return $campaign;
        }

        return $this->queueCampaign($campaign, $creator);
    }

    public function estimateAudienceCount(array $data): int
    {
        $audienceType = WhatsAppAudienceType::tryFrom((string) ($data['audience_type'] ?? ''))
            ?? WhatsAppAudienceType::OptedIn;

        return $this->audienceUsers($data, $audienceType)->count();
    }

    public function queueCampaign(WhatsappCampaign $campaign, ?User $sender = null): WhatsappCampaign
    {
        if (! $this->whatsApp->isConfigured()) {
            throw new \RuntimeException('Configure WhatsApp Access token + Phone number ID first.');
        }

        $this->refreshCampaignRecipients($campaign);
        $campaign->refresh();

        if ((int) $campaign->total_recipients < 1) {
            throw new \RuntimeException('No opted-in users with phone numbers for this campaign.');
        }

        $campaign->update([
            'status' => WhatsAppCampaignStatus::Queued,
            'shot_by' => $sender?->id,
            'shot_at' => now(),
        ]);

        $this->runCampaignNow($campaign);

        return $campaign->fresh() ?? $campaign;
    }

    public function pauseCampaign(WhatsappCampaign $campaign): WhatsappCampaign
    {
        if (in_array($campaign->status, [WhatsAppCampaignStatus::Completed, WhatsAppCampaignStatus::Draft], true)) {
            return $campaign;
        }

        $campaign->update(['status' => WhatsAppCampaignStatus::Paused]);

        return $campaign->fresh() ?? $campaign;
    }

    public function resumeCampaign(WhatsappCampaign $campaign, ?User $sender = null): WhatsappCampaign
    {
        if ($campaign->status !== WhatsAppCampaignStatus::Paused) {
            return $campaign;
        }

        return $this->queueCampaign($campaign, $sender);
    }

    protected function runCampaignNow(WhatsappCampaign $campaign): void
    {
        $inlineLimit = (int) config('whatsapp.inline_campaign_recipient_limit', 50);
        $total = (int) $campaign->total_recipients;
        $batchSize = max(1, min(50, (int) config('whatsapp.batch_size', 10)));
        $maxRuns = max(1, (int) ceil($total / $batchSize)) + 2;

        if ($total > $inlineLimit) {
            RunWhatsAppCampaignJob::dispatch($campaign->id);

            return;
        }

        $runs = 0;

        while ($runs < $maxRuns) {
            RunWhatsAppCampaignJob::dispatchSync($campaign->id);
            $campaign->refresh();

            if ($campaign->status === WhatsAppCampaignStatus::Completed) {
                return;
            }

            $pending = $campaign->recipients()
                ->whereIn('status', [
                    WhatsAppRecipientStatus::Pending->value,
                    WhatsAppRecipientStatus::Processing->value,
                ])
                ->exists();

            if (! $pending) {
                return;
            }

            $runs++;
        }
    }

    public function refreshCampaignRecipients(WhatsappCampaign $campaign): void
    {
        if ($campaign->status !== WhatsAppCampaignStatus::Draft) {
            return;
        }

        $existingPhones = $campaign->recipients()->pluck('phone')->all();
        $users = $this->audienceUsers([
            'audience_type' => $campaign->audience_type?->value,
            'user_ids' => data_get($campaign->campaign_variables, '_user_ids'),
        ], $campaign->audience_type ?? WhatsAppAudienceType::OptedIn);

        foreach ($users as $user) {
            $phone = PhoneNumber::normalize($user->phone);
            if ($phone === null || in_array($phone, $existingPhones, true)) {
                continue;
            }

            WhatsappCampaignRecipient::query()->create([
                'whatsapp_campaign_id' => $campaign->id,
                'user_id' => $user->id,
                'phone' => $phone,
                'status' => WhatsAppRecipientStatus::Pending,
            ]);
        }

        $campaign->update([
            'total_recipients' => $campaign->recipients()->count(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, User>
     */
    protected function audienceUsers(array $data, WhatsAppAudienceType $audienceType): Collection
    {
        if ($audienceType === WhatsAppAudienceType::Manual && ! empty($data['user_ids'])) {
            return User::query()
                ->whereIn('id', $data['user_ids'])
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->where('is_active', true)
                ->get();
        }

        return User::query()
            ->where('whatsapp_opt_in', true)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>|null  $variables
     * @return array<string, mixed>|null
     */
    protected function normalizeCampaignVariables(?array $variables): ?array
    {
        if ($variables === null || $variables === []) {
            return null;
        }

        return $variables;
    }
}
