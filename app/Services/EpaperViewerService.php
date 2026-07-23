<?php

namespace App\Services;

use App\Models\EpaperEdition;
use App\Models\OgImage;
use App\Support\FrontendUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EpaperViewerService
{
    /** @return array<string, mixed> */
    public static function config(EpaperEdition $edition, Request $request): array
    {
        $pages = self::normalizePages($edition);
        $pdfUrl = self::pdfUrl($edition);
        $pageCount = count($pages);
        $maxPage = max($pageCount, $pdfUrl ? 1 : 0);
        $clipMode = $request->boolean('tnf_clip');

        return [
            'editionId' => $edition->id,
            'title' => $edition->title,
            'slug' => $edition->slug,
            'logoUrl' => BrandLogoService::url(),
            'pages' => $pages,
            'pageCount' => $pageCount,
            'pdfUrl' => $pdfUrl,
            'initialPage' => $maxPage > 0
                ? max(1, min((int) $request->query('tnf_pg', 1), $maxPage))
                : 1,
            'clipMode' => $clipMode,
            'clip' => $clipMode ? [
                'page' => max(1, (int) $request->query('tnf_pg', 1)),
                'x' => (float) $request->query('tnf_cx', 0),
                'y' => (float) $request->query('tnf_cy', 0),
                'w' => (float) $request->query('tnf_cw', 0),
                'h' => (float) $request->query('tnf_ch', 0),
            ] : null,
            'shareUrl' => FrontendUrl::route('epaper.show', $edition->slug),
            'pdfDownloadUrl' => self::pdfUrl($edition),
            'clipSignUrl' => route('epaper.sign-clip', $edition->slug),
            'readUrl' => route('epaper.read', $edition->slug),
            'likeUrl' => route('epaper.like', $edition->slug),
            'readersCount' => (int) ($edition->readers_count ?? 0),
            'likesCount' => (int) ($edition->likes_count ?? 0),
            'liked' => app(EpaperReadService::class)->readerHasLiked($edition, $request),
        ];
    }

    /** @return list<array{page: int, url: string, width: int|null, height: int|null}> */
    public static function normalizePages(EpaperEdition $edition): array
    {
        $raw = $edition->pages_json;

        if (! $raw) {
            // PDF-only editions: viewer uses PDF.js when page images are not ready yet.
            if ($edition->pdf_path) {
                return [];
            }

            if ($edition->featuredMedia?->url()) {
                return [[
                    'page' => 1,
                    'url' => $edition->featuredMedia->url(),
                    'width' => null,
                    'height' => null,
                ]];
            }

            return [];
        }

        $items = is_array($raw['pages'] ?? null) ? $raw['pages'] : (is_array($raw) ? $raw : []);

        return collect($items)
            ->values()
            ->map(function ($item, int $index) {
                if (is_string($item)) {
                    return [
                        'page' => $index + 1,
                        'url' => $item,
                        'width' => null,
                        'height' => null,
                    ];
                }

                return [
                    'page' => (int) ($item['page'] ?? $index + 1),
                    'url' => (string) ($item['url'] ?? ''),
                    'width' => isset($item['width']) ? (int) $item['width'] : null,
                    'height' => isset($item['height']) ? (int) $item['height'] : null,
                ];
            })
            ->filter(fn (array $page) => filled($page['url']))
            ->values()
            ->all();
    }

    public static function pdfUrl(EpaperEdition $edition): ?string
    {
        if (! $edition->pdf_path) {
            return null;
        }

        if (! Storage::disk('public')->exists($edition->pdf_path)) {
            return null;
        }

        // Relative URL avoids APP_URL host mismatches (localhost vs 127.0.0.1).
        return '/storage/'.ltrim($edition->pdf_path, '/');
    }

    public static function coverImageUrl(EpaperEdition $edition): ?string
    {
        if ($edition->featuredMedia?->url()) {
            return $edition->featuredMedia->url();
        }

        $pages = self::normalizePages($edition);

        if (! empty($pages[0]['url'])) {
            return $pages[0]['url'];
        }

        $og = OgImage::query()
            ->where('entity_type', 'epaper')
            ->where('entity_id', $edition->id)
            ->first();

        if ($og && Storage::disk('public')->exists($og->path)) {
            return '/storage/'.ltrim($og->path, '/');
        }

        return null;
    }

    /**
     * Best source URL for social preview generation (page 1 image or PDF cover).
     */
    public static function shareImageSourceUrl(EpaperEdition $edition, int $pageIndex = 0): ?string
    {
        $edition->loadMissing('featuredMedia');
        $pages = self::normalizePages($edition);
        $url = $pages[$pageIndex]['url'] ?? null;

        if (! $url && $pageIndex === 0) {
            $url = $edition->featuredMedia?->url();
        }

        if (! $url && $pageIndex === 0 && $edition->pdf_path) {
            app(EpaperCoverService::class)->ensureCover($edition->fresh());
            $edition->refresh()->load('featuredMedia');
            $pages = self::normalizePages($edition);
            $url = $pages[0]['url'] ?? $edition->featuredMedia?->url();
        }

        if (! $url && $edition->pdf_path) {
            $url = app(EpaperCoverService::class)->ensurePageImage($edition, $pageIndex + 1);
        }

        return filled($url) ? $url : null;
    }
}
