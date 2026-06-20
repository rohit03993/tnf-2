<?php

namespace App\Filament\Resources\Submissions;

use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Submissions\Pages\EditSubmission;
use App\Filament\Resources\Submissions\Pages\ListSubmissions;
use App\Models\Submission;
use App\Support\TnfImageUpload;
use App\Services\SubmissionWorkflowService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $navigationLabel = 'Member Submissions';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Editor, UserRole::Admin], true);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'category', 'featuredMedia']);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Submission::query()->where('status', SubmissionStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Member details')->schema([
                Placeholder::make('member')
                    ->label('Member')
                    ->content(function (?Submission $record): string {
                        if (! $record?->user) {
                            return '—';
                        }

                        $name = trim((string) $record->user->name);

                        return $name !== '' ? $name : $record->user->email;
                    }),
                TextInput::make('created_at')
                    ->label('Submitted at')
                    ->formatStateUsing(fn (?Submission $record) => $record?->created_at?->format('M j, Y g:i A'))
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('status')
                    ->formatStateUsing(fn (?Submission $record) => $record?->displayStatus())
                    ->disabled()
                    ->dehydrated(false),
            ])->columns(3),

            Section::make('Story review')
                ->description(fn (?Submission $record) => $record?->status === SubmissionStatus::Pending
                    ? 'Read the full story, edit if needed, then approve and publish.'
                    : 'Submitted content (read-only).')
                ->schema([
                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255)
                        ->disabled(fn (?Submission $record) => $record && $record->status !== SubmissionStatus::Pending),
                    Select::make('category_id')
                        ->label('Category')
                        ->relationship('category', 'name', fn ($query) => $query->orderBy('name'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn (?Submission $record) => $record && $record->status !== SubmissionStatus::Pending),
                    RichEditor::make('content')
                        ->label('Story')
                        ->required()
                        ->columnSpanFull()
                        ->disabled(fn (?Submission $record) => $record && $record->status !== SubmissionStatus::Pending),
                    TextInput::make('embed_url')
                        ->label('Video URL')
                        ->url()
                        ->columnSpanFull()
                        ->disabled(fn (?Submission $record) => $record && $record->status !== SubmissionStatus::Pending),
                ])->columns(2),

            Section::make('Featured image')
                ->description(fn (?Submission $record) => $record?->status === SubmissionStatus::Pending
                    ? 'Upload, replace, or remove the member image before publishing.'
                    : null)
                ->schema([
                    TnfImageUpload::applyTo(
                        FileUpload::make('featured_upload')
                            ->label('Image')
                            ->image()
                            ->disk('public')
                            ->directory('submissions')
                            ->dehydrated(false)
                    )->visible(fn (?Submission $record) => $record?->status === SubmissionStatus::Pending),
                    Placeholder::make('featured_preview')
                        ->label('Uploaded image')
                        ->content(function (?Submission $record): HtmlString|string {
                            $url = $record?->featuredMedia?->url();

                            if (! $url) {
                                return 'No image uploaded.';
                            }

                            return new HtmlString(
                                '<img src="'.e($url).'" alt="Submission image" class="max-h-64 rounded-lg border border-gray-200">'
                            );
                        })
                        ->visible(fn (?Submission $record) => $record?->status !== SubmissionStatus::Pending),
                ]),

            Section::make('Rejection details')
                ->schema([
                    Textarea::make('rejection_reason')
                        ->label('Reason sent to member')
                        ->disabled()
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->visible(fn (?Submission $record) => $record?->status === SubmissionStatus::Rejected),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->limit(40)
                    ->searchable()
                    ->url(fn (Submission $record) => static::getUrl('edit', ['record' => $record])),
                TextColumn::make('category.name')->label('Category')->badge(),
                TextColumn::make('user.name')->label('Member'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (Submission $record): string => $record->displayStatus()),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(SubmissionStatus::class),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('Review')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Submission $record) => static::getUrl('edit', ['record' => $record])),
                Action::make('reject')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (Submission $record) => $record->status === SubmissionStatus::Pending)
                    ->form([
                        Textarea::make('rejection_reason')->label('Reason')->rows(3),
                    ])
                    ->action(function (Submission $record, array $data) {
                        SubmissionWorkflowService::reject($record, $data['rejection_reason'] ?? null);
                        Notification::make()->title('Submission rejected')->warning()->send();
                    }),
                Action::make('view_article')
                    ->label('View article')
                    ->icon(Heroicon::OutlinedNewspaper)
                    ->url(fn (Submission $record) => $record->promoted_article_id
                        ? ArticleResource::getUrl('edit', ['record' => $record->promoted_article_id])
                        : null)
                    ->visible(fn (Submission $record) => filled($record->promoted_article_id)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubmissions::route('/'),
            'edit' => EditSubmission::route('/{record}/edit'),
        ];
    }
}
