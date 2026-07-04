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
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | array | null $columns = [
        'default' => 2,
        'sm' => 2,
        'md' => 3,
        'lg' => 3,
        'xl' => 3,
    ];

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
            Stat::make('News published', $articleQuery->clone()->where('status', ContentStatus::Published)->count())
                ->icon(Heroicon::OutlinedNewspaper),
            Stat::make('Videos published', $videoQuery->clone()->where('status', ContentStatus::Published)->count())
                ->icon(Heroicon::OutlinedVideoCamera),
        ];

        if (! $isAuthorOnly) {
            $stats[] = Stat::make(
                'ePaper editions',
                EpaperEdition::query()->where('status', ContentStatus::Published)->count()
            )->icon(Heroicon::OutlinedDocumentText);
        }

        if (! $isAuthorOnly) {
            $stats[] = Stat::make('News pending review', $articleQuery->clone()->where('status', ContentStatus::Pending)->count())
                ->color('warning')
                ->icon(Heroicon::OutlinedClock);
            $stats[] = Stat::make('Member submissions', Submission::query()->where('status', SubmissionStatus::Pending)->count())
                ->color('warning')
                ->icon(Heroicon::OutlinedInboxArrowDown);
            $stats[] = Stat::make('Categories', Category::query()->count())
                ->icon(Heroicon::OutlinedTag);
        } elseif ($user?->requires_approval) {
            $stats[] = Stat::make('Awaiting approval', $articleQuery->clone()->where('status', ContentStatus::Pending)->count())
                ->color('warning')
                ->icon(Heroicon::OutlinedClock);
        }

        return $stats;
    }
}
