<?php

namespace Tests\Unit;

use App\Support\Embed;
use Tests\TestCase;

class EmbedTest extends TestCase
{
    public function test_extracts_youtube_shorts_id_and_thumbnail(): void
    {
        $url = 'https://youtube.com/shorts/pVhZ-lAUo9w?si=bn33tlPaOKMLJUU_';

        $this->assertSame('pVhZ-lAUo9w', Embed::youtubeVideoId($url));
        $this->assertSame(
            'https://img.youtube.com/vi/pVhZ-lAUo9w/hqdefault.jpg',
            Embed::previewImageUrl($url)
        );
        $this->assertSame(
            'https://www.youtube.com/embed/pVhZ-lAUo9w',
            Embed::youtubeIframeSrc($url)
        );
    }

    public function test_detects_instagram_reel_links(): void
    {
        $this->assertTrue(Embed::isInstagram('https://www.instagram.com/reel/ABC123/'));
        $this->assertNull(Embed::previewImageUrl('https://www.instagram.com/reel/ABC123/'));
    }
}
