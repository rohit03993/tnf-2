<?php

namespace Tests\Unit;

use App\Services\BrandLogoService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandLogoServiceTest extends TestCase
{
    public function test_it_preserves_horizontal_logo_dimensions(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available.');
        }

        Storage::fake('public');

        $source = imagecreatetruecolor(1600, 400);
        $red = imagecolorallocate($source, 188, 30, 56);
        imagefilledrectangle($source, 0, 0, 1600, 400, $red);

        ob_start();
        imagepng($source);
        $png = ob_get_clean() ?: '';
        imagedestroy($source);

        Storage::disk('public')->put('settings/brand/uploads/source.png', $png);

        $path = BrandLogoService::process('public', 'settings/brand/uploads/source.png');

        $this->assertSame(BrandLogoService::CANONICAL_PATH, $path);

        $image = imagecreatefromstring(Storage::disk('public')->get(BrandLogoService::CANONICAL_PATH));

        $this->assertNotFalse($image);
        $this->assertSame(BrandLogoService::MAX_WIDTH, imagesx($image));
        $this->assertSame(350, imagesy($image));
        imagedestroy($image);
    }
}
