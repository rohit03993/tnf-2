<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacyRedirectTest extends TestCase
{
    public function test_legacy_tnf_news_url_redirects_to_article_slug(): void
    {
        $response = $this->get('/tnf_news/sample-story-slug');

        $response->assertRedirect(route('article.show', 'sample-story-slug'));
        $response->assertStatus(301);
    }
}
