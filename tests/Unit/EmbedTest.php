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

        $embedSrc = Embed::youtubeIframeSrc($url);

        $this->assertStringStartsWith('https://www.youtube-nocookie.com/embed/pVhZ-lAUo9w?', $embedSrc);
        $this->assertStringContainsString('origin=', $embedSrc);
    }

    public function test_extracts_youtube_shorts_path_with_www(): void
    {
        $url = 'https://www.youtube.com/shorts/gcGBasBaOj8';

        $this->assertSame('gcGBasBaOj8', Embed::youtubeVideoId($url));
        $this->assertStringContainsString('youtube-nocookie.com/embed/gcGBasBaOj8', Embed::youtubeIframeSrc($url));
    }

    public function test_detects_instagram_reel_links(): void
    {
        $this->assertTrue(Embed::isInstagram('https://www.instagram.com/reel/ABC123/'));
        $this->assertNull(Embed::previewImageUrl('https://www.instagram.com/reel/ABC123/'));
        $this->assertNull(Embed::youtubeIframeSrc('https://www.instagram.com/reel/ABC123/'));
    }
}
