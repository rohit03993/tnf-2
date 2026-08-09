<?php

namespace App\Filament\Resources\WhatsappCampaigns\Pages;

use App\Filament\Resources\WhatsappCampaigns\WhatsappCampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhatsappCampaigns extends ListRecords
{
    protected static string $resource = WhatsappCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New campaign'),
        ];
    }
}
