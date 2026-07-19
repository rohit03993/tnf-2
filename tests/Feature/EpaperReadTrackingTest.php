<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\PdfStatus;
use App\Models\EpaperEdition;
use App\Models\User;
use App\Services\ArticleReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EpaperReadTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_epaper_page_shows_engagement_bar(): void
    {
        $edition = $this->createEdition(['readers_count' => 12, 'likes_count' => 3]);

        $this->get(route('epaper.show', $edition->slug))
            ->assertOk()
            ->assertSee('data-ep-engagement', false)
            ->assertSee('data-ep-like', false)
            ->assertSee('data-ep-readers-count', false)
            ->assertSee('12', false)
            ->assertSee('readers', false)
            ->assertSee('Like', false);
    }

    public function test_read_endpoint_updates_reader_count(): void
    {
        $edition = $this->createEdition();

        $this->get(route('epaper.show', $edition->slug));

        $this->withUnencryptedCookie(ArticleReadService::READER_COOKIE, 'epaper-reader-one');

        $this->postJson(route('epaper.read', $edition->slug), [], [
            'X-CSRF-TOKEN' => csrf_token(),
        ])
            ->assertOk()
            ->assertJson([
                'readers_count' => 1,
                'views_count' => 1,
                'likes_count' => 0,
                'liked' => false,
            ]);

        $this->assertDatabaseHas('epaper_editions', [
            'id' => $edition->id,
            'readers_count' => 1,
            'views_count' => 1,
        ]);
    }

    public function test_repeat_read_increments_views_not_readers(): void
    {
        $edition = $this->createEdition();
        $service = app(\App\Services\EpaperReadService::class);

        $request = \Illuminate\Http\Request::create(route('epaper.read', $edition->slug), 'POST');
        $request->cookies->set(ArticleReadService::READER_COOKIE, 'epaper-reader-repeat');

        $first = $service->record($edition, $request);
        $edition->refresh();

        $this->assertTrue($first['is_new_reader']);
        $this->assertSame(1, $first['readers_count']);
        $this->assertSame(1, $first['views_count']);

        $second = $service->record($edition->fresh(), $request);
        $edition->refresh();

        $this->assertFalse($second['is_new_reader']);
        $this->assertSame(1, $second['readers_count']);
        $this->assertSame(2, $second['views_count']);
    }

    public function test_like_endpoint_records_a_like(): void
    {
        $edition = $this->createEdition();

        $this->get(route('epaper.show', $edition->slug));
        $this->withUnencryptedCookie(ArticleReadService::READER_COOKIE, 'epaper-reader-like');

        $this->postJson(route('epaper.like', $edition->slug), [], [
            'X-CSRF-TOKEN' => csrf_token(),
        ])
            ->assertOk()
            ->assertJson([
                'liked' => true,
                'likes_count' => 1,
                'readers_count' => 1,
            ]);

        $this->assertDatabaseHas('epaper_editions', [
            'id' => $edition->id,
            'likes_count' => 1,
            'readers_count' => 1,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    protected function createEdition(array $overrides = []): EpaperEdition
    {
        Storage::fake('public');
        Storage::disk('public')->put('epaper/pdfs/test.pdf', '%PDF-1.4 test');

        $author = User::factory()->create();

        return EpaperEdition::query()->create(array_merge([
            'title' => 'Sunday Edition',
            'slug' => 'sunday-edition-'.uniqid(),
            'content' => '<p>Digital newspaper</p>',
            'author_id' => $author->id,
            'pdf_path' => 'epaper/pdfs/test.pdf',
            'restricted' => false,
            'pdf_status' => PdfStatus::Ready,
            'status' => ContentStatus::Published,
            'published_at' => now()->subHour(),
            'readers_count' => 0,
            'likes_count' => 0,
            'views_count' => 0,
        ], $overrides));
    }
}
