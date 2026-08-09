<?php

namespace App\Filament\Resources\WhatsappTemplates\Pages;

use App\Filament\Resources\WhatsappTemplates\WhatsappTemplateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewWhatsappTemplate extends ViewRecord
{
    protected static string $resource = WhatsappTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to list')
                ->url(WhatsappTemplateResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
