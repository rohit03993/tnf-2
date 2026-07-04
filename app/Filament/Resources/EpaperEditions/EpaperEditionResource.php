<?php

namespace App\Filament\Resources\EpaperEditions;

use App\Enums\ContentStatus;
use App\Enums\PdfStatus;
use App\Enums\UserRole;
use App\Filament\Concerns\ScopesToAuthor;
use App\Filament\Resources\EpaperEditions\Pages\CreateEpaperEdition;
use App\Filament\Resources\EpaperEditions\Pages\EditEpaperEdition;
use App\Filament\Resources\EpaperEditions\Pages\ListEpaperEditions;
use App\Models\EpaperEdition;
use App\Support\TnfImageUpload;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class EpaperEditionResource extends Resource
{
    use ScopesToAuthor;

    protected static ?string $model = EpaperEdition::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'ePaper';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Editor, UserRole::Admin], true);
    }

    public static function statusIsPublished(mixed $status): bool
    {
        if ($status instanceof ContentStatus) {
            return $status === ContentStatus::Published;
        }

        return $status === ContentStatus::Published->value;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Edition details')->schema([
                TextInput::make('title')->required()->live(onBlur: true)
                    ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                RichEditor::make('content')->columnSpanFull(),
                Textarea::make('excerpt')->rows(3)->columnSpanFull(),
                Toggle::make('restricted')->label('Subscriber only'),
            ])->columns(2),
            Section::make('Publishing')->schema([
                Select::make('author_id')->relationship('author', 'name')->required()
                    ->default(fn () => auth()->id())
                    ->disabled(fn () => auth()->user()?->role === UserRole::Author),
                Select::make('status')->options(ContentStatus::class)->required()->live(),
                DateTimePicker::make('published_at')
                    ->label('Publish date')
                    ->default(now())
                    ->required(fn (Get $get): bool => static::statusIsPublished($get('status'))),
                FileUpload::make('pdf_file')->label('PDF file')->acceptedFileTypes(['application/pdf'])
                    ->disk('public')->directory('epaper/pdfs')->required(fn (string $operation) => $operation === 'create'),
            ])->columns(2),
            Section::make('Archive thumbnail')->schema([
                TnfImageUpload::applyTo(
                    FileUpload::make('cover_upload')
                        ->label('Listing cover')
                        ->image()
                        ->disk('public')
                        ->directory('epaper/covers')
                        ->visibility('public')
                        ->dehydrated(false)
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('3:4')
                        ->imageResizeTargetWidth('480')
                        ->imageResizeTargetHeight('640')
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            '3:4',
                        ])
                        ->imageEditorMode(2)
                        ->imageEditorViewportWidth('480')
                        ->imageEditorViewportHeight('640')
                        ->columnSpanFull(),
                    'Auto-cropped to 3:4. Optional: use the pencil on the preview to adjust.'
                ),
            ]),
            Section::make('PDF processing')->schema([
                TextInput::make('pdf_status')->disabled()->dehydrated(false)
                    ->formatStateUsing(fn ($record) => $record?->pdf_status?->value ?? PdfStatus::Idle->value),
                TextInput::make('pdf_job_id')->disabled(),
                Textarea::make('pdf_error')->disabled()->rows(2),
            ])->columns(2)->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featuredMedia.path')
                    ->label('Cover')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(fn () => null)
                    ->toggleable(),
                TextColumn::make('title')->searchable()->limit(40),
                IconColumn::make('restricted')->boolean()->label('Premium'),
                TextColumn::make('pdf_status')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('published_at')->dateTime()->sortable(),
            ])
            ->defaultSort('published_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEpaperEditions::route('/'),
            'create' => CreateEpaperEdition::route('/create'),
            'edit' => EditEpaperEdition::route('/{record}/edit'),
        ];
    }
}
