<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TeamPerformanceWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Editor, UserRole::Admin], true);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Reporter performance')
            ->query($this->reporterQuery())
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('published_articles_count')->label('Published news')->sortable(),
                TextColumn::make('pending_articles_count')->label('Pending news')->sortable(),
                TextColumn::make('published_videos_count')->label('Published videos')->sortable(),
            ])
            ->defaultSort('published_articles_count', 'desc')
            ->paginated([10]);
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
            ]);
    }
}
