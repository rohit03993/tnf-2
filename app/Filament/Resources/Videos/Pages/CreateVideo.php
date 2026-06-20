<?php

namespace App\Filament\Resources\Videos\Pages;

use App\Filament\Concerns\SyncsFeaturedUpload;
use App\Filament\Resources\Videos\VideoResource;
use App\Services\ContentPublishingGuard;
use Filament\Resources\Pages\CreateRecord;

class CreateVideo extends CreateRecord
{
    use SyncsFeaturedUpload;

    protected static string $resource = VideoResource::class;

    protected function featuredUploadField(): string
    {
        return 'featured_upload';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user) {
            $data = ContentPublishingGuard::enforce($user, $data);
        }

        return $this->stripFeaturedUploadFromSave($data);
    }

    protected function afterCreate(): void
    {
        $this->syncFeaturedUpload($this->record->title);
    }
}
