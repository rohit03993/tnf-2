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

        if ($bodyText === '') {
            throw new InvalidArgumentException('Message body is required.');
        }

        $components = [];

        if (filled($headerText)) {
            $components[] = [
                'type' => 'HEADER',
                'format' => 'TEXT',
                'text' => trim($headerText),
            ];
        }

        $indices = self::placeholderOrder($bodyText);
        $body = ['type' => 'BODY', 'text' => $bodyText];

        if ($indices !== []) {
            $examples = array_values(array_filter(array_map(
                static fn (string $part): string => trim($part),
                explode(',', (string) $bodyExamplesCsv),
            )));

            while (count($examples) < count($indices)) {
                $examples[] = 'sample'.(count($examples) + 1);
            }

            $body['example'] = [
                'body_text' => [array_slice($examples, 0, count($indices))],
            ];
        }

        $components[] = $body;

        if (filled($footerText)) {
            $components[] = [
                'type' => 'FOOTER',
                'text' => trim($footerText),
            ];
        }

        return [
            'name' => $name,
            'language' => $language,
            'category' => $category,
            'components' => $components,
            'allow_category_change' => true,
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
