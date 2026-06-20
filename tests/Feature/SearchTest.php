<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\PdfStatus;
use App\Enums\UserRole;
use App\Models\EpaperEdition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_published_epaper_editions(): void
    {
        $author = User::factory()->create(['role' => UserRole::Admin]);

        $edition = EpaperEdition::query()->create([
            'title' => 'UniqueEpaperSearchTerm Daily Edition',
            'slug' => 'unique-epaper-search-term',
            'content' => '<p>Edition body</p>',
            'author_id' => $author->id,
            'status' => ContentStatus::Published,
            'pdf_status' => PdfStatus::Ready,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/search?q=UniqueEpaperSearchTerm');

        $response->assertOk();
        $response->assertSee($edition->title, false);
        $response->assertSee('ePaper', false);
    }
}
