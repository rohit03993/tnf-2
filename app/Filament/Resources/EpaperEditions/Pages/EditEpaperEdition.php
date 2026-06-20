<?php

namespace App\Filament\Resources\EpaperEditions\Pages;

use App\Filament\Concerns\SyncsFeaturedUpload;
use App\Filament\Resources\EpaperEditions\EpaperEditionResource;
use App\Services\PdfProcessingService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditEpaperEdition extends EditRecord
{
    use SyncsFeaturedUpload;

    protected static string $resource = EpaperEditionResource::class;

    protected function featuredUploadField(): string
    {
        return 'cover_upload';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reprocess_pdf')
                ->label('Reprocess PDF')
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->visible(fn () => filled($this->record->pdf_path))
                ->action(fn () => app(PdfProcessingService::class)->process($this->record)),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['pdf_file'] = $this->record->pdf_path;

        return $this->mutateFeaturedUploadBeforeFill($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (EpaperEditionResource::statusIsPublished($data['status'] ?? null) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if (! empty($data['pdf_file'])) {
            $data['pdf_path'] = $data['pdf_file'];
        }

        unset($data['pdf_file']);

        return $this->stripFeaturedUploadFromSave($data);
    }

    protected function afterSave(): void
    {
        $this->syncFeaturedUpload();

        if ($this->record->wasChanged('pdf_path') && $this->record->pdf_path) {
            app(PdfProcessingService::class)->process($this->record->fresh());
        }
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->load('featuredMedia');
    }
}
