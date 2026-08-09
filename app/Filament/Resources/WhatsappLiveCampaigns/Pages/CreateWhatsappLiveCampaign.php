<?php

namespace App\Filament\Resources\WhatsappLiveCampaigns\Pages;

use App\Filament\Resources\WhatsappLiveCampaigns\WhatsappLiveCampaignResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWhatsappLiveCampaign extends CreateRecord
{
    protected static string $resource = WhatsappLiveCampaignResource::class;

    protected static ?string $title = 'New live campaign';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? 'draft';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return WhatsappLiveCampaignResource::getUrl('edit', ['record' => $this->record]);
    }
}
