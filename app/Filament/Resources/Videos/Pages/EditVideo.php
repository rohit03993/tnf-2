<?php

namespace App\Filament\Resources\Videos\Pages;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Filament\Concerns\SyncsFeaturedUpload;
use App\Filament\Resources\Videos\VideoResource;
use App\Notifications\ReporterContentPublishedNotification;
use App\Services\ContentPublishingGuard;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVideo extends EditRecord
{
    use SyncsFeaturedUpload;

    protected static string $resource = VideoResource::class;

    protected function featuredUploadField(): string
    {
        return 'featured_upload';
    }

    protected function getHeaderActions(): array
    {
        $actions = [DeleteAction::make()];
        $user = auth()->user();

        if ($user && in_array($user->role, [UserRole::Editor, UserRole::Admin], true)) {
            $actions[] = Action::make('approve')
                ->label('Approve & publish')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn () => $this->record->status === ContentStatus::Pending)
                ->requiresConfirmation()
                ->action(function (): void {
                    if ($this->record->categories()->count() === 0) {
                        Notification::make()
                            ->title('Category required')
                            ->body('Assign at least one category before approving this video.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->record->update([
                        'status' => ContentStatus::Published,
                        'published_at' => $this->record->published_at ?? now(),
                    ]);

                    $this->notifyReporterIfNeeded();

                    $this->refreshFormData(['status', 'published_at']);
                });
        }

        return $actions;
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->load('featuredMedia');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->mutateFeaturedUploadBeforeFill($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        if ($user) {
            $data = ContentPublishingGuard::enforce($user, $data);
        }

        return $this->stripFeaturedUploadFromSave($data);
    }

    protected function afterSave(): void
    {
        $this->syncFeaturedUpload($this->record->title);
    }

    protected function notifyReporterIfNeeded(): void
    {
        $author = $this->record->author;

        if (! $author?->isReporter()) {
            return;
        }

        $author->notify(new ReporterContentPublishedNotification(
            title: $this->record->title,
            url: route('videos.show', $this->record->slug),
            contentType: 'video',
        ));
    }
}
