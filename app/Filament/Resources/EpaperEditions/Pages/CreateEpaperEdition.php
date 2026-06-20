<?php

namespace App\Filament\Resources\EpaperEditions\Pages;

use App\Enums\ContentStatus;
use App\Filament\Concerns\SyncsFeaturedUpload;
use App\Filament\Resources\EpaperEditions\EpaperEditionResource;
use App\Services\PdfProcessingService;
use Filament\Resources\Pages\CreateRecord;

class CreateEpaperEdition extends CreateRecord
{
    use SyncsFeaturedUpload;

    protected static string $resource = EpaperEditionResource::class;

    protected function featuredUploadField(): string
    {
        return 'cover_upload';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (EpaperEditionResource::statusIsPublished($data['status'] ?? null) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $data['pdf_path'] = $data['pdf_file'] ?? null;
        unset($data['pdf_file'], $data['cover_upload']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncFeaturedUpload();

        if ($this->record->pdf_path) {
            app(PdfProcessingService::class)->process($this->record->fresh());
        }
    }
}
