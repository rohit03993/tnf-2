<?php

namespace Tests\Unit;

use App\Services\OgImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OgImageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_epaper_cover_top_fills_canvas_from_page_top(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension required');
        }

        Storage::fake('public');

        // Tall newspaper-like page: red masthead at top, blue footer at bottom.
        $source = imagecreatetruecolor(400, 1000);
        $red = imagecolorallocate($source, 200, 20, 20);
        $blue = imagecolorallocate($source, 20, 20, 200);
        imagefilledrectangle($source, 0, 0, 399, 499, $red);
        imagefilledrectangle($source, 0, 500, 399, 999, $blue);

        ob_start();
        imagejpeg($source, null, 90);
        $bytes = ob_get_clean() ?: '';
        imagedestroy($source);

        Storage::disk('public')->put('epaper/covers/tall.jpg', $bytes);

        $og = app(OgImageService::class)->generateForEntity(
            'epaper',
            1,
            '/storage/epaper/covers/tall.jpg',
            'cover-top',
        );

        $this->assertNotNull($og);
        $this->assertSame('cover-top', $og->signature_hash);

        $result = imagecreatefromstring(Storage::disk('public')->get($og->path));
        $this->assertNotFalse($result);
        $this->assertSame(1200, imagesx($result));
        $this->assertSame(630, imagesy($result));

        // Top-center should be from the red masthead, not white letterboxing.
        $top = imagecolorat($result, 600, 20);
        $r = ($top >> 16) & 0xFF;
        $g = ($top >> 8) & 0xFF;
        $b = $top & 0xFF;
        $this->assertGreaterThan(150, $r);
        $this->assertLessThan(80, $g);
        $this->assertLessThan(80, $b);

        // Side edge should also be filled (no white sidebar).
        $side = imagecolorat($result, 10, 315);
        $sr = ($side >> 16) & 0xFF;
        $sg = ($side >> 8) & 0xFF;
        $sb = $side & 0xFF;
        $this->assertFalse($sr > 240 && $sg > 240 && $sb > 240);

        imagedestroy($result);
    }

    public function test_branded_clip_jpeg_includes_header_band_above_crop(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension required');
        }

        Storage::fake('public');

        $source = imagecreatetruecolor(800, 1000);
        $green = imagecolorallocate($source, 20, 180, 60);
        imagefilledrectangle($source, 0, 0, 799, 999, $green);

        ob_start();
        imagejpeg($source, null, 90);
        $bytes = ob_get_clean() ?: '';
        imagedestroy($source);

        Storage::disk('public')->put('epaper/covers/clip-source.jpg', $bytes);

        $logo = imagecreatetruecolor(120, 40);
        $white = imagecolorallocate($logo, 255, 255, 255);
        imagefilledrectangle($logo, 0, 0, 119, 39, $white);
        ob_start();
        imagepng($logo);
        $logoBytes = ob_get_clean() ?: '';
        imagedestroy($logo);
        Storage::disk('public')->put('settings/brand/logo.png', $logoBytes);

        \App\Models\Setting::set('site_logo', 'settings/brand/logo.png');

        $jpeg = app(OgImageService::class)->buildBrandedClipJpeg(
            '/storage/epaper/covers/clip-source.jpg',
            ['x' => 0.1, 'y' => 0.2, 'w' => 0.5, 'h' => 0.3],
            '23 July 2024 TNF Today Newspaper',
        );

        $this->assertNotNull($jpeg);

        $result = imagecreatefromstring($jpeg);
        $this->assertNotFalse($result);
        $this->assertSame(1200, imagesx($result));
        $this->assertSame(630, imagesy($result));

        // Header band should be navy, not the green crop.
        $header = imagecolorat($result, 600, 40);
        $hr = ($header >> 16) & 0xFF;
        $hg = ($header >> 8) & 0xFF;
        $hb = $header & 0xFF;
        $this->assertLessThan(40, $hr);
        $this->assertLessThan(40, $hg);
        $this->assertLessThan(50, $hb);

        // Content area below header should contain green from the crop.
        $body = imagecolorat($result, 600, 360);
        $br = ($body >> 16) & 0xFF;
        $bg = ($body >> 8) & 0xFF;
        $bb = $body & 0xFF;
        $this->assertGreaterThan(100, $bg);
        $this->assertLessThan(80, $br);

        imagedestroy($result);
    }
}
