<?php

namespace App\Filament\Resources\Categories;

use App\Enums\UserRole;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|\UnitEnum|null $navigationGroup = 'Taxonomy';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Editor, UserRole::Admin], true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(120)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
            TextInput::make('slug')
                ->required()
                ->maxLength(120)
                ->unique(ignoreRecord: true)
                ->helperText('Used in the site URL for this section.'),
            Textarea::make('description')
                ->rows(3)
                ->maxLength(500)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query) => $query->withCount(['articles', 'videos', 'submissions']),
            )
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Category $record): ?string => filled($record->description)
                        ? Str::limit($record->description, 72)
                        : null),
                TextColumn::make('slug')
                    ->copyable()
                    ->copyMessage('Slug copied')
                    ->toggleable(),
                TextColumn::make('articles_count')
                    ->label('Articles')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('videos_count')
                    ->label('Videos')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->modalHeading('Edit category')
                    ->successNotificationTitle('Category updated'),
                DeleteAction::make()
                    ->label('Delete')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete category')
                    ->modalDescription(fn (Category $record): string => $record->isInUse()
                        ? 'This category is linked to published or pending content. Reassign those items before deleting.'
                        : 'This action cannot be undone.')
                    ->disabled(fn (Category $record): bool => $record->isInUse())
                    ->tooltip(fn (Category $record): ?string => $record->isInUse()
                        ? 'Reassign linked articles, videos, or submissions before deleting.'
                        : 'Delete this category')
                    ->successNotificationTitle('Category deleted'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
