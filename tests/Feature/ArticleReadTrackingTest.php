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
            ->assertSee('data-server-recorded="1"', false)
            ->assertSee('Like', false);
    }

    public function test_opening_article_records_a_reader(): void
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

        $this->withUnencryptedCookie(ArticleReadService::READER_COOKIE, 'reader-one')
            ->get(route('article.show', $article))
            ->assertOk();

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'readers_count' => 1,
            'views_count' => 1,
        ]);
    }

    public function test_repeat_visit_increments_views_not_readers(): void
    {
        $author = User::factory()->create();
        $article = Article::query()->create([
            'title' => 'Tracked article',
            'slug' => 'tracked-article-repeat',
            'content' => '<p>Story body</p>',
            'author_id' => $author->id,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $this->withUnencryptedCookie(ArticleReadService::READER_COOKIE, 'reader-repeat')
            ->get(route('article.show', $article))
            ->assertOk();

        $this->withUnencryptedCookie(ArticleReadService::READER_COOKIE, 'reader-repeat')
            ->get(route('article.show', $article))
            ->assertOk();

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'readers_count' => 1,
            'views_count' => 2,
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

        $this->withUnencryptedCookie(ArticleReadService::READER_COOKIE, 'reader-like')
            ->get(route('article.show', $article))
            ->assertOk();

        $this->withUnencryptedCookie(ArticleReadService::READER_COOKIE, 'reader-like')
            ->postJson(route('article.like', $article), [], [
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
