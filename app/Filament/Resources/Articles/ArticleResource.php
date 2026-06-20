<?php

namespace App\Filament\Resources\Articles;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Filament\Concerns\ReporterPublishing;
use App\Filament\Concerns\ScopesToAuthor;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Models\Article;
use App\Models\User;
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

class ArticleResource extends Resource
{
    use ReporterPublishing;
    use ScopesToAuthor;

    protected static ?string $model = Article::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $navigationLabel = 'News';

    protected static ?string $modelLabel = 'News article';

    protected static ?string $pluralModelLabel = 'News';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user || $user->isReporter()) {
            return null;
        }

        $count = Article::query()->where('status', ContentStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Article details')->schema([
                TextInput::make('title')
                    ->label('Title (Hindi)')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($set, ?string $state, $get): void {
                        if (filled($get('slug'))) {
                            return;
                        }

                        $slug = \App\Support\ArticleSlug::uniqueFromTitle($state ?? '');

                        $set('slug', $slug);
                    }),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('URL slug in English/Latin letters, e.g. patna-news-update'),
                static::categorySelect(),
                RichEditor::make('content')
                    ->label('Story (Hindi)')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('excerpt')
                    ->label('Summary (Hindi)')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('embed_url')->label('Video embed URL')->url()->columnSpanFull(),
            ])->columns(2),
            Section::make('Publishing')->schema([
                Select::make('author_id')
                    ->relationship('author', 'name')
                    ->required()
                    ->default(fn () => auth()->id())
                    ->disabled(fn () => auth()->user()?->role === UserRole::Author),
                Select::make('status')
                    ->options(fn () => static::statusOptionsFor(auth()->user()))
                    ->required()
                    ->default(ContentStatus::Draft)
                    ->live()
                    ->helperText(fn () => static::statusHelperFor(auth()->user())),
                DateTimePicker::make('published_at')
                    ->visible(fn () => ! auth()->user()?->isReporter() || auth()->user()?->canSelfPublishArticles()),
                Select::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->visible(fn () => ! auth()->user()?->isReporter())
                    ->createOptionForm([
                        TextInput::make('name')->required()->live(onBlur: true)
                            ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                        TextInput::make('slug')->required(),
                    ]),
                TnfImageUpload::applyTo(
                    FileUpload::make('featured_upload')
                        ->label('Featured image')
                        ->image()
                        ->disk('public')
                        ->directory('articles/featured')
                        ->dehydrated(false)
                ),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->limit(50),
                TextColumn::make('categories.name')
                    ->label('Category')
                    ->badge()
                    ->limitList(2),
                TextColumn::make('author.name')->label('Author')->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('published_at')->dateTime()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(ContentStatus::class),
            ])
            ->defaultSort('published_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }

}
