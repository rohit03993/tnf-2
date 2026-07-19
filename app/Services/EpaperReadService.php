<?php

namespace App\Services;

use App\Models\EpaperEdition;
use App\Models\EpaperRead;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EpaperReadService
{
    /** @return array{liked: bool, likes_count: int, readers_count: int, views_count: int, is_new_reader: bool} */
    public function record(EpaperEdition $edition, Request $request): array
    {
        $articleReads = app(ArticleReadService::class);
        $readerKey = $articleReads->readerKey($request);
        $userId = $request->user()?->id;
        $now = now();

        $read = EpaperRead::query()->firstOrCreate(
            [
                'epaper_edition_id' => $edition->id,
                'reader_key' => $readerKey,
            ],
            [
                'user_id' => $userId,
                'first_read_at' => $now,
                'last_read_at' => $now,
            ],
        );

        $isNewReader = $read->wasRecentlyCreated;

        if (! $isNewReader) {
            $read->forceFill([
                'last_read_at' => $now,
                'user_id' => $userId ?? $read->user_id,
            ])->save();
        }

        // Public "readers" counter bumps on every open (from the admin base upward).
        $edition->increment('readers_count');
        $edition->increment('views_count');
        $edition->refresh();

        return array_merge($this->engagementCounts($edition, $request), [
            'is_new_reader' => $isNewReader,
        ]);
    }

    /** @return array{liked: bool, likes_count: int, readers_count: int, views_count: int} */
    public function toggleLike(EpaperEdition $edition, Request $request): array
    {
        $articleReads = app(ArticleReadService::class);
        $readerKey = $articleReads->readerKey($request);
        $userId = $request->user()?->id;
        $now = now();

        $read = EpaperRead::query()->firstOrCreate(
            [
                'epaper_edition_id' => $edition->id,
                'reader_key' => $readerKey,
            ],
            [
                'user_id' => $userId,
                'first_read_at' => $now,
                'last_read_at' => $now,
            ],
        );

        $isNewReader = $read->wasRecentlyCreated;

        if ($isNewReader) {
            $edition->increment('readers_count');
        } else {
            $read->forceFill([
                'last_read_at' => $now,
                'user_id' => $userId ?? $read->user_id,
            ])->save();
        }

        if ($read->liked_at !== null) {
            $read->forceFill(['liked_at' => null])->save();
            $edition->decrement('likes_count');
            $liked = false;
        } else {
            $read->forceFill(['liked_at' => $now])->save();
            $edition->increment('likes_count');
            $liked = true;
        }

        $edition->refresh();

        return array_merge($this->engagementCounts($edition, $request), [
            'liked' => $liked,
        ]);
    }

    public function readerHasLiked(EpaperEdition $edition, Request $request): bool
    {
        $readerKey = $request->cookie(ArticleReadService::READER_COOKIE);

        if (! is_string($readerKey) || $readerKey === '') {
            return false;
        }

        return EpaperRead::query()
            ->where('epaper_edition_id', $edition->id)
            ->where('reader_key', $readerKey)
            ->whereNotNull('liked_at')
            ->exists();
    }

    /** @return array{liked: bool, likes_count: int, readers_count: int, views_count: int} */
    public function engagementCounts(EpaperEdition $edition, Request $request): array
    {
        return [
            'liked' => $this->readerHasLiked($edition, $request),
            'likes_count' => (int) $edition->likes_count,
            'readers_count' => (int) $edition->readers_count,
            'views_count' => (int) $edition->views_count,
        ];
    }

    public function attachReaderCookie(Request $request, Response $response): Response
    {
        return app(ArticleReadService::class)->attachReaderCookie($request, $response);
    }
}
