<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsappMessage;
use App\Support\FrontendUrl;
use App\Support\PhoneNumber;
use App\Support\TnfSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppCloudService
{
    public function graphVersion(): string
    {
        return trim((string) TnfSetting::get('whatsapp_graph_version', 'v21.0'), '/');
    }

    public function accessToken(): ?string
    {
        $token = trim((string) TnfSetting::get('whatsapp_access_token', ''));

        return $token !== '' ? $token : null;
    }

    public function phoneNumberId(): ?string
    {
        $id = trim((string) TnfSetting::get('whatsapp_phone_number_id', ''));

        return $id !== '' ? $id : null;
    }

    public function businessAccountId(): ?string
    {
        $id = trim((string) TnfSetting::get('whatsapp_business_account_id', ''));

        return $id !== '' ? $id : null;
    }

    public function appSecret(): ?string
    {
        $secret = trim((string) TnfSetting::get('whatsapp_app_secret', ''));

        return $secret !== '' ? $secret : null;
    }

    public function verifyToken(): ?string
    {
        $token = trim((string) TnfSetting::get('whatsapp_webhook_verify_token', ''));

        return $token !== '' ? $token : null;
    }

    public function isConfigured(): bool
    {
        return filled($this->accessToken()) && filled($this->phoneNumberId());
    }

    public function isEnabled(): bool
    {
        return TnfSetting::bool('whatsapp_enabled', false) && $this->isConfigured();
    }

    /**
     * @return array{
     *   connected: bool,
     *   configured: bool,
     *   enabled: bool,
     *   display_phone_number: ?string,
     *   verified_name: ?string,
     *   quality_rating: ?string,
     *   business_account_id: ?string,
     *   business_name: ?string,
     *   error: ?string
     * }
     */
    public function connectionStatus(): array
    {
        $base = [
            'connected' => false,
            'configured' => $this->isConfigured(),
            'enabled' => TnfSetting::bool('whatsapp_enabled', false),
            'display_phone_number' => null,
            'verified_name' => null,
            'quality_rating' => null,
            'business_account_id' => $this->businessAccountId(),
            'business_name' => null,
            'error' => null,
        ];

        if (! $this->isConfigured()) {
            $base['error'] = 'Add Access Token and Phone Number ID, then save.';

            return $base;
        }

        $phoneId = $this->phoneNumberId();
        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout(20)
            ->get($this->graphUrl($phoneId), [
                'fields' => 'display_phone_number,verified_name,quality_rating,code_verification_status',
            ]);

        if (! $response->successful()) {
            $base['error'] = $this->extractError($response->json(), $response->body());

            return $base;
        }

        $data = $response->json();
        $base['connected'] = true;
        $base['display_phone_number'] = $data['display_phone_number'] ?? null;
        $base['verified_name'] = $data['verified_name'] ?? null;
        $base['quality_rating'] = $data['quality_rating'] ?? null;

        $wabaId = $this->businessAccountId();
        if ($wabaId) {
            $waba = Http::withToken($this->accessToken())
                ->acceptJson()
                ->timeout(20)
                ->get($this->graphUrl($wabaId), [
                    'fields' => 'id,name,account_review_status',
                ]);

            if ($waba->successful()) {
                $base['business_name'] = $waba->json('name');
            }
        }

        Setting::set('whatsapp_connected', true);
        Setting::set('whatsapp_display_phone', $base['display_phone_number']);
        Setting::set('whatsapp_verified_name', $base['verified_name']);
        Setting::set('whatsapp_quality_rating', $base['quality_rating']);
        Setting::set('whatsapp_last_checked_at', now()->toIso8601String());

        return $base;
    }

    /**
     * Fetch message templates from Meta for the configured WABA.
     *
     * @return array{
     *   ok: bool,
     *   templates: list<array{
     *     id: ?string,
     *     name: string,
     *     language: string,
     *     status: string,
     *     category: ?string,
     *     body: ?string
     *   }>,
     *   error: ?string
     * }
     */
    public function listMessageTemplates(): array
    {
        if (! filled($this->accessToken())) {
            return [
                'ok' => false,
                'templates' => [],
                'error' => 'Access token is missing. Save it first.',
            ];
        }

        $wabaId = $this->businessAccountId();
        if (! filled($wabaId)) {
            return [
                'ok' => false,
                'templates' => [],
                'error' => 'WhatsApp Business Account ID (WABA) is required to list templates.',
            ];
        }

        $templates = [];
        $url = $this->graphUrl($wabaId.'/message_templates');
        $params = [
            'limit' => 100,
            'fields' => 'id,name,language,status,category,components',
        ];

        do {
            $response = Http::withToken($this->accessToken())
                ->acceptJson()
                ->timeout(30)
                ->get($url, $params);

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'templates' => [],
                    'error' => $this->extractError($response->json(), $response->body()),
                ];
            }

            $json = $response->json();

            foreach ($json['data'] ?? [] as $row) {
                $templates[] = [
                    'id' => $row['id'] ?? null,
                    'name' => (string) ($row['name'] ?? ''),
                    'language' => (string) ($row['language'] ?? ''),
                    'status' => strtoupper((string) ($row['status'] ?? 'UNKNOWN')),
                    'category' => $row['category'] ?? null,
                    'body' => $this->extractTemplateBody($row['components'] ?? []),
                ];
            }

            $next = $json['paging']['next'] ?? null;
            $url = is_string($next) ? $next : null;
            $params = []; // next URL already has query string
        } while (filled($url));

        usort($templates, function (array $a, array $b): int {
            $statusOrder = ['APPROVED' => 0, 'PENDING' => 1, 'REJECTED' => 2];
            $aRank = $statusOrder[$a['status']] ?? 9;
            $bRank = $statusOrder[$b['status']] ?? 9;

            return [$aRank, $a['name'], $a['language']] <=> [$bRank, $b['name'], $b['language']];
        });

        return [
            'ok' => true,
            'templates' => $templates,
            'error' => null,
        ];
    }

    /**
     * @return array{
     *   ok: bool,
     *   count: int,
     *   approved: int,
     *   error: ?string
     * }
     */
    public function syncMessageTemplates(): array
    {
        $result = $this->listMessageTemplates();

        if (! $result['ok']) {
            return [
                'ok' => false,
                'count' => 0,
                'approved' => 0,
                'error' => $result['error'],
            ];
        }

        Setting::set('whatsapp_templates_cache', $result['templates']);
        Setting::set('whatsapp_templates_synced_at', now()->toIso8601String());

        $approved = collect($result['templates'])
            ->where('status', 'APPROVED')
            ->count();

        return [
            'ok' => true,
            'count' => count($result['templates']),
            'approved' => $approved,
            'error' => null,
        ];
    }

    /**
     * @return list<array{
     *   id: ?string,
     *   name: string,
     *   language: string,
     *   status: string,
     *   category: ?string,
     *   body: ?string
     * }>
     */
    public function cachedMessageTemplates(): array
    {
        $cached = Setting::get('whatsapp_templates_cache', []);

        return is_array($cached) ? $cached : [];
    }

    /**
     * @return array<string, string> value => label (approved only)
     */
    public function approvedTemplatePickOptions(): array
    {
        $options = [];

        $fromDb = \App\Models\WhatsappTemplate::query()
            ->where('status', 'APPROVED')
            ->orderBy('name')
            ->get(['name', 'language', 'category']);

        foreach ($fromDb as $template) {
            $name = (string) $template->name;
            $lang = (string) $template->language;
            if ($name === '' || $lang === '') {
                continue;
            }

            $key = $name.'||'.$lang;
            $category = $template->category ?: '—';
            $options[$key] = "{$name} · {$lang} · {$category}";
        }

        if ($options !== []) {
            return $options;
        }

        foreach ($this->cachedMessageTemplates() as $template) {
            if (($template['status'] ?? '') !== 'APPROVED') {
                continue;
            }

            $name = (string) ($template['name'] ?? '');
            $lang = (string) ($template['language'] ?? '');
            if ($name === '' || $lang === '') {
                continue;
            }

            $key = $name.'||'.$lang;
            $category = $template['category'] ?? '—';
            $options[$key] = "{$name} · {$lang} · {$category}";
        }

        return $options;
    }

    /**
     * @return array<string, string> name => label (approved only, one per name)
     */
    public function approvedTemplateNameOptions(): array
    {
        $options = [];

        foreach ($this->approvedTemplatePickOptions() as $key => $label) {
            $name = explode('||', (string) $key, 2)[0] ?? '';
            if ($name === '' || isset($options[$name])) {
                continue;
            }
            $options[$name] = $label;
        }

        return $options;
    }

    /**
     * @param  list<array<string, mixed>>  $components
     */
    protected function extractTemplateBody(array $components): ?string
    {
        foreach ($components as $component) {
            if (strtoupper((string) ($component['type'] ?? '')) !== 'BODY') {
                continue;
            }

            $text = $component['text'] ?? null;

            return filled($text) ? (string) $text : null;
        }

        return null;
    }

    public function sendAuthenticationOtp(string $phone, string $code): bool
    {
        $phone = PhoneNumber::normalize($phone);
        if ($phone === null || ! $this->isEnabled()) {
            return false;
        }

        $template = trim((string) TnfSetting::get('whatsapp_otp_template', 'tnf_login_otp'));
        $language = trim((string) TnfSetting::get('whatsapp_otp_template_lang', 'en'));

        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $code],
                ],
            ],
            [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [
                    ['type' => 'text', 'text' => $code],
                ],
            ],
        ];

        return $this->sendTemplate($phone, $template, $language, $components, 'otp', "Your TNF Today login code is {$code}");
    }

    public function sendContentAlert(string $phone, string $title, string $url, string $kind = 'news'): bool
    {
        $phone = PhoneNumber::normalize($phone);
        if ($phone === null || ! $this->isEnabled()) {
            return false;
        }

        $templateKey = $kind === 'epaper' ? 'whatsapp_epaper_template' : 'whatsapp_news_template';
        $langKey = $kind === 'epaper' ? 'whatsapp_epaper_template_lang' : 'whatsapp_news_template_lang';
        $defaultName = $kind === 'epaper' ? 'tnf_epaper_alert' : 'tnf_news_alert';

        $template = trim((string) TnfSetting::get($templateKey, $defaultName));
        $language = trim((string) TnfSetting::get($langKey, 'en'));
        $absoluteUrl = FrontendUrl::to($url);
        $shortTitle = Str::limit(trim($title), 60, '…');

        return $this->sendTemplateBodyParams(
            phone: $phone,
            template: $template,
            language: $language,
            bodyParams: [$shortTitle, $absoluteUrl],
            bodyPreview: $shortTitle."\n".$absoluteUrl,
            type: $kind,
        );
    }

    /**
     * @param  list<string>  $bodyParams
     */
    public function sendTemplateBodyParams(
        string $phone,
        string $template,
        string $language,
        array $bodyParams,
        ?string $bodyPreview = null,
        string $type = 'template',
    ): bool {
        $parameters = [];
        foreach ($bodyParams as $text) {
            $parameters[] = ['type' => 'text', 'text' => (string) $text];
        }

        $components = $parameters === []
            ? []
            : [['type' => 'body', 'parameters' => $parameters]];

        return $this->sendTemplate($phone, $template, $language, $components, $type, $bodyPreview);
    }

    /**
     * @param  list<array<string, mixed>>  $components
     */
    public function sendTemplate(
        string $phone,
        string $template,
        string $language,
        array $components,
        string $type = 'template',
        ?string $bodyPreview = null,
    ): bool {
        $phone = PhoneNumber::normalize($phone);
        if ($phone === null || ! $this->isConfigured()) {
            return false;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => $language],
                'components' => $components,
            ],
        ];

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout(30)
            ->post($this->graphUrl($this->phoneNumberId().'/messages'), $payload);

        return $this->persistOutbound(
            phone: $phone,
            type: $type,
            body: $bodyPreview,
            payload: $payload,
            template: $template,
            response: $response->json(),
            ok: $response->successful(),
            errorBody: $response->successful() ? null : $response->body(),
        );
    }

    public function sendText(string $phone, string $text): bool
    {
        $phone = PhoneNumber::normalize($phone);
        if ($phone === null || ! $this->isConfigured()) {
            return false;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => [
                'preview_url' => true,
                'body' => $text,
            ],
        ];

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout(30)
            ->post($this->graphUrl($this->phoneNumberId().'/messages'), $payload);

        return $this->persistOutbound(
            phone: $phone,
            type: 'text',
            body: $text,
            payload: $payload,
            template: null,
            response: $response->json(),
            ok: $response->successful(),
            errorBody: $response->successful() ? null : $response->body(),
        );
    }

    public function verifyWebhookChallenge(?string $mode, ?string $token, ?string $challenge): ?string
    {
        if ($mode !== 'subscribe') {
            return null;
        }

        $expected = $this->verifyToken();
        if (! filled($expected) || ! hash_equals($expected, (string) $token)) {
            return null;
        }

        return (string) $challenge;
    }

    public function signatureIsValid(?string $signatureHeader, string $rawBody): bool
    {
        $secret = $this->appSecret();
        if (! filled($secret)) {
            // If no app secret configured, accept (local/dev). Production should set it.
            return ! app()->isProduction();
        }

        if (! filled($signatureHeader) || ! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signatureHeader);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhookPayload(array $payload): void
    {
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                $this->ingestInboundMessages($value);
                $this->ingestStatuses($value);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $value
     */
    protected function ingestInboundMessages(array $value): void
    {
        foreach ($value['messages'] ?? [] as $message) {
            if (! is_array($message)) {
                continue;
            }

            $wamid = $message['id'] ?? null;
            if (filled($wamid) && WhatsappMessage::query()->where('wamid', $wamid)->exists()) {
                continue;
            }

            $phone = PhoneNumber::normalize($message['from'] ?? null);
            if ($phone === null) {
                continue;
            }

            $parsed = \App\Support\WhatsAppInboundParser::parse($message);
            $body = $parsed['body'];
            $lower = Str::lower(trim((string) $body));

            if (in_array($lower, ['stop', 'unsubscribe', 'cancel'], true)) {
                User::query()->where('phone', $phone)->update([
                    'whatsapp_opt_in' => false,
                ]);
            }

            if (in_array($lower, ['start', 'subscribe'], true)) {
                User::query()->where('phone', $phone)->update([
                    'whatsapp_opt_in' => true,
                    'whatsapp_opt_in_at' => now(),
                ]);
            }

            $record = WhatsappMessage::query()->create([
                'wamid' => $wamid,
                'direction' => 'inbound',
                'phone' => $phone,
                'user_id' => User::query()->where('phone', $phone)->value('id'),
                'type' => $parsed['type'],
                'media_id' => $parsed['media_id'],
                'media_mime_type' => $parsed['media_mime_type'],
                'media_filename' => $parsed['media_filename'],
                'caption' => $parsed['caption'],
                'body' => $body,
                'payload' => $message,
                'status' => 'received',
                'provider_timestamp' => isset($message['timestamp'])
                    ? now()->setTimestamp((int) $message['timestamp'])
                    : now(),
            ]);

            if (\App\Support\WhatsAppInboundParser::isMediaType($parsed['type']) && filled($parsed['media_id'])) {
                \App\Jobs\DownloadWhatsAppMediaJob::dispatch($record->id);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $value
     */
    protected function ingestStatuses(array $value): void
    {
        foreach ($value['statuses'] ?? [] as $status) {
            $wamid = $status['id'] ?? null;
            if (! filled($wamid)) {
                continue;
            }

            $message = WhatsappMessage::query()->where('wamid', $wamid)->first();
            if (! $message) {
                continue;
            }

            $message->status = $status['status'] ?? $message->status;
            if (! empty($status['errors'][0]['message'])) {
                $message->error_message = (string) $status['errors'][0]['message'];
            }
            $message->save();
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>|null  $response
     */
    protected function persistOutbound(
        string $phone,
        string $type,
        ?string $body,
        array $payload,
        ?string $template,
        ?array $response,
        bool $ok,
        ?string $errorBody,
    ): bool {
        $wamid = $response['messages'][0]['id'] ?? null;

        WhatsappMessage::query()->create([
            'wamid' => $wamid,
            'direction' => 'outbound',
            'phone' => $phone,
            'user_id' => User::query()->where('phone', $phone)->value('id'),
            'type' => $type,
            'body' => $body,
            'payload' => [
                'request' => $payload,
                'response' => $response,
            ],
            'status' => $ok ? 'accepted' : 'failed',
            'template_name' => $template,
            'error_message' => $ok ? null : $this->extractError($response, $errorBody),
            'read_at' => now(),
            'provider_timestamp' => now(),
        ]);

        if (! $ok) {
            Log::warning('WhatsApp send failed', [
                'phone' => $phone,
                'template' => $template,
                'error' => $this->extractError($response, $errorBody),
            ]);
        }

        return $ok;
    }

    public function publicGraphUrl(string $path): string
    {
        return $this->graphUrl($path);
    }

    protected function graphUrl(string $path): string
    {
        return 'https://graph.facebook.com/'.$this->graphVersion().'/'.ltrim($path, '/');
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    protected function extractError(?array $json, ?string $fallback = null): string
    {
        $message = $json['error']['message'] ?? null;

        if (filled($message)) {
            return (string) $message;
        }

        return Str::limit((string) ($fallback ?: 'Unknown WhatsApp API error'), 240);
    }
}
