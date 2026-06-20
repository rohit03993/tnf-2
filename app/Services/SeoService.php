<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\Video;
use App\Support\SeoMeta;
use Illuminate\Support\Str;

class SeoService
{
    public function forHome(): SeoMeta
    {
        return new SeoMeta(
            title: config('app.name', 'TNF Today'),
            description: 'Latest Hindi news, videos, and digital ePaper from TNF Today.',
            url: route('home'),
        );
    }

    public function forArticle(Article $article): SeoMeta
    {
        $description = $this->excerpt($article->excerpt, $article->content);

        return new SeoMeta(
            title: $article->title,
            description: $description,
            image: route('og.article', $article),
            url: route('article.show', $article->slug),
            type: 'article',
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
                'mainEntityOfPage' => route('article.show', $article->slug),
                'image' => route('og.article', $article),
            ],
        );
    }

    public function forVideo(Video $video): SeoMeta
    {
        $description = $this->excerpt($video->excerpt, $video->content);

        return new SeoMeta(
            title: $video->title,
            description: $description,
            image: route('og.video', $video),
            url: route('videos.show', $video->slug),
            type: 'video.other',
            jsonLd: [
                '@context' => 'https://schema.org',
                '@type' => 'VideoObject',
                'name' => $video->title,
                'description' => $description,
                'uploadDate' => $video->published_at?->toIso8601String(),
                'thumbnailUrl' => route('og.video', $video),
                'url' => route('videos.show', $video->slug),
            ],
        );
    }

    public function forCategory(Category $category): SeoMeta
    {
        return new SeoMeta(
            title: $category->name,
            description: $category->description ?: 'Latest '.$category->name.' news on TNF Today.',
            url: route('category.show', $category->slug),
        );
    }

    public function forPage(Page $page): SeoMeta
    {
        return new SeoMeta(
            title: $page->title,
            description: Str::limit(strip_tags($page->content ?? ''), 160),
            url: match ($page->slug) {
                'about-us' => route('page.about'),
                'contact-us' => route('page.contact'),
                'privacy-policy' => route('page.privacy'),
                'terms-of-use' => route('page.terms'),
                default => url('/'.$page->slug),
            },
        );
    }

    public function forSearch(?string $query = null): SeoMeta
    {
        return new SeoMeta(
            title: $query ? 'Search: '.$query : 'Search',
            description: 'Search news and videos on TNF Today.',
            url: route('search', $query ? ['q' => $query] : []),
            noindex: true,
        );
    }

    protected function excerpt(?string $excerpt, ?string $content): string
    {
        $text = filled($excerpt)
            ? strip_tags($excerpt)
            : strip_tags((string) $content);

        return Str::limit(trim($text), 160);
    }
}
