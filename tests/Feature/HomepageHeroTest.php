<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Setting;
use App\Models\User;
use App\Services\HomepageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageHeroTest extends TestCase
{
    use RefreshDatabase;

    public function test_hero_uses_one_lead_and_remaining_headlines(): void
    {
        $author = User::factory()->create(['role' => UserRole::Admin]);
        Setting::set('top_stories_count', 6);

        foreach (range(1, 6) as $index) {
            Article::query()->create([
                'title' => "Hero story {$index}",
                'slug' => "hero-story-{$index}",
                'content' => '<p>Demo content.</p>',
                'author_id' => $author->id,
                'status' => ContentStatus::Published,
                'published_at' => now()->subMinutes(6 - $index),
            ]);
        }

        app(HomepageService::class)->clearCache();
        $data = app(HomepageService::class)->data();

        $this->assertNotNull($data['heroLead']);
        $this->assertSame(5, $data['heroHeadlines']->count());
    }
}
