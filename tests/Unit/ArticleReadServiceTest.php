<?php

namespace Tests\Unit;

use App\Enums\ContentStatus;
use App\Models\Article;
use App\Models\User;
use App\Services\ArticleReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ArticleReadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_unique_reader_and_increments_counters(): void
    {
        $author = User::factory()->create();
        $article = Article::query()->create([
            'title' => 'Read test',
            'slug' => 'read-test',
            'content' => 'Body',
            'author_id' => $author->id,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $service = app(ArticleReadService::class);
        $request = Request::create('/n/'.$article->id.'/read', 'POST');
        $request->cookies->set(ArticleReadService::READER_COOKIE, 'reader-abc');

        $first = $service->record($article, $request);
        $article->refresh();

        $this->assertTrue($first['is_new_reader']);
        $this->assertSame(1, $first['readers_count']);
        $this->assertSame(1, $first['views_count']);

        $second = $service->record($article->fresh(), $request);
        $article->refresh();

        $this->assertFalse($second['is_new_reader']);
        $this->assertSame(1, $second['readers_count']);
        $this->assertSame(2, $second['views_count']);
    }

    public function test_toggle_like_adds_and_removes_like_for_same_reader(): void
    {
        $author = User::factory()->create();
        $article = Article::query()->create([
            'title' => 'Like test',
            'slug' => 'like-test',
            'content' => 'Body',
            'author_id' => $author->id,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $service = app(ArticleReadService::class);
        $request = Request::create('/n/'.$article->id.'/like', 'POST');
        $request->cookies->set(ArticleReadService::READER_COOKIE, 'reader-like');

        $liked = $service->toggleLike($article, $request);
        $article->refresh();

        $this->assertTrue($liked['liked']);
        $this->assertSame(1, $liked['likes_count']);
        $this->assertSame(1, $article->likes_count);

        $unliked = $service->toggleLike($article, $request);
        $article->refresh();

        $this->assertFalse($unliked['liked']);
        $this->assertSame(0, $unliked['likes_count']);
        $this->assertSame(0, $article->likes_count);
    }
}
