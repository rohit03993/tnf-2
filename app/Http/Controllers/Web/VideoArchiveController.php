<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\View\View;

class VideoArchiveController extends Controller
{
    public function __invoke(): View
    {
        $videos = Video::query()
            ->published()
            ->with('featuredMedia')
            ->latest('published_at')
            ->paginate(12);

        return view('pages.videos.index', compact('videos'));
    }
}
