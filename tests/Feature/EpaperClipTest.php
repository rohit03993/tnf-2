<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\PdfStatus;
use App\Models\EpaperEdition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EpaperClipTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsigned_clip_url_is_accessible_when_signing_enabled(): void
    {
        config(['tnf.epaper_clip_og_secret' => 'test-clip-secret']);

        Storage::fake('public');
        Storage::disk('public')->put('epaper/pdfs/test.pdf', '%PDF-1.4 test');

        $edition = EpaperEdition::query()->create([
            'title' => 'Clip Test Edition',
            'slug' => 'clip-test-edition',
            'content' => '<p>Test</p>',
            'author_id' => User::factory()->create()->id,
            'pdf_path' => 'epaper/pdfs/test.pdf',
            'restricted' => false,
            'pdf_status' => PdfStatus::Ready,
            'status' => ContentStatus::Published,
            'published_at' => now()->subHour(),
        ]);

        $response = $this->get(route('epaper.show', [
            'edition' => $edition->slug,
            'tnf_clip' => 1,
            'tnf_pg' => 1,
            'tnf_cx' => 0.4934,
            'tnf_cy' => 0.1712,
            'tnf_cw' => 0.5066,
            'tnf_ch' => 0.2247,
        ]));

        $response->assertOk();
        $response->assertSee('tnf-epaper-viewer', false);
        $response->assertSee('pdfUrl', false);
        $response->assertDontSee('This edition has no pages yet', false);
    }
}
