<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditMedia extends EditRecord
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open')
                ->label('Open file')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn () => $this->record->url(), shouldOpenInNewTab: true)
                ->visible(fn () => filled($this->record->url())),
            DeleteAction::make()
                ->label('Delete file')
                ->action(function (): void {
                    MediaResource::deleteMedia($this->record);
                    $this->redirect(MediaResource::getUrl());
                }),
        ];
    }
}
