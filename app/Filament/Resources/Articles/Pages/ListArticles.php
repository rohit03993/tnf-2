<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        $user = auth()->user();

        if (! $user || $user->role === UserRole::Author) {
            return [];
        }

        return [
            'all' => Tab::make('All'),
            'pending' => Tab::make('Pending review')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ContentStatus::Pending))
                ->badge(function (): ?string {
                    $count = Article::query()->where('status', ContentStatus::Pending)->count();

                    return $count > 0 ? (string) $count : null;
                }),
        ];
    }
}
