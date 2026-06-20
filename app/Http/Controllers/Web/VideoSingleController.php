<?php

namespace App\Http\Controllers\Web;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Services\SeoService;
use Illuminate\View\View;

class VideoSingleController extends Controller
{
    public function __invoke(Video $video, SeoService $seo): View
    {
        abort_unless(
            $video->status === ContentStatus::Published
            && $video->published_at
            && $video->published_at <= now(),
            404,
        );

        $video->load(['author', 'categories', 'featuredMedia', 'tags']);

        return view('pages.videos.show', [
            'video' => $video,
            'seo' => $seo->forVideo($video),
        ]);
    }
}
