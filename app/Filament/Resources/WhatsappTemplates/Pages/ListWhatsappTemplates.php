<?php

namespace App\Filament\Resources\WhatsappTemplates\Pages;

use App\Filament\Resources\WhatsappTemplates\WhatsappTemplateResource;
use App\Services\WhatsAppTemplateCatalogService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListWhatsappTemplates extends ListRecords
{
    protected static string $resource = WhatsappTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncFromMeta')
                ->label('Sync from Meta')
                ->icon('heroicon-o-arrow-path')
                ->action(function (WhatsAppTemplateCatalogService $catalog): void {
                    $result = $catalog->syncFromMeta();

                    if (! $result['ok']) {
                        Notification::make()
                            ->title('Template sync failed')
                            ->body($result['error'] ?: 'Check Access token + WABA in WhatsApp API settings.')
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Templates synced')
                        ->body($result['approved'].' approved / '.$result['count'].' total')
                        ->success()
                        ->send();
                }),
            CreateAction::make()->label('Submit new template'),
        ];
    }
}
