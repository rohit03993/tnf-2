<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_tnf_news_url_redirects_to_numeric_article_url(): void
    {
        $author = User::factory()->create();

        $article = Article::query()->create([
            'title' => 'Legacy story',
            'slug' => 'sample-story-slug',
            'content' => '<p>Demo content.</p>',
            'author_id' => $author->id,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $response = $this->get('/tnf_news/sample-story-slug');

        $response->assertRedirect(route('article.show', $article));
        $response->assertStatus(301);
    }
}
