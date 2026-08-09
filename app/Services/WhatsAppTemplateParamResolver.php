<?php

namespace App\Services;

use App\Models\User;
use App\Models\WhatsappCampaign;
use Illuminate\Support\Str;

class WhatsAppTemplateParamResolver
{
    /**
     * @param  list<string>  $sources
     * @return list<string>
     */
    public function resolveAll(array $sources, ?User $user, WhatsappCampaign $campaign): array
    {
        $manual = data_get($campaign->campaign_variables, '_manual', []);
        $manual = is_array($manual) ? array_values($manual) : [];
        $manualIndex = 0;

        $resolved = [];

        foreach ($sources as $source) {
            $source = trim((string) $source);

            if ($source === '' || str_starts_with($source, 'manual')) {
                $resolved[] = (string) ($manual[$manualIndex] ?? '');
                $manualIndex++;

                continue;
            }

            $resolved[] = $this->resolveSource($source, $user, $campaign);
        }

        return $resolved;
    }

    public function resolveSource(string $source, ?User $user, WhatsappCampaign $campaign): string
    {
        return match ($source) {
            'user.name' => (string) ($user?->name ?: 'Reader'),
            'user.phone' => (string) ($user?->phone ?: ''),
            'campaign.title', 'campaign.topic', 'campaign.headline' => Str::limit(
                trim((string) $campaign->campaignVariable('title', '')),
                60,
                '…',
            ),
            'campaign.url', 'campaign.link' => trim((string) $campaign->campaignVariable('url', '')),
            'campaign.kind' => trim((string) $campaign->campaignVariable('kind', 'news')),
            default => str_starts_with($source, '"') && str_ends_with($source, '"')
                ? trim($source, '"')
                : trim((string) $campaign->campaignVariable($source, '')),
        };
    }

    /**
     * @param  list<string>  $params
     */
    public function buildPreview(?string $body, array $params): string
    {
        $preview = (string) $body;

        foreach ($params as $index => $value) {
            $preview = str_replace('{{'.($index + 1).'}}', (string) $value, $preview);
        }

        return $preview !== '' ? $preview : implode(' · ', array_filter($params));
    }

    /**
     * @param  list<string>  $params
     */
    public function hasResolvableParams(array $params): bool
    {
        foreach ($params as $param) {
            if (trim((string) $param) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    public static function sourceOptions(): array
    {
        return [
            'user.name' => 'User name',
            'user.phone' => 'User phone',
            'campaign.title' => 'Campaign title / headline',
            'campaign.url' => 'Campaign URL / link',
            'campaign.kind' => 'Campaign kind (news/epaper)',
            'manual' => 'Manual value (filled on campaign)',
        ];
    }
}
