<?php

namespace App\Services\WordPress;

use App\Models\Category;
use RuntimeException;
use XMLReader;

class WxrParser
{
    private const WP_NS = 'http://wordpress.org/export/1.2/';

    private const CONTENT_NS = 'http://purl.org/rss/1.0/modules/content/';

    private const EXCERPT_NS = 'http://wordpress.org/export/1.2/excerpt/';

    private const DC_NS = 'http://purl.org/dc/elements/1.1/';

    /** @return list<array{slug: string, name: string}> */
    public function parseChannelCategories(string $path): array
    {
        $reader = $this->open($path);
        $categories = [];

        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'category') {
                    continue;
                }

                if ($reader->namespaceURI !== self::WP_NS) {
                    continue;
                }

                $node = simplexml_load_string($reader->readOuterXML(), 'SimpleXMLElement', LIBXML_NOCDATA);

                if ($node === false) {
                    continue;
                }

                $slug = trim((string) ($node->category_nicename ?? ''));
                $name = trim((string) ($node->cat_name ?? ''));

                if ($slug === '') {
                    continue;
                }

                $categories[] = [
                    'slug' => $slug,
                    'name' => $name !== '' ? $name : $slug,
                ];
            }
        } finally {
            $reader->close();
        }

        return $categories;
    }

    /** @return \Generator<int, array<string, mixed>> */
    public function items(string $path): \Generator
    {
        $reader = $this->open($path);

        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'item') {
                    continue;
                }

                $item = simplexml_load_string($reader->readOuterXML(), 'SimpleXMLElement', LIBXML_NOCDATA);

                if ($item === false) {
                    continue;
                }

                $parsed = $this->parseItem($item);

                if ($parsed !== null) {
                    yield $parsed;
                }
            }
        } finally {
            $reader->close();
        }
    }

    protected function open(string $path): XMLReader
    {
        if (! is_file($path)) {
            throw new RuntimeException("Export file not found: {$path}");
        }

        $reader = new XMLReader();

        if (! $reader->open($path)) {
            throw new RuntimeException('Could not open WordPress XML export.');
        }

        return $reader;
    }

    /** @return array<string, mixed>|null */
    protected function parseItem(\SimpleXMLElement $item): ?array
    {
        $wp = $item->children(self::WP_NS);
        $content = $item->children(self::CONTENT_NS);
        $excerpt = $item->children(self::EXCERPT_NS);
        $dc = $item->children(self::DC_NS);

        $postType = (string) ($wp->post_type ?? '');
        $status = (string) ($wp->status ?? '');

        if (! in_array($postType, NewsWxrImporter::POST_TYPES, true)) {
            return null;
        }

        if (in_array($status, ['trash', 'auto-draft', 'inherit'], true)) {
            return null;
        }

        $body = $this->decode((string) ($content->encoded ?? ''));

        if ($status !== 'publish' && $status !== 'draft' && $status !== 'pending') {
            return null;
        }

        if ($body === '' && $status === 'publish') {
            return null;
        }

        $categories = [];
        $tags = [];

        foreach ($item->category ?? [] as $category) {
            $domain = (string) ($category['domain'] ?? '');
            $nicename = (string) ($category['nicename'] ?? '');
            $label = trim((string) $category);

            if ($domain === 'category' && $nicename !== '') {
                $categories[] = [
                    'slug' => $nicename,
                    'name' => $label !== '' ? $label : $nicename,
                ];
            }

            if ($domain === 'post_tag' && $nicename !== '') {
                $tags[] = [
                    'slug' => $nicename,
                    'name' => $label !== '' ? $label : $nicename,
                ];
            }
        }

        return [
            'wp_id' => (int) ($wp->post_id ?? 0),
            'title' => $this->decode((string) ($item->title ?? '')),
            'slug' => (string) ($wp->post_name ?? ''),
            'content' => $body,
            'excerpt' => $this->decode((string) ($excerpt->encoded ?? '')),
            'status' => $status,
            'post_type' => $postType,
            'author_name' => $this->decode((string) ($dc->creator ?? '')),
            'published_at' => $this->normalizeDate((string) ($wp->post_date ?? '')),
            'comment_count' => (int) ($wp->comment_count ?? 0),
            'embed_url' => $this->extractEmbedUrl($wp),
            'categories' => $categories,
            'tags' => $tags,
        ];
    }

    protected function decode(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected function normalizeDate(string $value): ?string
    {
        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        return $value;
    }

    protected function extractEmbedUrl(\SimpleXMLElement $wp): ?string
    {
        $keys = ['embed_url', 'tnf_embed_url', 'video_url', 'youtube_url', 'tnf_video_url'];

        foreach ($wp->postmeta ?? [] as $meta) {
            $key = (string) ($meta->meta_key ?? '');
            $value = trim((string) ($meta->meta_value ?? ''));

            if ($value !== '' && in_array($key, $keys, true)) {
                return $value;
            }
        }

        return null;
    }
}
