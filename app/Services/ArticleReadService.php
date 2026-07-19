<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleRead;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ArticleReadService
{
    public const READER_COOKIE = 'tnf_reader';

    public const READER_COOKIE_MINUTES = 525600;

    /** @return array{liked: bool, likes_count: int, readers_count: int, views_count: int, is_new_reader: bool} */
    public function record(Article $article, Request $request): array
    {
        $readerKey = $this->readerKey($request);
        $userId = $request->user()?->id;
        $now = now();

        $read = ArticleRead::query()->firstOrCreate(
            [
                'article_id' => $article->id,
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
        $article->increment('readers_count');
        $article->increment('views_count');
        $article->refresh();

        return array_merge($this->engagementCounts($article, $request), [
            'is_new_reader' => $isNewReader,
        ]);
    }

    /** @return array{liked: bool, likes_count: int, readers_count: int, views_count: int} */
    public function toggleLike(Article $article, Request $request): array
    {
        $readerKey = $this->readerKey($request);
        $userId = $request->user()?->id;
        $now = now();

        $read = ArticleRead::query()->firstOrCreate(
            [
                'article_id' => $article->id,
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
            // Reader row created via like before a page view — keep counts in sync.
            $article->increment('readers_count');
        } else {
            $read->forceFill([
                'last_read_at' => $now,
                'user_id' => $userId ?? $read->user_id,
            ])->save();
        }

        if ($read->liked_at !== null) {
            $read->forceFill(['liked_at' => null])->save();
            $article->decrement('likes_count');
            $liked = false;
        } else {
            $read->forceFill(['liked_at' => $now])->save();
            $article->increment('likes_count');
            $liked = true;
        }

        $article->refresh();

        return array_merge($this->engagementCounts($article, $request), [
            'liked' => $liked,
        ]);
    }

    public function readerHasLiked(Article $article, Request $request): bool
    {
        $readerKey = $request->cookie(self::READER_COOKIE);

        if (! is_string($readerKey) || $readerKey === '') {
            return false;
        }

        return ArticleRead::query()
            ->where('article_id', $article->id)
            ->where('reader_key', $readerKey)
            ->whereNotNull('liked_at')
            ->exists();
    }

    /** @return array{liked: bool, likes_count: int, readers_count: int, views_count: int} */
    public function engagementCounts(Article $article, Request $request): array
    {
        return [
            'liked' => $this->readerHasLiked($article, $request),
            'likes_count' => (int) $article->likes_count,
            'readers_count' => (int) $article->readers_count,
            'views_count' => (int) $article->views_count,
        ];
    }

    public function attachReaderCookie(Request $request, Response $response, ?string $readerKey = null): Response
    {
        $readerKey ??= $this->readerKey($request);

        if ($request->cookie(self::READER_COOKIE) === $readerKey) {
            return $response;
        }

        return $response->withCookie(cookie(
            self::READER_COOKIE,
            $readerKey,
            self::READER_COOKIE_MINUTES,
            '/',
            null,
            $request->secure(),
            true,
            false,
            'lax',
        ));
    }

    public function readerKey(Request $request): string
    {
        $existing = $request->cookie(self::READER_COOKIE);

        if (is_string($existing) && $existing !== '' && strlen($existing) <= 64) {
            return $existing;
        }

        return (string) Str::uuid();
    }

    public static function formatCount(int $count): string
    {
        return number_format($count);
    }
}
