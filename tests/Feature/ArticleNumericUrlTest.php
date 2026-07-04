<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleNumericUrlTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{article: Article, author: User} */
    protected function publishedArticle(string $slug = 'sample-national-story'): array
    {
        $author = User::factory()->create();

        $article = Article::query()->create([
            'title' => 'Sample headline',
            'slug' => $slug,
            'content' => '<p>Demo content.</p>',
            'author_id' => $author->id,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        return compact('article', 'author');
    }

    public function test_numeric_article_url_returns_successfully(): void
    {
        ['article' => $article] = $this->publishedArticle();

        $this->get(route('article.show', $article))
            ->assertOk()
            ->assertSee('Sample headline', false);
    }

    public function test_legacy_slug_url_redirects_to_numeric_url(): void
    {
        ['article' => $article] = $this->publishedArticle('aagra-dm-school-action');

        $this->get('/'.$article->slug)
            ->assertRedirect(route('article.show', $article))
            ->assertStatus(301);
    }

    public function test_legacy_tnf_news_url_redirects_to_numeric_url(): void
    {
        ['article' => $article] = $this->publishedArticle('legacy-wordpress-slug');

        $this->get('/tnf_news/'.$article->slug)
            ->assertRedirect(route('article.show', $article))
            ->assertStatus(301);
    }

    public function test_unpublished_legacy_slug_returns_not_found(): void
    {
        $author = User::factory()->create();

        $article = Article::query()->create([
            'title' => 'Draft story',
            'slug' => 'draft-story-slug',
            'content' => '<p>Draft.</p>',
            'author_id' => $author->id,
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ]);

        $this->get('/'.$article->slug)->assertNotFound();
        $this->get(route('article.show', $article))->assertNotFound();
    }
}
