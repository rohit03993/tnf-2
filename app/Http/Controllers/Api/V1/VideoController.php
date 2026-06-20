<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Support\Api\WpContentSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $videos = Video::query()
            ->published()
            ->with('featuredMedia')
            ->latest('published_at')
            ->paginate((int) $request->query('per_page', 15));

        return response()->json([
            'data' => $videos->getCollection()->map(fn (Video $video) => WpContentSerializer::video($video))->values(),
            'meta' => [
                'current_page' => $videos->currentPage(),
                'last_page' => $videos->lastPage(),
                'per_page' => $videos->perPage(),
                'total' => $videos->total(),
            ],
        ]);
    }

    public function show(Video $video): JsonResponse
    {
        abort_unless(
            $video->status === ContentStatus::Published
            && $video->published_at
            && $video->published_at <= now(),
            404,
        );

        return response()->json([
            'data' => WpContentSerializer::video($video->load('featuredMedia')),
        ]);
    }
}
