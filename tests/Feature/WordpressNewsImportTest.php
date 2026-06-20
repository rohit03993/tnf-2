<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordpressNewsImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_published_news_from_wxr_xml(): void
    {
        User::factory()->create(['role' => UserRole::Admin]);

        $fixture = base_path('tests/fixtures/wordpress-news-sample.xml');

        $this->artisan('tnf:import-wordpress', ['path' => $fixture])
            ->assertSuccessful();

        $this->assertDatabaseHas('articles', [
            'slug' => 'sample-national-story',
            'title' => 'Sample National Story',
            'status' => ContentStatus::Published->value,
            'comment_count' => 12,
            'embed_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);

        $article = Article::query()->where('slug', 'sample-national-story')->first();

        $this->assertNotNull($article);
        $this->assertTrue($article->categories->contains('slug', 'national'));
        $this->assertTrue($article->tags->contains('slug', 'breaking'));
        $this->assertStringContainsString('Story body from WordPress', $article->content);

        $this->assertDatabaseHas('articles', [
            'slug' => 'draft-story',
            'status' => ContentStatus::Draft->value,
        ]);

        $this->assertDatabaseMissing('articles', [
            'slug' => 'ignored-video',
        ]);
    }

    public function test_dry_run_does_not_write_articles(): void
    {
        User::factory()->create(['role' => UserRole::Admin]);
        Category::query()->create(['name' => 'National', 'slug' => 'national']);

        $fixture = base_path('tests/fixtures/wordpress-news-sample.xml');

        $this->artisan('tnf:import-wordpress', [
            'path' => $fixture,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame(0, Article::query()->count());
    }
}
