<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_videos_index_renders_published_videos(): void
    {
        $author = User::factory()->create();

        Video::query()->create([
            'title' => 'Sample video',
            'slug' => 'sample-video',
            'embed_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'author_id' => $author->id,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $this->get(route('videos.index'))
            ->assertOk()
            ->assertSee('Sample video', false);
    }

    public function test_homepage_renders_when_featured_videos_exist(): void
    {
        $author = User::factory()->create();

        Video::query()->create([
            'title' => 'Homepage video',
            'slug' => 'homepage-video',
            'embed_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'author_id' => $author->id,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Homepage video', false);
    }

    public function test_video_show_page_renders_youtube_embed_with_referrer_policy(): void
    {
        $author = User::factory()->create();

        $video = Video::query()->create([
            'title' => 'Shorts video',
            'slug' => 'shorts-video',
            'embed_url' => 'https://www.youtube.com/shorts/gcGBasBaOj8',
            'author_id' => $author->id,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $this->get(route('videos.show', $video->slug))
            ->assertOk()
            ->assertSee('referrerpolicy="strict-origin-when-cross-origin"', false)
            ->assertSee('youtube-nocookie.com/embed/gcGBasBaOj8', false);
    }
}
