<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SiteChromeService
{
    public static function chrome(bool $authLite = false): array
    {
        $ttl = (int) config('tnf.chrome_cache_ttl', 300);

        return Cache::remember('site.chrome.'.($authLite ? 'auth' : 'full'), $ttl, function () use ($authLite) {
            $navCategories = static::navCategories();

            return [
                'authLite' => $authLite,
                'breakingHeadlines' => $authLite
                    ? collect()
                    : Article::query()->published()
                        ->latest('published_at')
                        ->limit((int) Setting::get('breaking_count', 12))
                        ->get(['id', 'title', 'slug']),
                'navCategories' => $navCategories,
                'primaryNav' => static::primaryNav($navCategories),
                'bannerImage' => Setting::get('banner_image', ''),
                'bannerLink' => Setting::get('banner_link_url', ''),
                'whatsappUrl' => Setting::get('whatsapp_url', ''),
                'siteLogo' => Setting::get('site_logo', ''),
                'siteLogoUrl' => BrandLogoService::url(),
                'siteFavicon' => Setting::get('site_favicon', ''),
                'disclaimerText' => Setting::get('disclaimer_text', ''),
                'disclaimerEmail' => Setting::get('disclaimer_email', 'contact@tnftoday.com'),
                'creditsLine' => Setting::get('credits_line', ''),
                'hotTags' => $authLite
                    ? collect()
                    : Tag::query()
                        ->whereHas('articles')
                        ->withCount('articles')
                        ->orderByDesc('articles_count')
                        ->limit(12)
                        ->get(['id', 'name', 'slug']),
            ];
        });
    }

    /** @return Collection<int, array{label: string, url: string, slug: string, articles_count: int}> */
    public static function navCategories(): Collection
    {
        return Category::query()
            ->whereHas('articles', fn (Builder $query) => $query->published())
            ->withCount(['articles as articles_count' => fn (Builder $query) => $query->published()])
            ->orderByDesc('articles_count')
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $category) => [
                'label' => $category->name,
                'url' => route('category.show', $category->slug),
                'slug' => $category->slug,
                'articles_count' => (int) $category->articles_count,
            ]);
    }

    /** @return array<int, array{label: string, url: string, slug?: string, articles_count?: int}> */
    protected static function primaryNav(Collection $navCategories): array
    {
        $items = [
            ['label' => 'Home', 'url' => route('home'), 'slug' => 'home'],
            ['label' => 'Videos', 'url' => route('videos.index'), 'slug' => 'videos'],
        ];

        foreach ($navCategories->take(5) as $category) {
            $items[] = $category;
        }

        if ($navCategories->count() > 5) {
            $items[] = ['label' => 'More', 'url' => '#', 'slug' => 'more'];
        }

        return $items;
    }
}
