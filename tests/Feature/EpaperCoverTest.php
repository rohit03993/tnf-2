<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\PdfStatus;
use App\Models\EpaperEdition;
use App\Models\Media;
use App\Models\User;
use App\Services\EpaperCoverService;
use App\Services\PdfProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class EpaperCoverTest extends TestCase
{
    use RefreshDatabase;

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
