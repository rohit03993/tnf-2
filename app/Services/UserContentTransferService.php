<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Models\Article;
use App\Models\EpaperEdition;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserContentTransferService
{
    /** @return array{articles: int, published_articles: int, videos: int, epaper: int} */
    public static function contentCounts(User $user): array
    {
        return [
            'articles' => $user->articles()->count(),
            'published_articles' => $user->articles()->where('status', ContentStatus::Published)->count(),
            'videos' => $user->videos()->count(),
            'epaper' => $user->epaperEditions()->count(),
        ];
    }

    public static function hasTransferableContent(User $user): bool
    {
        $counts = static::contentCounts($user);

        return ($counts['articles'] + $counts['videos'] + $counts['epaper']) > 0;
    }

    public static function transferContent(User $from, User $to): void
    {
        if ($from->id === $to->id) {
            throw ValidationException::withMessages([
                'transfer_to' => 'Choose a different staff member to receive this content.',
            ]);
        }

        DB::transaction(function () use ($from, $to): void {
            Article::query()
                ->where('author_id', $from->id)
                ->update(['author_id' => $to->id]);

            Video::query()
                ->where('author_id', $from->id)
                ->update(['author_id' => $to->id]);

            EpaperEdition::query()
                ->where('author_id', $from->id)
                ->update(['author_id' => $to->id]);
        });
    }

    public static function delete(User $user, ?int $transferToUserId): void
    {
        if (static::hasTransferableContent($user)) {
            if (! $transferToUserId) {
                throw ValidationException::withMessages([
                    'transfer_to' => 'Select a staff member to transfer news and other content to.',
                ]);
            }

            $target = User::query()->find($transferToUserId);

            if (! $target) {
                throw ValidationException::withMessages([
                    'transfer_to' => 'The selected staff member could not be found.',
                ]);
            }

            static::transferContent($user, $target);
        }

        $user->delete();
    }

    public static function transferTargetOptions(User $record): array
    {
        return User::query()
            ->whereKeyNot($record->id)
            ->whereIn('role', [
                \App\Enums\UserRole::Admin,
                \App\Enums\UserRole::Editor,
                \App\Enums\UserRole::Author,
            ])
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function deleteModalDescription(User $user): string
    {
        if (! static::hasTransferableContent($user)) {
            return 'This will permanently remove this user account. This action cannot be undone.';
        }

        $counts = static::contentCounts($user);
        $parts = [];

        if ($counts['published_articles'] > 0) {
            $parts[] = $counts['published_articles'].' published news';
        }

        $otherArticles = $counts['articles'] - $counts['published_articles'];

        if ($otherArticles > 0) {
            $parts[] = $otherArticles.' draft/pending news';
        }

        if ($counts['videos'] > 0) {
            $parts[] = $counts['videos'].' video'.($counts['videos'] === 1 ? '' : 's');
        }

        if ($counts['epaper'] > 0) {
            $parts[] = $counts['epaper'].' ePaper edition'.($counts['epaper'] === 1 ? '' : 's');
        }

        $summary = implode(', ', $parts);

        return "This user owns {$summary}. Choose another staff member to transfer this content to before deleting the account.";
    }
}
