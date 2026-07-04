<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\Article;
use App\Models\User;
use App\Services\ArticleReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleReadTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_page_shows_reader_count_and_tracking_endpoint_updates_it(): void
    {
        $author = User::factory()->create();
        $article = Article::query()->create([
            'title' => 'Tracked article',
            'slug' => 'tracked-article',
            'content' => '<p>Story body</p>',
            'author_id' => $author->id,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $this->get(route('article.show', $article))
            ->assertOk()
            ->assertSee('data-article-readers', false)
            ->assertSee('readers', false);

        $this->postJson(route('article.read', $article), [], [
            'X-CSRF-TOKEN' => csrf_token(),
            'Cookie' => ArticleReadService::READER_COOKIE.'=reader-one',
        ])
            ->assertOk()
            ->assertJson([
                'readers_count' => 1,
                'views_count' => 1,
            ]);

        $article->refresh();
        $this->assertSame(1, $article->readers_count);
        $this->assertSame(1, $article->views_count);
    }
}
