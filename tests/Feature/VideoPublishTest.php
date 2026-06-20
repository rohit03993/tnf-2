<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoPublishTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_video_without_date_gets_publish_timestamp_on_save(): void
    {
        $author = User::factory()->create();

        $video = Video::query()->create([
            'title' => 'Short clip',
            'slug' => 'short-clip',
            'author_id' => $author->id,
            'embed_url' => 'https://youtube.com/shorts/pVhZ-lAUo9w',
            'status' => ContentStatus::Published,
            'published_at' => null,
        ]);

        $this->assertNotNull($video->fresh()->published_at);
        $this->assertTrue(
            Video::query()->published()->whereKey($video->id)->exists()
        );
    }

    public function test_youtube_shorts_auto_thumbnail(): void
    {
        $author = User::factory()->create();

        $video = Video::query()->create([
            'title' => 'Short clip',
            'slug' => 'short-clip-thumb',
            'author_id' => $author->id,
            'embed_url' => 'https://youtube.com/shorts/pVhZ-lAUo9w',
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $this->assertSame(
            'https://img.youtube.com/vi/pVhZ-lAUo9w/hqdefault.jpg',
            $video->thumbnailUrl()
        );
    }
}
