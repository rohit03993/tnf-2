<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\PwaIconService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PwaManifestTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_json_is_served_dynamically(): void
    {
        $this->get(route('manifest'))
            ->assertOk()
            ->assertHeader('content-type', 'application/manifest+json')
            ->assertJsonPath('name', config('app.name'))
            ->assertJsonPath('short_name', 'TNF Today')
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('icons.0.sizes', '192x192')
            ->assertJsonPath('icons.1.sizes', '512x512')
            ->assertJsonPath('icons.2.purpose', 'maskable');
    }

    public function test_pwa_icons_are_available(): void
    {
        $this->get(route('pwa.icon', ['size' => 192]))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->get(route('pwa.icon', ['size' => 512]))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_homepage_links_to_dynamic_manifest(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('manifest'), false);
    }

    public function test_service_worker_file_exists_on_disk(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertStringContainsString('tnf-pwa-v4', (string) file_get_contents(public_path('sw.js')));
    }

    public function test_pwa_icon_uses_uploaded_square_logo_from_settings(): void
    {
        Storage::fake('public');

        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true,
        );

        Storage::disk('public')->put(PwaIconService::CANONICAL_PATH, $png);
        Setting::set('pwa_icon', PwaIconService::CANONICAL_PATH);

        $this->get(route('pwa.icon', ['size' => 192]))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }
}
