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

    public function test_article_page_shows_engagement_bar(): void
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
            ->assertSee('tnf-article-engagement', false)
            ->assertSee('data-article-readers', false)
            ->assertSee('data-article-likes', false)
            ->assertSee('Like', false);
    }

    public function test_read_endpoint_updates_reader_count(): void
    {
        $author = User::factory()->create();
        $article = Article::query()->create([
            'title' => 'Tracked article',
            'slug' => 'tracked-article-read',
            'content' => '<p>Story body</p>',
            'author_id' => $author->id,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $this->get(route('article.show', $article));

        $this->withUnencryptedCookie(ArticleReadService::READER_COOKIE, 'reader-one');

        $this->postJson(route('article.read', $article), [], [
            'X-CSRF-TOKEN' => csrf_token(),
        ])
            ->assertOk()
            ->assertJson([
                'readers_count' => 1,
                'views_count' => 1,
                'likes_count' => 0,
                'liked' => false,
            ]);
    }

    public function test_like_endpoint_records_a_like(): void
    {
        $author = User::factory()->create();
        $article = Article::query()->create([
            'title' => 'Liked article',
            'slug' => 'liked-article',
            'content' => '<p>Story body</p>',
            'author_id' => $author->id,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $this->get(route('article.show', $article));

        $this->withUnencryptedCookie(ArticleReadService::READER_COOKIE, 'reader-like');

        $this->postJson(route('article.like', $article), [], [
            'X-CSRF-TOKEN' => csrf_token(),
        ])
            ->assertOk()
            ->assertJson([
                'liked' => true,
                'likes_count' => 1,
                'readers_count' => 1,
            ]);
    }
}
