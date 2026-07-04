<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ArticleReadService
{
    public const READER_COOKIE = 'tnf_reader';

    public const READER_COOKIE_MINUTES = 525600;

    /** @return array{readers_count: int, views_count: int, is_new_reader: bool} */
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

        if ($isNewReader) {
            $article->increment('readers_count');
        }

        $article->increment('views_count');
        $article->refresh();

        return [
            'readers_count' => (int) $article->readers_count,
            'views_count' => (int) $article->views_count,
            'is_new_reader' => $isNewReader,
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
