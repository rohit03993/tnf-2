<?php

namespace App\Filament\Resources\Submissions\Pages;

use App\Enums\SubmissionStatus;
use App\Filament\Concerns\SyncsFeaturedUpload;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Submissions\SubmissionResource;
use App\Models\Category;
use App\Services\SubmissionWorkflowService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditSubmission extends EditRecord
{
    use SyncsFeaturedUpload;

    protected static string $resource = SubmissionResource::class;

    protected function featuredUploadField(): string
    {
        return 'featured_upload';
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        if ($this->record->status === SubmissionStatus::Pending) {
            $actions[] = Action::make('approve')
                ->label('Approve & publish')
                ->color('success')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->requiresConfirmation()
                ->modalDescription('Save your edits and publish this story on the public site.')
                ->form([
                    Select::make('categories')
                        ->label('Categories')
                        ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id'))
                        ->multiple()
                        ->required()
                        ->searchable()
                        ->default(fn () => $this->record->category_id ? [$this->record->category_id] : [])
                        ->helperText('Adjust categories if needed before publishing.'),
                ])
                ->action(function (array $data): void {
                    $this->save();

                    $article = SubmissionWorkflowService::approve(
                        $this->record->fresh(['featuredMedia']),
                        auth()->user(),
                        $data['categories'] ?? [],
                    );

                    Notification::make()
                        ->title('Submission approved and published')
                        ->success()
                        ->send();

                    $this->redirect(ArticleResource::getUrl('edit', ['record' => $article]));
                });

            $actions[] = Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon(Heroicon::OutlinedXCircle)
                ->form([
                    Textarea::make('rejection_reason')->label('Reason for member')->rows(3),
                ])
                ->action(function (array $data): void {
                    SubmissionWorkflowService::reject($this->record, $data['rejection_reason'] ?? null);

                    Notification::make()->title('Submission rejected')->warning()->send();

                    $this->redirect(SubmissionResource::getUrl('index'));
                });
        }

        if (filled($this->record->promoted_article_id)) {
            $actions[] = Action::make('view_article')
                ->label('View published article')
                ->icon(Heroicon::OutlinedNewspaper)
                ->url(ArticleResource::getUrl('edit', ['record' => $this->record->promoted_article_id]));
        }

        return $actions;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->mutateFeaturedUploadBeforeFill($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->stripFeaturedUploadFromSave($data);
    }

    protected function afterSave(): void
    {
        $this->syncFeaturedUpload($this->record->title);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        if ($this->record->status === SubmissionStatus::Pending) {
            return 'Submission review saved';
        }

        return null;
    }

    public function getTitle(): string
    {
        if ($this->record->status === SubmissionStatus::Pending) {
            return 'Review submission';
        }

        return 'View submission';
    }

    protected function getFormActions(): array
    {
        if ($this->record->status !== SubmissionStatus::Pending) {
            return [];
        }

        return parent::getFormActions();
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->load(['user', 'category', 'featuredMedia']);
    }
}
