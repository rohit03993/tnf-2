<?php

namespace App\Filament\Concerns;

use App\Models\Media;
use App\Support\TnfImageUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

trait SyncsFeaturedUpload
{
    abstract protected function featuredUploadField(): string;

    protected function mutateFeaturedUploadBeforeFill(array $data): array
    {
        if ($this->record->featuredMedia?->path) {
            $data[$this->featuredUploadField()] = [$this->record->featuredMedia->path];
        }

        return $data;
    }

    protected function stripFeaturedUploadFromSave(array $data): array
    {
        unset($data[$this->featuredUploadField()]);

        return $data;
    }

    protected function syncFeaturedUpload(?string $alt = null): void
    {
        $field = $this->featuredUploadField();
        $upload = $this->featuredUploadPath();

        if (! $upload) {
            if ($this->record->featured_media_id) {
                $this->deleteFeaturedUploadMedia($this->record->featuredMedia);
                $this->record->update(['featured_media_id' => null]);
            }

            return;
        }

        if ($this->record->featuredMedia?->path === $upload) {
            return;
        }

        if (! Storage::disk('public')->exists($upload)) {
            return;
        }

        if (! TnfImageUpload::storedFileWithinLimit('public', $upload)) {
            Storage::disk('public')->delete($upload);

            throw ValidationException::withMessages([
                $field => TnfImageUpload::validationMessage(),
            ]);
        }

        $oldMedia = $this->record->featuredMedia;

        $media = Media::query()->create([
            'disk' => 'public',
            'path' => $upload,
            'mime' => Storage::disk('public')->mimeType($upload),
            'size' => Storage::disk('public')->size($upload),
            'alt' => $alt ?? $this->record->title,
        ]);

        $this->record->update(['featured_media_id' => $media->id]);

        if ($oldMedia && $oldMedia->id !== $media->id) {
            $this->deleteFeaturedUploadMedia($oldMedia);
        }
    }

    protected function featuredUploadPath(): ?string
    {
        $field = $this->featuredUploadField();

        $component = $this->form->getComponent($field);
        $component?->saveUploadedFiles();

        $upload = data_get($this->form->getRawState(), $field);

        if (is_array($upload)) {
            $upload = $upload[array_key_first($upload)] ?? null;
        }

        if (! is_string($upload) || $upload === '') {
            return null;
        }

        return $upload;
    }

    protected function deleteFeaturedUploadMedia(?Media $media): void
    {
        if (! $media) {
            return;
        }

        if (Storage::disk($media->disk)->exists($media->path)) {
            Storage::disk($media->disk)->delete($media->path);
        }

        $media->delete();
    }
}
