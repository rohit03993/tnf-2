<?php

namespace App\Filament\Resources\Videos\Pages;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Videos\VideoResource;
use App\Models\Video;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListVideos extends ListRecords
{
    protected static string $resource = VideoResource::class;

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
                    $count = Video::query()->where('status', ContentStatus::Pending)->count();

                    return $count > 0 ? (string) $count : null;
                }),
        ];
    }
}
