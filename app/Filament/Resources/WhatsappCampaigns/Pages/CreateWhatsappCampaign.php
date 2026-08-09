<?php

namespace App\Filament\Resources\WhatsappCampaigns\Pages;

use App\Enums\WhatsAppAudienceType;
use App\Filament\Resources\WhatsappCampaigns\WhatsappCampaignResource;
use App\Services\WhatsAppCampaignService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWhatsappCampaign extends CreateRecord
{
    protected static string $resource = WhatsappCampaignResource::class;

    protected static ?string $title = 'New WhatsApp campaign';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['send_immediately']);

        $variables = $data['campaign_variables'] ?? [];
        $manual = collect($data['template_manual_params'] ?? [])
            ->map(fn ($row) => is_array($row) ? ($row['value'] ?? null) : $row)
            ->filter(fn ($value): bool => filled($value))
            ->values()
            ->all();

        if ($manual !== []) {
            $variables['_manual'] = $manual;
        }

        $data['campaign_variables'] = $variables === [] ? null : $variables;
        unset($data['template_manual_params']);

        $data['audience_type'] = $data['audience_type'] ?? WhatsAppAudienceType::OptedIn->value;

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return app(WhatsAppCampaignService::class)->createCampaign($data, Auth::user());
    }

    protected function afterCreate(): void
    {
        if (! (bool) data_get($this->form->getRawState(), 'send_immediately', true)) {
            return;
        }

        try {
            app(WhatsAppCampaignService::class)->queueCampaign($this->record, Auth::user());
        } catch (\RuntimeException $exception) {
            Notification::make()
                ->title('Campaign saved but not sent')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return WhatsappCampaignResource::getUrl('view', ['record' => $this->record]);
    }
}
