<?php

namespace App\Filament\Resources\WhatsappCampaigns\Pages;

use App\Enums\WhatsAppAudienceType;
use App\Filament\Resources\WhatsappCampaigns\WhatsappCampaignResource;
use App\Services\WhatsAppCampaignService;
use App\Support\WhatsAppCampaignFormHelper;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class CreateWhatsappCampaign extends CreateRecord
{
    protected static string $resource = WhatsappCampaignResource::class;

    protected static ?string $title = 'New WhatsApp campaign';

    public function mount(): void
    {
        parent::mount();

        $this->form->fill(array_merge($this->form->getState(), [
            'name' => WhatsAppCampaignFormHelper::generateDefaultName(),
            'send_immediately' => true,
            'audience_type' => WhatsAppAudienceType::OptedIn->value,
        ]));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['send_immediately']);

        $variables = $data['campaign_variables'] ?? [];
        $manual = collect($data['template_manual_params'] ?? [])
            ->filter(fn ($value): bool => filled($value))
            ->all();

        if ($manual !== []) {
            $variables['_manual'] = array_values($manual);
        }

        if (filled($variables['date'] ?? null)) {
            $variables['date'] = Carbon::parse($variables['date'])->format('d M Y');
        }

        if (filled($variables['time'] ?? null)) {
            $variables['time'] = Carbon::parse($variables['time'])->format('g:i A');
        }

        $data['campaign_variables'] = $variables === [] ? null : $variables;
        unset($data['template_manual_params']);

        if (blank($data['name'] ?? null)) {
            $data['name'] = WhatsAppCampaignFormHelper::generateDefaultName();
        }

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
