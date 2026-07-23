<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\PdfStatus;
use App\Models\EpaperEdition;
use App\Models\User;
use App\Services\EpaperClipCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EpaperClipTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsigned_clip_url_is_accessible_when_signing_enabled(): void
    {
        config(['tnf.epaper_clip_og_secret' => 'test-clip-secret']);

        $edition = $this->makeEdition();

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

    public function test_sign_clip_returns_short_url(): void
    {
        $edition = $this->makeEdition();

        $response = $this->postJson(route('epaper.sign-clip', $edition->slug), [
            'page' => 5,
            'x' => 0.3822,
            'y' => 0.3895,
            'w' => 0.6059,
            'h' => 0.2437,
        ]);

        $response->assertOk();
        $url = $response->json('url');
        $token = $response->json('token');

        $this->assertIsString($token);
        $this->assertSame(route('epaper.clip.short', ['token' => $token]), $url);
        $this->assertStringNotContainsString('tnf_clip=', $url);
        $this->assertLessThan(strlen(route('epaper.show', $edition->slug)) + 40, strlen($url));
    }

    public function test_short_clip_url_opens_clip_mode(): void
    {
        $edition = $this->makeEdition();

        $token = EpaperClipCodeService::encode($edition->id, [
            'page' => 5,
            'x' => 0.3822,
            'y' => 0.3895,
            'w' => 0.6059,
            'h' => 0.2437,
        ]);

        $response = $this->get(route('epaper.clip.short', ['token' => $token]));

        $response->assertOk();
        $response->assertSee('tnf-epaper-viewer', false);
        $response->assertSee('"clipMode":true', false);
        $response->assertSee('"page":5', false);
    }

    public function test_invalid_short_clip_token_returns_404(): void
    {
        $this->get(route('epaper.clip.short', ['token' => 'not-a-valid-token']))
            ->assertNotFound();
    }

    protected function makeEdition(): EpaperEdition
    {
        Storage::fake('public');
        Storage::disk('public')->put('epaper/pdfs/test.pdf', '%PDF-1.4 test');

        return EpaperEdition::query()->create([
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
    }
}
