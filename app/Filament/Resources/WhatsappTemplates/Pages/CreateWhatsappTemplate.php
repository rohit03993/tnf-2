<?php

namespace App\Filament\Resources\WhatsappTemplates\Pages;

use App\Filament\Resources\WhatsappTemplates\WhatsappTemplateResource;
use App\Services\WhatsAppTemplateCatalogService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class CreateWhatsappTemplate extends CreateRecord
{
    protected static string $resource = WhatsappTemplateResource::class;

    protected static ?string $title = 'Submit template to Meta';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return WhatsappTemplateResource::normalizeCreateFormData($data);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $result = app(WhatsAppTemplateCatalogService::class)->submitToMeta([
            'name' => (string) ($data['name'] ?? ''),
            'language' => (string) ($data['language'] ?? 'en'),
            'category' => (string) ($data['category'] ?? 'UTILITY'),
            'body_text' => (string) ($data['body_text'] ?? ''),
            'header_text' => $data['header_text'] ?? null,
            'footer_text' => $data['footer_text'] ?? null,
            'body_examples' => $data['body_examples'] ?? null,
            'allow_category_change' => (bool) ($data['allow_category_change'] ?? true),
            'code_expiration_minutes' => (int) ($data['code_expiration_minutes'] ?? 10),
        ]);

        if (! $result['ok'] || ! $result['template']) {
            Notification::make()
                ->title('Meta rejected the template')
                ->body($result['error'] ?: 'Check Access token, WABA, and template fields, then try again.')
                ->danger()
                ->persistent()
                ->send();

            throw new Halt;
        }

        return $result['template'];
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Template submitted to Meta';
    }

    protected function getRedirectUrl(): string
    {
        return WhatsappTemplateResource::getUrl('index');
    }
}
