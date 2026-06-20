<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SiteChromeService
{
    public static function chrome(bool $authLite = false): array
    {
        $ttl = (int) config('tnf.chrome_cache_ttl', 300);

        return Cache::remember('site.chrome.'.($authLite ? 'auth' : 'full'), $ttl, function () use ($authLite) {
            $categories = Category::query()->orderBy('name')->get()->keyBy('slug');

            return [
                'authLite' => $authLite,
                'breakingHeadlines' => $authLite
                    ? collect()
                    : Article::query()->published()
                        ->latest('published_at')
                        ->limit((int) Setting::get('breaking_count', 12))
                        ->get(['id', 'title', 'slug']),
                'drawerGroups' => static::drawerGroups($categories),
                'primaryNav' => static::primaryNav($categories),
                'bannerImage' => Setting::get('banner_image', ''),
                'bannerLink' => Setting::get('banner_link_url', ''),
                'whatsappUrl' => Setting::get('whatsapp_url', ''),
                'disclaimerText' => Setting::get('disclaimer_text', ''),
                'disclaimerEmail' => Setting::get('disclaimer_email', 'contact@tnftoday.com'),
                'creditsLine' => Setting::get('credits_line', ''),
            ];
        });
    }

    /** @return array<string, array<int, array{label: string, url: string}>> */
    protected static function drawerGroups(Collection $categories): array
    {
        $link = fn (string $slug, string $fallback) => $categories->has($slug)
            ? ['label' => $categories[$slug]->name, 'url' => route('category.show', $categories[$slug]->slug)]
            : ['label' => $fallback, 'url' => '#'];

        return [
            'Start here' => [
                ['label' => 'Home', 'url' => route('home')],
            ],
            'Daily digest' => [
                $link('national', 'National'),
                $link('health', 'Health'),
                $link('religion', 'Religion'),
                $link('entertainment', 'Entertainment'),
            ],
            'Desk & arena' => [
                $link('tech', 'Tech'),
                $link('politics', 'Politics'),
                $link('sports', 'Sports'),
                $link('business', 'Business'),
            ],
            'Magazine' => [
                $link('exclusive', 'Exclusive'),
                $link('lifestyle', 'Lifestyle'),
                $link('cultural', 'Cultural'),
                $link('crime', 'Crime'),
            ],
        ];
    }

    /** @return array<int, array{label: string, url: string, slug?: string}> */
    protected static function primaryNav(Collection $categories): array
    {
        $items = [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'ePaper', 'url' => route('epaper.index')],
            ['label' => 'Videos', 'url' => route('videos.index'), 'slug' => 'videos'],
        ];

        foreach (['national', 'entertainment', 'religion', 'lifestyle', 'sports'] as $slug) {
            if ($categories->has($slug)) {
                $items[] = [
                    'label' => $categories[$slug]->name,
                    'url' => route('category.show', $slug),
                    'slug' => $slug,
                ];
            }
        }

        $items[] = ['label' => 'More', 'url' => '#', 'slug' => 'more'];

        return $items;
    }
}
