<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class SubmissionContent
{
    /** @var array<string, array<int, string>> */
    private const ALLOWED = [
        'p' => [],
        'br' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'u' => [],
        's' => [],
        'del' => [],
        'sub' => [],
        'sup' => [],
        'h2' => [],
        'h3' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'blockquote' => [],
        'pre' => [],
        'code' => [],
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt'],
        'table' => [],
        'thead' => [],
        'tbody' => [],
        'tr' => [],
        'th' => [],
        'td' => [],
    ];

    public static function sanitize(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        if (! str_contains($html, '<')) {
            return self::plainToHtml($html);
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8"><div id="tnf-wrap">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $wrapper = $document->getElementById('tnf-wrap');

        if (! $wrapper) {
            return self::plainToHtml(strip_tags($html));
        }

        self::sanitizeNode($wrapper);

        $clean = '';

        foreach ($wrapper->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        return trim($clean);
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        if (! $node->hasChildNodes()) {
            return;
        }

        $children = [];

        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            /** @var DOMElement $child */
            $tag = strtolower($child->nodeName);

            if (! array_key_exists($tag, self::ALLOWED)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }

                $node->removeChild($child);

                continue;
            }

            self::sanitizeAttributes($child, $tag);
            self::sanitizeNode($child);
        }
    }

    private static function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowed = self::ALLOWED[$tag];

        if ($allowed === [] && $element->hasAttributes()) {
            while ($element->attributes->length > 0) {
                $element->removeAttribute($element->attributes->item(0)->nodeName);
            }

            return;
        }

        $toRemove = [];

        foreach ($element->attributes as $attribute) {
            $name = strtolower($attribute->nodeName);

            if (! in_array($name, $allowed, true)) {
                $toRemove[] = $name;
            }
        }

        foreach ($toRemove as $name) {
            $element->removeAttribute($name);
        }

        if ($tag === 'a') {
            $href = trim((string) $element->getAttribute('href'));

            if (! self::isSafeUrl($href)) {
                $element->removeAttribute('href');
            } else {
                $element->setAttribute('href', $href);
                $element->setAttribute('rel', 'noopener noreferrer');

                if (strtolower((string) $element->getAttribute('target')) === '_blank') {
                    $element->setAttribute('target', '_blank');
                } else {
                    $element->removeAttribute('target');
                }
            }
        }

        if ($tag === 'img') {
            $src = trim((string) $element->getAttribute('src'));

            if (! self::isSafeImageSrc($src)) {
                $element->parentNode?->removeChild($element);
            } else {
                $element->setAttribute('src', $src);
            }
        }
    }

    private static function isSafeUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return ! str_starts_with(strtolower($url), '//');
        }

        return (bool) preg_match('/^https?:\/\//i', $url);
    }

    private static function isSafeImageSrc(string $src): bool
    {
        if ($src === '') {
            return false;
        }

        if (str_starts_with($src, '/storage/')) {
            return true;
        }

        return (bool) preg_match('/^https?:\/\//i', $src);
    }

    public static function plainToHtml(string $text): string
    {
        $paragraphs = preg_split('/\R\R+/', trim($text)) ?: [];

        return collect($paragraphs)
            ->filter()
            ->map(fn (string $paragraph) => '<p>'.e(trim($paragraph)).'</p>')
            ->join('');
    }

    public static function textLength(string $html): int
    {
        return mb_strlen(trim(strip_tags($html)));
    }
}
