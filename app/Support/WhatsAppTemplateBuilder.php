<?php

namespace App\Support;

use InvalidArgumentException;

class WhatsAppTemplateBuilder
{
    /**
     * @return array<string, mixed>
     */
    public static function buildCreatePayload(
        string $name,
        string $language,
        string $category,
        string $bodyText,
        ?string $headerText = null,
        ?string $footerText = null,
        ?string $bodyExamplesCsv = null,
        bool $allowCategoryChange = true,
        int $codeExpirationMinutes = 10,
    ): array {
        $name = self::normalizeName($name);
        $language = trim($language);
        $category = strtoupper(trim($category));
        $bodyText = trim($bodyText);

        if ($name === '' || strlen($name) < 3) {
            throw new InvalidArgumentException('Template name must be at least 3 characters.');
        }

        if (! preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException('Template name: lowercase letters, numbers, underscores only.');
        }

        if ($category === 'AUTHENTICATION') {
            return self::buildAuthenticationPayload(
                $name,
                $language,
                $codeExpirationMinutes,
                $allowCategoryChange,
            );
        }

        if ($bodyText === '') {
            throw new InvalidArgumentException('Message body is required.');
        }

        if (filled($headerText) && str_contains((string) $headerText, '{{')) {
            throw new InvalidArgumentException('Header cannot include variables. Use plain text only.');
        }

        $components = [];

        if (filled($headerText)) {
            $components[] = [
                'type' => 'HEADER',
                'format' => 'TEXT',
                'text' => trim((string) $headerText),
            ];
        }

        $indices = self::placeholderOrder($bodyText);
        $body = ['type' => 'BODY', 'text' => $bodyText];

        if ($indices !== []) {
            $examples = array_values(array_filter(array_map(
                static fn (string $part): string => trim($part),
                explode(',', (string) $bodyExamplesCsv),
            )));

            if (count($examples) < count($indices)) {
                throw new InvalidArgumentException(
                    'The body has '.count($indices).' variable(s). Fill every sample value below.',
                );
            }

            $body['example'] = [
                'body_text' => [array_slice($examples, 0, count($indices))],
            ];
        }

        $components[] = $body;

        if (filled($footerText)) {
            $components[] = [
                'type' => 'FOOTER',
                'text' => trim((string) $footerText),
            ];
        }

        return [
            'name' => $name,
            'language' => $language,
            'category' => $category,
            'components' => $components,
            'allow_category_change' => $allowCategoryChange,
        ];
    }

    /**
     * Meta AUTHENTICATION OTP templates use a fixed structure (not free-form body text).
     *
     * @return array<string, mixed>
     */
    protected static function buildAuthenticationPayload(
        string $name,
        string $language,
        int $codeExpirationMinutes,
        bool $allowCategoryChange,
    ): array {
        $minutes = max(1, min(90, $codeExpirationMinutes));

        return [
            'name' => $name,
            'language' => $language,
            'category' => 'AUTHENTICATION',
            'message_send_ttl_seconds' => $minutes * 60,
            'components' => [
                [
                    'type' => 'BODY',
                    'add_security_recommendation' => true,
                ],
                [
                    'type' => 'FOOTER',
                    'code_expiration_minutes' => $minutes,
                ],
                [
                    'type' => 'BUTTONS',
                    'buttons' => [
                        [
                            'type' => 'OTP',
                            'otp_type' => 'COPY_CODE',
                        ],
                    ],
                ],
            ],
            'allow_category_change' => $allowCategoryChange,
        ];
    }

    public static function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9_]+/', '_', $name) ?? '';

        return trim($name, '_');
    }

    /** @return list<int> */
    public static function placeholderOrder(string $bodyText): array
    {
        preg_match_all('/\{\{(\d+)\}\}/', $bodyText, $matches);

        $order = [];
        foreach ($matches[1] ?? [] as $index) {
            $n = (int) $index;
            if (! in_array($n, $order, true)) {
                $order[] = $n;
            }
        }

        return $order;
    }
}
