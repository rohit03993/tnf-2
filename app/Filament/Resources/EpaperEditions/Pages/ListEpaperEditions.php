<?php

namespace App\Filament\Resources\EpaperEditions\Pages;

use App\Filament\Resources\EpaperEditions\EpaperEditionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEpaperEditions extends ListRecords
{
    protected static string $resource = EpaperEditionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
