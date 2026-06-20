<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\EpaperEdition;
use App\Models\Setting;
use App\Models\Video;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HomepageService
{
    /** @var array<int, string> */
    protected array $categoryRailOrder = [
        'national',
        'health',
        'religion',
        'politics',
        'sports',
        'business',
        'entertainment',
        'tech',
        'exclusive',
        'lifestyle',
        'cultural',
        'crime',
    ];

    public function data(): array
    {
        $ttl = (int) config('tnf.homepage_cache_ttl', 300);

        return Cache::remember('homepage.data', $ttl, fn () => $this->build());
    }

    public function clearCache(): void
    {
        Cache::forget('homepage.data');
        PageCacheService::bump();
    }

    protected function build(): array
    {
        $settings = $this->settings();

        $publishedArticles = Article::query()
            ->published()
            ->with(['featuredMedia', 'categories'])
            ->latest('published_at');

        $heroSlots = max(1, (int) $settings['top_stories_count']);

        $allForHero = (clone $publishedArticles)
            ->limit($heroSlots)
            ->get();

        $heroLead = $allForHero->first();
        $heroHeadlines = $allForHero->slice(1);

        $excludeIds = $allForHero->pluck('id');

        $recentNews = (clone $publishedArticles)
            ->when($excludeIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->limit($settings['recent_news_count'])
            ->get();

        $sidebarTopNews = (clone $publishedArticles)
            ->limit($settings['top_stories_count'])
            ->get();

        $trendingNews = $settings['show_trending']
            ? Article::query()
                ->published()
                ->with('featuredMedia')
                ->orderByDesc('comment_count')
                ->limit($settings['trending_count'])
                ->get()
            : collect();

        $featuredVideoLimit = min(max((int) $settings['featured_videos_count'], 1), 10);

        $featuredVideos = $settings['show_featured_videos']
            ? Video::query()
                ->published()
                ->with('featuredMedia')
                ->latest('published_at')
                ->limit($featuredVideoLimit)
                ->get()
            : collect();

        $latestEpaper = EpaperEdition::query()
            ->published()
            ->with('featuredMedia')
            ->latest('published_at')
            ->first();

        $categoryRails = $this->categoryRails($settings);

        return compact(
            'settings',
            'heroLead',
            'heroHeadlines',
            'recentNews',
            'sidebarTopNews',
            'trendingNews',
            'featuredVideos',
            'latestEpaper',
            'categoryRails',
        );
    }

    /** @return array<string, mixed> */
    protected function settings(): array
    {
        return [
            'top_stories_count' => (int) Setting::get('top_stories_count', 6),
            'featured_videos_count' => (int) Setting::get('featured_videos_count', 4),
            'recent_news_count' => (int) Setting::get('recent_news_count', 9),
            'trending_count' => (int) Setting::get('trending_count', 8),
            'show_featured_videos' => (bool) Setting::get('show_featured_videos', true),
            'show_trending' => (bool) Setting::get('show_trending', true),
            'show_crime' => (bool) Setting::get('show_crime', true),
        ];
    }

    /** @return Collection<int, array{category: Category, articles: Collection}> */
    protected function categoryRails(array $settings): Collection
    {
        $slugs = collect($this->categoryRailOrder)
            ->when(! $settings['show_crime'], fn ($c) => $c->reject(fn ($s) => $s === 'crime'));

        $categories = Category::query()
            ->whereIn('slug', $slugs->all())
            ->get()
            ->keyBy('slug');

        return $slugs
            ->map(fn (string $slug) => $categories->get($slug))
            ->filter()
            ->map(function (Category $category) {
                $articles = Article::query()
                    ->published()
                    ->with('featuredMedia')
                    ->inCategory($category->slug)
                    ->latest('published_at')
                    ->limit(4)
                    ->get();

                return [
                    'category' => $category,
                    'articles' => $articles,
                ];
            })
            ->filter(fn (array $rail) => $rail['articles']->isNotEmpty());
    }
}
