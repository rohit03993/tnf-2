<?php

namespace App\Filament\Resources\Videos;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Filament\Concerns\ReporterPublishing;
use App\Filament\Concerns\ScopesToAuthor;
use App\Filament\Resources\Videos\Pages\CreateVideo;
use App\Filament\Resources\Videos\Pages\EditVideo;
use App\Filament\Resources\Videos\Pages\ListVideos;
use App\Models\Video;
use App\Support\TnfImageUpload;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class VideoResource extends Resource
{
    use ReporterPublishing;
    use ScopesToAuthor;

    protected static ?string $model = Video::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user || $user->isReporter()) {
            return null;
        }

        $count = Video::query()->where('status', ContentStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Video details')->schema([
                TextInput::make('title')->required()->live(onBlur: true)
                    ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                static::categorySelect(),
                TextInput::make('embed_url')
                    ->label('YouTube / Shorts URL')
                    ->required()
                    ->url()
                    ->helperText('Paste a YouTube or Shorts link — the thumbnail is picked automatically. Upload a custom thumbnail only if you need to override it.')
                    ->columnSpanFull(),
                RichEditor::make('content')->columnSpanFull(),
                Textarea::make('excerpt')->rows(3)->columnSpanFull(),
            ])->columns(2),
            Section::make('Publishing')->schema([
                Select::make('author_id')->relationship('author', 'name')->required()
                    ->default(fn () => auth()->id())
                    ->disabled(fn () => auth()->user()?->role === UserRole::Author),
                Select::make('status')
                    ->options(fn () => static::statusOptionsFor(auth()->user()))
                    ->required()
                    ->default(ContentStatus::Draft)
                    ->live()
                    ->helperText(fn () => static::statusHelperFor(auth()->user())),
                DateTimePicker::make('published_at')
                    ->visible(fn () => ! auth()->user()?->isReporter() || auth()->user()?->canSelfPublishArticles())
                    ->helperText('Leave empty when publishing — today\'s date and time are set automatically.'),
                TnfImageUpload::applyTo(
                    FileUpload::make('featured_upload')
                        ->label('Custom thumbnail (optional)')
                        ->image()
                        ->disk('public')
                        ->directory('videos/featured')
                        ->dehydrated(false),
                    'Overrides the auto YouTube thumbnail when set.'
                ),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->limit(50),
                TextColumn::make('categories.name')->label('Category')->badge()->limitList(2),
                TextColumn::make('author.name')->label('Author'),
                TextColumn::make('status')->badge(),
                TextColumn::make('published_at')->dateTime()->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(ContentStatus::class)])
            ->defaultSort('published_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVideos::route('/'),
            'create' => CreateVideo::route('/create'),
            'edit' => EditVideo::route('/{record}/edit'),
        ];
    }
}
