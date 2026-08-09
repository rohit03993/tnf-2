<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Concerns\RequestsWhatsAppBroadcast;
use App\Filament\Concerns\SyncsFeaturedUpload;
use App\Filament\Resources\Articles\ArticleResource;
use App\Services\ArticlePublishingGuard;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    use RequestsWhatsAppBroadcast;
    use SyncsFeaturedUpload;

    protected static string $resource = ArticleResource::class;

    protected function featuredUploadField(): string
    {
        return 'featured_upload';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->captureWhatsAppBroadcastFlag();

        $user = auth()->user();

        if ($user) {
            $data = ArticlePublishingGuard::enforce($user, $data);
        }

        return $this->stripFeaturedUploadFromSave($data);
    }

    protected function afterCreate(): void
    {
        $this->syncFeaturedUpload($this->record->title);
    }
}
