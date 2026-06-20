<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Category;
use App\Models\EpaperEdition;
use App\Models\Submission;
use App\Models\Video;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        $isAuthorOnly = $user?->role === UserRole::Author;

        $articleQuery = Article::query();
        $videoQuery = Video::query();

        if ($isAuthorOnly) {
            $articleQuery->where('author_id', $user->id);
            $videoQuery->where('author_id', $user->id);
        }

        $stats = [
            Stat::make('News published', $articleQuery->clone()->where('status', ContentStatus::Published)->count()),
            Stat::make('Videos published', $videoQuery->clone()->where('status', ContentStatus::Published)->count()),
        ];

        if (! $isAuthorOnly) {
            $stats[] = Stat::make(
                'ePaper editions',
                EpaperEdition::query()->where('status', ContentStatus::Published)->count()
            );
        }

        if (! $isAuthorOnly) {
            $stats[] = Stat::make('News pending review', $articleQuery->clone()->where('status', ContentStatus::Pending)->count())
                ->color('warning');
            $stats[] = Stat::make('Member submissions', Submission::query()->where('status', SubmissionStatus::Pending)->count())
                ->color('warning');
            $stats[] = Stat::make('Categories', Category::query()->count());
        } elseif ($user?->requires_approval) {
            $stats[] = Stat::make('Awaiting approval', $articleQuery->clone()->where('status', ContentStatus::Pending)->count())
                ->color('warning');
        }

        return $stats;
    }
}
