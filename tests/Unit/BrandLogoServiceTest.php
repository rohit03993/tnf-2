<?php

namespace Tests\Unit;

use App\Services\BrandLogoService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandLogoServiceTest extends TestCase
{
    public function test_it_normalizes_uploaded_logo_into_square_png(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available.');
        }

        Storage::fake('public');

        $source = imagecreatetruecolor(800, 800);
        $red = imagecolorallocate($source, 188, 30, 56);
        imagefilledrectangle($source, 0, 0, 800, 800, $red);

        ob_start();
        imagepng($source);
        $png = ob_get_clean() ?: '';
        imagedestroy($source);

        Storage::disk('public')->put('settings/brand/uploads/source.png', $png);

        $path = BrandLogoService::process('public', 'settings/brand/uploads/source.png');

        $this->assertSame(BrandLogoService::CANONICAL_PATH, $path);
        $this->assertTrue(Storage::disk('public')->exists(BrandLogoService::CANONICAL_PATH));
        $this->assertFalse(Storage::disk('public')->exists('settings/brand/uploads/source.png'));

        $optimized = Storage::disk('public')->get(BrandLogoService::CANONICAL_PATH);
        $image = imagecreatefromstring($optimized);

        $this->assertNotFalse($image);
        $this->assertSame(BrandLogoService::OUTPUT_SIZE, imagesx($image));
        $this->assertSame(BrandLogoService::OUTPUT_SIZE, imagesy($image));
        imagedestroy($image);
    }
}
