<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Services\SiteChromeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_nav_categories_include_only_categories_with_published_news(): void
    {
        $author = User::factory()->create();

        $agra = Category::query()->create(['name' => 'Agra News', 'slug' => 'agra-news']);
        $empty = Category::query()->create(['name' => 'Business', 'slug' => 'business']);
        $politics = Category::query()->create(['name' => 'Politics', 'slug' => 'politics']);

        foreach (range(1, 3) as $index) {
            $article = Article::query()->create([
                'title' => "Politics story {$index}",
                'slug' => "politics-story-{$index}",
                'content' => '<p>Demo</p>',
                'author_id' => $author->id,
                'status' => ContentStatus::Published,
                'published_at' => now()->subMinutes($index),
            ]);
            $article->categories()->attach($politics);
        }

        $agraArticle = Article::query()->create([
            'title' => 'Agra story',
            'slug' => 'agra-story',
            'content' => '<p>Demo</p>',
            'author_id' => $author->id,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);
        $agraArticle->categories()->attach($agra);

        Article::query()->create([
            'title' => 'Draft only',
            'slug' => 'draft-only',
            'content' => '<p>Demo</p>',
            'author_id' => $author->id,
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ])->categories()->attach($empty);

        $navCategories = SiteChromeService::navCategories();

        $this->assertCount(2, $navCategories);
        $this->assertSame('politics', $navCategories->first()['slug']);
        $this->assertSame(3, $navCategories->first()['articles_count']);
        $this->assertSame('agra-news', $navCategories->last()['slug']);
        $this->assertFalse($navCategories->contains(fn (array $item) => $item['slug'] === 'business'));
    }

    public function test_homepage_menu_lists_categories_with_news(): void
    {
        $author = User::factory()->create();
        $category = Category::query()->create(['name' => 'Education', 'slug' => 'education']);

        $article = Article::query()->create([
            'title' => 'School update',
            'slug' => 'school-update',
            'content' => '<p>Demo</p>',
            'author_id' => $author->id,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);
        $article->categories()->attach($category);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Education', false)
            ->assertSee(route('category.show', 'education'), false);
    }
}
