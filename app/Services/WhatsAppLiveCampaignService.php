<?php

namespace App\Services;

use App\Enums\WhatsAppLiveCampaignStatus;
use App\Models\WhatsappLiveCampaign;
use App\Models\WhatsappTemplate;
use App\Support\PhoneNumber;
use App\Support\TnfSetting;

class WhatsAppLiveCampaignService
{
    public function __construct(
        protected WhatsAppCloudService $whatsApp,
    ) {}

    public function resolveByName(string $campaignName): ?WhatsappLiveCampaign
    {
        $campaignName = trim($campaignName);
        if ($campaignName === '') {
            return null;
        }

        $live = WhatsappLiveCampaign::query()
            ->with('template')
            ->where('status', WhatsAppLiveCampaignStatus::Live)
            ->where('name', $campaignName)
            ->first();

        if ($live) {
            return $live;
        }

        return WhatsappLiveCampaign::query()
            ->with('template')
            ->where('status', WhatsAppLiveCampaignStatus::Live)
            ->whereHas('template', fn ($query) => $query
                ->where('name', $campaignName)
                ->where('status', 'APPROVED')
                ->where('is_active', true))
            ->orderBy('name')
            ->first();
    }

    public function resolveById(int|string|null $id): ?WhatsappLiveCampaign
    {
        if (! filled($id)) {
            return null;
        }

        return WhatsappLiveCampaign::query()
            ->with('template')
            ->whereKey((int) $id)
            ->where('status', WhatsAppLiveCampaignStatus::Live)
            ->first();
    }

    /**
     * Live campaigns for settings pickers.
     *
     * @return array<int, string>
     */
    public function liveOptions(): array
    {
        try {
            return WhatsappLiveCampaign::query()
                ->with('template')
                ->where('status', WhatsAppLiveCampaignStatus::Live)
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn (WhatsappLiveCampaign $campaign): array => [
                    $campaign->id => $campaign->label(),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param  list<mixed>  $templateParams
     * @return array{ok: bool, error: ?string, campaign_id: ?int}
     */
    public function triggerByName(
        string $campaignName,
        string $phone,
        ?string $userName = null,
        array $templateParams = [],
    ): array {
        $campaign = $this->resolveByName($campaignName);

        if ($campaign === null) {
            return [
                'ok' => false,
                'error' => "No live campaign found for '{$campaignName}'. Create one under WhatsApp → Live campaigns and click Go live.",
                'campaign_id' => null,
            ];
        }

        return $this->trigger($campaign, $phone, $userName, $templateParams);
    }

    /**
     * @param  list<mixed>  $templateParams
     * @return array{ok: bool, error: ?string, campaign_id: ?int}
     */
    public function trigger(
        WhatsappLiveCampaign $campaign,
        string $phone,
        ?string $userName = null,
        array $templateParams = [],
    ): array {
        if (! $this->whatsApp->isConfigured()) {
            return ['ok' => false, 'error' => 'WhatsApp is not configured.', 'campaign_id' => $campaign->id];
        }

        $template = $campaign->template;
        if ($template === null || ! $template->isApproved() || ! $template->is_active) {
            return [
                'ok' => false,
                'error' => 'Linked template is not approved/active for sending.',
                'campaign_id' => $campaign->id,
            ];
        }

        $phone = PhoneNumber::normalize($phone);
        if ($phone === null) {
            return ['ok' => false, 'error' => 'Invalid phone number.', 'campaign_id' => $campaign->id];
        }

        $params = $this->mergeTemplateParams($campaign, $templateParams, $userName);
        $ok = $this->whatsApp->sendTemplateBodyParams(
            phone: $phone,
            template: $template->name,
            language: $template->language ?: 'en',
            bodyParams: $params,
            bodyPreview: implode(' · ', $params),
            type: 'live_campaign',
        );

        return [
            'ok' => $ok,
            'error' => $ok ? null : 'Meta send failed.',
            'campaign_id' => $campaign->id,
        ];
    }

    public function goLive(WhatsappLiveCampaign $campaign): WhatsappLiveCampaign
    {
        $template = $campaign->template;

        if ($template === null || ! $template->isApproved()) {
            throw new \InvalidArgumentException('Cannot go live — linked template must be APPROVED in Meta.');
        }

        $campaign->update([
            'status' => WhatsAppLiveCampaignStatus::Live,
            'went_live_at' => $campaign->went_live_at ?? now(),
        ]);

        return $campaign->fresh() ?? $campaign;
    }

    public function pause(WhatsappLiveCampaign $campaign): WhatsappLiveCampaign
    {
        $campaign->update([
            'status' => WhatsAppLiveCampaignStatus::Draft,
        ]);

        return $campaign->fresh() ?? $campaign;
    }

    public function templateForSetting(string $settingKey): ?WhatsappTemplate
    {
        $id = TnfSetting::get($settingKey);
        $campaign = $this->resolveById($id);

        return $campaign?->template;
    }

    /**
     * @param  list<mixed>  $templateParams
     * @return list<string>
     */
    protected function mergeTemplateParams(
        WhatsappLiveCampaign $campaign,
        array $templateParams,
        ?string $userName,
    ): array {
        $params = collect($templateParams)
            ->map(fn (mixed $value): string => trim((string) $value))
            ->values()
            ->all();

        $defaults = $campaign->default_variables ?? [];
        if (is_array($defaults)) {
            foreach ($defaults as $index => $value) {
                if (! is_numeric($index)) {
                    continue;
                }
                $i = (int) $index;
                if (! isset($params[$i]) || $params[$i] === '') {
                    $params[$i] = trim((string) $value);
                }
            }
        }

        if (filled($userName) && ($params[0] ?? '') === '') {
            $params[0] = trim((string) $userName);
        }

        $expected = (int) ($campaign->template?->param_count ?? 0);
        while (count($params) < $expected) {
            $params[] = '—';
        }

        return array_slice(array_values($params), 0, max($expected, count($params)));
    }
}
