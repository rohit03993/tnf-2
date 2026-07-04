<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TeamPerformanceWidget extends Widget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.team-performance-widget';

    public static function canView(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Editor, UserRole::Admin], true);
    }

    /** @return Collection<int, User> */
    public function getReporters(): Collection
    {
        return $this->reporterQuery()->limit(10)->get();
    }

    /** @return Builder<User> */
    protected function reporterQuery(): Builder
    {
        return User::query()
            ->where('role', UserRole::Author)
            ->withCount([
                'articles as published_articles_count' => fn (Builder $query) => $query->where('status', ContentStatus::Published),
                'articles as pending_articles_count' => fn (Builder $query) => $query->where('status', ContentStatus::Pending),
                'videos as published_videos_count' => fn (Builder $query) => $query->where('status', ContentStatus::Published),
            ])
            ->orderByDesc('published_articles_count');
    }
}
