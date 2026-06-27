<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\PdfStatus;
use App\Models\EpaperEdition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EpaperSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_epaper_edition_page_includes_og_image_meta(): void
    {
        Storage::fake('public');

        $edition = $this->createEdition();
        Storage::disk('public')->put('epaper/covers/'.$edition->id.'.jpg', $this->fakeJpeg());

        $response = $this->get(route('epaper.show', $edition->slug));

        $response->assertOk();
        $response->assertSee('property="og:image"', false);
        $response->assertSee(route('og.epaper.page', $edition, absolute: true), false);
        $response->assertSee('property="og:title"', false);
        $response->assertSee($edition->title, false);
    }

    public function test_epaper_clip_page_includes_cropped_og_image_meta(): void
    {
        Storage::fake('public');

        $edition = $this->createEdition();
        Storage::disk('public')->put('epaper/covers/'.$edition->id.'.jpg', $this->fakeJpeg());

        $response = $this->get(route('epaper.show', [
            'edition' => $edition->slug,
            'tnf_clip' => 1,
            'tnf_pg' => 1,
            'tnf_cx' => 0.1,
            'tnf_cy' => 0.2,
            'tnf_cw' => 0.5,
            'tnf_ch' => 0.4,
        ]));

        $response->assertOk();
        $response->assertSee('property="og:image"', false);
        $response->assertSee('/pdf-report/'.$edition->id.'/clip-og', false);
        $response->assertSee('tnf_cw=0.5', false);
    }

    protected function createEdition(): EpaperEdition
    {
        $author = User::factory()->create();

        $edition = EpaperEdition::query()->create([
            'title' => 'Sunday Edition',
            'slug' => 'sunday-edition',
            'content' => '<p>Digital newspaper</p>',
            'author_id' => $author->id,
            'pdf_path' => 'epaper/pdfs/test.pdf',
            'restricted' => false,
            'pdf_status' => PdfStatus::Ready,
            'status' => ContentStatus::Published,
            'published_at' => now()->subHour(),
        ]);

        $media = \App\Models\Media::query()->create([
            'disk' => 'public',
            'path' => 'epaper/covers/'.$edition->id.'.jpg',
            'mime' => 'image/jpeg',
            'size' => 100,
            'alt' => $edition->title,
        ]);

        $edition->update(['featured_media_id' => $media->id]);

        return $edition->fresh(['featuredMedia']);
    }

    protected function fakeJpeg(): string
    {
        if (! extension_loaded('gd')) {
            return '';
        }

        $image = imagecreatetruecolor(10, 10);
        ob_start();
        imagejpeg($image);
        $bytes = ob_get_clean() ?: '';
        imagedestroy($image);

        return $bytes;
    }
}
