<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\PdfStatus;
use App\Models\EpaperEdition;
use App\Models\Media;
use App\Models\User;
use App\Services\EpaperCoverService;
use App\Services\EpaperViewerService;
use App\Services\PdfProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class EpaperCoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_ensure_page_image_returns_cached_render_for_non_first_page(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('epaper/pdfs/test.pdf', '%PDF-1.4 test');

        $edition = EpaperEdition::query()->create([
            'title' => 'Page Render Test',
            'slug' => 'page-render-test',
            'content' => '<p>Test</p>',
            'author_id' => User::factory()->create()->id,
            'pdf_path' => 'epaper/pdfs/test.pdf',
            'restricted' => false,
            'pdf_status' => PdfStatus::Ready,
            'status' => ContentStatus::Published,
            'published_at' => now()->subHour(),
        ]);

        $renderPath = "epaper/renders/{$edition->id}/page-6.jpg";
        Storage::disk('public')->put($renderPath, 'fake-jpeg');

        $url = app(EpaperCoverService::class)->ensurePageImage($edition, 6);

        $this->assertSame('/storage/'.$renderPath, $url);
    }

    public function test_share_image_source_url_uses_page_render_for_non_first_page(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('epaper/pdfs/test.pdf', '%PDF-1.4 test');

        $edition = EpaperEdition::query()->create([
            'title' => 'Clip Source Test',
            'slug' => 'clip-source-test',
            'content' => '<p>Test</p>',
            'author_id' => User::factory()->create()->id,
            'pdf_path' => 'epaper/pdfs/test.pdf',
            'restricted' => false,
            'pdf_status' => PdfStatus::Ready,
            'status' => ContentStatus::Published,
            'published_at' => now()->subHour(),
        ]);

        $renderPath = "epaper/renders/{$edition->id}/page-6.jpg";
        Storage::disk('public')->put($renderPath, 'fake-jpeg');

        $url = EpaperViewerService::shareImageSourceUrl($edition, 5);

        $this->assertSame('/storage/'.$renderPath, $url);
    }

    public function test_share_image_source_url_still_prefers_pages_json_for_non_first_page(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('epaper/pdfs/test.pdf', '%PDF-1.4 test');
        Storage::disk('public')->put('epaper/renders/1/page-2.jpg', 'fallback-jpeg');

        $edition = EpaperEdition::query()->create([
            'title' => 'Pages Json Test',
            'slug' => 'pages-json-test',
            'content' => '<p>Test</p>',
            'author_id' => User::factory()->create()->id,
            'pdf_path' => 'epaper/pdfs/test.pdf',
            'pages_json' => [
                'pages' => [
                    ['page' => 1, 'url' => '/storage/epaper/page-1.jpg'],
                    ['page' => 2, 'url' => '/storage/epaper/page-2.jpg'],
                ],
            ],
            'restricted' => false,
            'pdf_status' => PdfStatus::Ready,
            'status' => ContentStatus::Published,
            'published_at' => now()->subHour(),
        ]);

        $url = EpaperViewerService::shareImageSourceUrl($edition, 1);

        $this->assertSame('/storage/epaper/page-2.jpg', $url);
    }

    public function test_cover_service_skips_when_manual_cover_exists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('epaper/pdfs/test.pdf', '%PDF-1.4 test');

        $media = Media::query()->create([
            'disk' => 'public',
            'path' => 'epaper/covers/existing.jpg',
            'mime' => 'image/jpeg',
            'size' => 100,
            'alt' => 'Existing',
        ]);

        $edition = EpaperEdition::query()->create([
            'title' => 'Has Cover',
            'slug' => 'has-cover',
            'content' => '<p>Test</p>',
            'author_id' => User::factory()->create()->id,
            'pdf_path' => 'epaper/pdfs/test.pdf',
            'featured_media_id' => $media->id,
            'restricted' => false,
            'pdf_status' => PdfStatus::Ready,
            'status' => ContentStatus::Published,
            'published_at' => now()->subHour(),
        ]);

        $this->assertFalse(app(EpaperCoverService::class)->ensureCover($edition));
    }

    public function test_pdf_processing_attempts_cover_generation_without_pdf_service(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('epaper/pdfs/test.pdf', '%PDF-1.4 test');

        $edition = EpaperEdition::query()->create([
            'title' => 'Cover Attempt',
            'slug' => 'cover-attempt',
            'content' => '<p>Test</p>',
            'author_id' => User::factory()->create()->id,
            'pdf_path' => 'epaper/pdfs/test.pdf',
            'restricted' => false,
            'pdf_status' => PdfStatus::Idle,
            'status' => ContentStatus::Draft,
        ]);

        $mock = Mockery::mock(EpaperCoverService::class);
        $mock->shouldReceive('ensureCover')->once()->andReturn(false);
        $this->app->instance(EpaperCoverService::class, $mock);

        app(PdfProcessingService::class)->process($edition);

        $edition->refresh();

        $this->assertSame(PdfStatus::Ready, $edition->pdf_status);
    }

    public function test_epaper_archive_uses_cover_image_when_present(): void
    {
        Storage::fake('public');

        $media = Media::query()->create([
            'disk' => 'public',
            'path' => 'epaper/covers/page-1.jpg',
            'mime' => 'image/jpeg',
            'size' => 100,
            'alt' => 'Cover',
        ]);

        $edition = EpaperEdition::query()->create([
            'title' => 'Archive Cover Test',
            'slug' => 'archive-cover-test',
            'content' => '<p>Test</p>',
            'author_id' => User::factory()->create()->id,
            'pdf_path' => 'epaper/pdfs/test.pdf',
            'featured_media_id' => $media->id,
            'restricted' => false,
            'pdf_status' => PdfStatus::Ready,
            'status' => ContentStatus::Published,
            'published_at' => now()->subHour(),
        ]);

        $response = $this->get(route('epaper.index'));

        $response->assertOk();
        $response->assertSee('/storage/epaper/covers/page-1.jpg', false);
        $response->assertSee($edition->title, false);
        $response->assertDontSee('data-ep-pdf-cover', false);
    }
}
