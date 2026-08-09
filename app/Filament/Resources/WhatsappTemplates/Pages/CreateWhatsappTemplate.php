<?php

namespace App\Filament\Resources\WhatsappTemplates\Pages;

use App\Filament\Resources\WhatsappTemplates\WhatsappTemplateResource;
use App\Services\WhatsAppTemplateCatalogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Filament\Resources\Pages\CreateRecord;

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
        ]);

        if (! $result['ok'] || ! $result['template']) {
            throw ValidationException::withMessages([
                'body_text' => $result['error'] ?: 'Meta rejected the template.',
            ]);
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
