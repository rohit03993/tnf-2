<?php

namespace Tests\Feature;

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_valid_xml(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('<urlset', false);
        $response->assertSee(route('home'), false);
    }

    public function test_sitemap_includes_tag_urls(): void
    {
        $tag = Tag::query()->create(['name' => 'Election', 'slug' => 'election']);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee(route('tag.show', $tag->slug), false);
    }
}
