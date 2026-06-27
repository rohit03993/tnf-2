<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\EpaperEdition;
use App\Models\Page;
use App\Models\Video;
use App\Services\EpaperClipSignatureService;
use App\Support\FrontendUrl;
use App\Support\SeoMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SeoService
{
    public const OG_IMAGE_WIDTH = 1200;

    public const OG_IMAGE_HEIGHT = 630;

    public function forHome(): SeoMeta
    {
        return new SeoMeta(
            title: config('app.name', 'TNF Today'),
            description: 'Latest Hindi news, videos, and digital ePaper from TNF Today.',
            image: $this->defaultShareImage(),
            url: FrontendUrl::route('home'),
            imageWidth: self::OG_IMAGE_WIDTH,
            imageHeight: self::OG_IMAGE_HEIGHT,
            imageAlt: config('app.name', 'TNF Today'),
        );
    }

    public function forArticle(Article $article): SeoMeta
    {
        $description = $this->excerpt($article->excerpt, $article->content);
        $image = $this->resolveArticleShareImage($article);

        return new SeoMeta(
            title: $article->title,
            description: $description,
            image: $image,
            url: FrontendUrl::route('article.show', $article->slug),
            type: 'article',
            imageWidth: self::OG_IMAGE_WIDTH,
            imageHeight: self::OG_IMAGE_HEIGHT,
            imageAlt: $article->title,
            jsonLd: [
                '@context' => 'https://schema.org',
                '@type' => 'NewsArticle',
                'headline' => $article->title,
                'description' => $description,
                'datePublished' => $article->published_at?->toIso8601String(),
                'dateModified' => $article->updated_at?->toIso8601String(),
                'author' => [
                    '@type' => 'Person',
                    'name' => $article->author?->name ?? config('app.name', 'TNF Today'),
                ],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => config('app.name', 'TNF Today'),
                ],
                'mainEntityOfPage' => FrontendUrl::route('article.show', $article->slug),
                'image' => $image,
            ],
        );
    }

    public function forVideo(Video $video): SeoMeta
    {
        $description = $this->excerpt($video->excerpt, $video->content);
        $image = $this->resolveVideoShareImage($video);

        return new SeoMeta(
            title: $video->title,
            description: $description,
            image: $image,
            url: FrontendUrl::route('videos.show', $video->slug),
            type: 'video.other',
            imageWidth: self::OG_IMAGE_WIDTH,
            imageHeight: self::OG_IMAGE_HEIGHT,
            imageAlt: $video->title,
            jsonLd: [
                '@context' => 'https://schema.org',
                '@type' => 'VideoObject',
                'name' => $video->title,
                'description' => $description,
                'uploadDate' => $video->published_at?->toIso8601String(),
                'thumbnailUrl' => $image,
                'url' => FrontendUrl::route('videos.show', $video->slug),
            ],
        );
    }

    public function forCategory(Category $category): SeoMeta
    {
        return new SeoMeta(
            title: $category->name,
            description: $category->description ?: 'Latest '.$category->name.' news on TNF Today.',
            image: $this->defaultShareImage(),
            url: FrontendUrl::route('category.show', $category->slug),
            imageWidth: self::OG_IMAGE_WIDTH,
            imageHeight: self::OG_IMAGE_HEIGHT,
            imageAlt: $category->name,
        );
    }

    public function forPage(Page $page): SeoMeta
    {
        return new SeoMeta(
            title: $page->title,
            description: Str::limit(strip_tags($page->content ?? ''), 160),
            image: $this->defaultShareImage(),
            url: match ($page->slug) {
                'about-us' => FrontendUrl::route('page.about'),
                'contact-us' => FrontendUrl::route('page.contact'),
                'privacy-policy' => FrontendUrl::route('page.privacy'),
                'terms-of-use' => FrontendUrl::route('page.terms'),
                default => FrontendUrl::to('/'.$page->slug),
            },
            imageWidth: self::OG_IMAGE_WIDTH,
            imageHeight: self::OG_IMAGE_HEIGHT,
            imageAlt: $page->title,
        );
    }

    public function forSearch(?string $query = null): SeoMeta
    {
        return new SeoMeta(
            title: $query ? 'Search: '.$query : 'Search',
            description: 'Search news and videos on TNF Today.',
            url: FrontendUrl::route('search', $query ? ['q' => $query] : []),
            noindex: true,
        );
    }

    public function forEpaperIndex(): SeoMeta
    {
        return new SeoMeta(
            title: 'ePaper',
            description: 'Browse digital newspaper editions from TNF Today.',
            image: $this->defaultShareImage(),
            url: FrontendUrl::route('epaper.index'),
            imageWidth: self::OG_IMAGE_WIDTH,
            imageHeight: self::OG_IMAGE_HEIGHT,
            imageAlt: 'TNF Today ePaper',
        );
    }

    public function forEpaper(EpaperEdition $edition, ?Request $request = null): SeoMeta
    {
        $edition->loadMissing('featuredMedia');
        $description = $this->excerpt($edition->excerpt, $edition->content)
            ?: 'Read the digital newspaper edition on TNF Today.';
        $isClip = $request?->boolean('tnf_clip')
            && EpaperClipSignatureService::hasValidClipParams($request);

        if ($isClip) {
            return new SeoMeta(
                title: 'Newspaper clip — '.$edition->title,
                description: $description,
                image: $this->resolveEpaperClipShareImage($edition, $request),
                url: FrontendUrl::to($request->fullUrl()),
                type: 'article',
                imageWidth: self::OG_IMAGE_WIDTH,
                imageHeight: self::OG_IMAGE_HEIGHT,
                imageAlt: 'Newspaper clip from '.$edition->title,
            );
        }

        return new SeoMeta(
            title: $edition->title,
            description: $description,
            image: $this->resolveEpaperShareImage($edition),
            url: FrontendUrl::route('epaper.show', $edition->slug),
            type: 'article',
            imageWidth: self::OG_IMAGE_WIDTH,
            imageHeight: self::OG_IMAGE_HEIGHT,
            imageAlt: $edition->title,
            jsonLd: [
                '@context' => 'https://schema.org',
                '@type' => 'NewsArticle',
                'headline' => $edition->title,
                'description' => $description,
                'datePublished' => $edition->published_at?->toIso8601String(),
                'dateModified' => $edition->updated_at?->toIso8601String(),
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => config('app.name', 'TNF Today'),
                ],
                'mainEntityOfPage' => FrontendUrl::route('epaper.show', $edition->slug),
                'image' => $this->resolveEpaperShareImage($edition),
            ],
        );
    }

    protected function resolveEpaperShareImage(EpaperEdition $edition): string
    {
        return route('og.epaper.page', $edition, absolute: true);
    }

    protected function resolveEpaperClipShareImage(EpaperEdition $edition, Request $request): string
    {
        return route('og.epaper.clip', [
            'edition' => $edition->id,
            'tnf_pg' => (int) $request->query('tnf_pg', 1),
            'tnf_cx' => $request->query('tnf_cx'),
            'tnf_cy' => $request->query('tnf_cy'),
            'tnf_cw' => $request->query('tnf_cw'),
            'tnf_ch' => $request->query('tnf_ch'),
        ], absolute: true);
    }

    public function defaultShareImage(): string
    {
        return route('og.default', absolute: true);
    }

    protected function resolveArticleShareImage(Article $article): string
    {
        if ($url = $article->featuredMedia?->absoluteUrl()) {
            return $url;
        }

        if ($url = $this->firstImageFromHtml($article->content)) {
            return $url;
        }

        return $this->defaultShareImage();
    }

    protected function resolveVideoShareImage(Video $video): string
    {
        if ($url = $video->featuredMedia?->absoluteUrl()) {
            return $url;
        }

        $thumbnail = $video->thumbnailUrl();

        if ($thumbnail) {
            return str_starts_with($thumbnail, 'http')
                ? $thumbnail
                : FrontendUrl::to($thumbnail);
        }

        if ($url = $this->firstImageFromHtml($video->content)) {
            return $url;
        }

        return $this->defaultShareImage();
    }

    protected function firstImageFromHtml(?string $html): ?string
    {
        if (! filled($html) || ! preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches)) {
            return null;
        }

        $src = html_entity_decode(trim($matches[1]));

        if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) {
            return $src;
        }

        return FrontendUrl::to($src);
    }

    protected function excerpt(?string $excerpt, ?string $content): string
    {
        $text = filled($excerpt)
            ? strip_tags($excerpt)
            : strip_tags((string) $content);

        return Str::limit(trim($text), 160);
    }
}
