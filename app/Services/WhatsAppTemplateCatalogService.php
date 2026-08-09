<?php

namespace App\Services;

use App\Models\WhatsappTemplate;
use App\Support\WhatsAppTemplateBuilder;
use Illuminate\Support\Facades\Http;

class WhatsAppTemplateCatalogService
{
    public function __construct(
        protected WhatsAppCloudService $whatsApp,
    ) {}

    /**
     * @return array{ok: bool, count: int, approved: int, error: ?string}
     */
    public function syncFromMeta(): array
    {
        $result = $this->whatsApp->listMessageTemplates();

        if (! $result['ok']) {
            return [
                'ok' => false,
                'count' => 0,
                'approved' => 0,
                'error' => $result['error'],
            ];
        }

        foreach ($result['templates'] as $row) {
            $name = (string) ($row['name'] ?? '');
            $language = (string) ($row['language'] ?? 'en');
            if ($name === '') {
                continue;
            }

            $status = strtoupper((string) ($row['status'] ?? 'UNKNOWN'));
            $body = $row['body'] ?? null;

            WhatsappTemplate::query()->updateOrCreate(
                ['name' => $name, 'language' => $language],
                [
                    'status' => $status,
                    'category' => $row['category'] ?? null,
                    'param_count' => $body ? count(WhatsAppTemplateBuilder::placeholderOrder((string) $body)) : 0,
                    'body' => $body,
                    'meta_template_id' => $row['id'] ?? null,
                    'provider_meta' => $row,
                    'is_active' => $status === 'APPROVED',
                    'synced_at' => now(),
                ],
            );
        }

        $this->whatsApp->syncMessageTemplates();

        return [
            'ok' => true,
            'count' => count($result['templates']),
            'approved' => WhatsappTemplate::query()->where('status', 'APPROVED')->count(),
            'error' => null,
        ];
    }

    /**
     * @param  array{
     *   name: string,
     *   language: string,
     *   category: string,
     *   body_text: string,
     *   header_text?: ?string,
     *   footer_text?: ?string,
     *   body_examples?: ?string
     * }  $data
     * @return array{ok: bool, template: ?WhatsappTemplate, error: ?string}
     */
    public function submitToMeta(array $data): array
    {
        if (! $this->whatsApp->isConfigured() || blank($this->whatsApp->businessAccountId())) {
            return ['ok' => false, 'template' => null, 'error' => 'Configure Access token + Phone number ID + WABA first.'];
        }

        try {
            $payload = WhatsAppTemplateBuilder::buildCreatePayload(
                $data['name'],
                $data['language'],
                $data['category'],
                $data['body_text'],
                $data['header_text'] ?? null,
                $data['footer_text'] ?? null,
                $data['body_examples'] ?? null,
            );
        } catch (\Throwable $e) {
            return ['ok' => false, 'template' => null, 'error' => $e->getMessage()];
        }

        $response = Http::withToken((string) $this->whatsApp->accessToken())
            ->acceptJson()
            ->timeout(60)
            ->post(
                $this->whatsApp->publicGraphUrl($this->whatsApp->businessAccountId().'/message_templates'),
                $payload,
            );

        if (! $response->successful()) {
            $error = data_get($response->json(), 'error.message') ?: $response->body();

            return ['ok' => false, 'template' => null, 'error' => (string) $error];
        }

        $json = $response->json();
        $status = strtoupper((string) ($json['status'] ?? 'PENDING'));

        $template = WhatsappTemplate::query()->updateOrCreate(
            [
                'name' => $payload['name'],
                'language' => $payload['language'],
            ],
            [
                'status' => $status,
                'category' => $payload['category'],
                'param_count' => count(WhatsAppTemplateBuilder::placeholderOrder($data['body_text'])),
                'body' => $data['body_text'],
                'components' => $payload['components'],
                'meta_template_id' => $json['id'] ?? null,
                'provider_meta' => $json,
                'is_active' => $status === 'APPROVED',
                'synced_at' => now(),
            ],
        );

        return ['ok' => true, 'template' => $template, 'error' => null];
    }
}
