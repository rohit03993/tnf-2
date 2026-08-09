<?php

namespace App\Filament\Resources\WhatsappLiveCampaigns\Pages;

use App\Filament\Resources\WhatsappLiveCampaigns\WhatsappLiveCampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhatsappLiveCampaigns extends ListRecords
{
    protected static string $resource = WhatsappLiveCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New live campaign'),
        ];
    }
}
