<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Services\PageCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ContentCacheBustTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_article_busts_homepage_and_chrome_caches(): void
    {
        $author = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'National',
            'slug' => 'national',
        ]);

        Cache::put('homepage.data.v2', ['stale' => true], 3600);
        Cache::put('homepage.data', ['stale' => true], 3600);
        Cache::put('site.chrome.full', ['stale' => true], 3600);
        Cache::put('page_cache.version', 10, 3600);
        $versionBefore = PageCacheService::version();

        $article = Article::query()->create([
            'title' => 'Breaking story',
            'slug' => 'breaking-story',
            'content' => 'Test content',
            'author_id' => $author->id,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $article->categories()->attach($category->id);

        $this->assertFalse(Cache::has('homepage.data'));
        $this->assertFalse(Cache::has('homepage.data.v2'));
        $this->assertFalse(Cache::has('site.chrome.full'));
        $this->assertGreaterThan($versionBefore, PageCacheService::version());
    }
}
