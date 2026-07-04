<?php

namespace App\Filament\Resources\Users;

use App\Enums\ContentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Services\AdminService;
use App\Services\UserContentTransferService;
use App\Support\TnfImageUpload;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Users';

    protected static string|\UnitEnum|null $navigationGroup = 'Users';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount([
            'articles',
            'articles as published_articles_count' => fn (Builder $query) => $query->where('status', ContentStatus::Published),
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            TnfImageUpload::applyTo(
                FileUpload::make('avatar_path')
                    ->label('Profile photo')
                    ->image()
                    ->avatar()
                    ->disk('public')
                    ->directory('users/avatars')
            ),
            TextInput::make('password')->password()->dehydrateStateUsing(
                fn (?string $state) => filled($state) ? $state : null
            )->dehydrated(fn (?string $state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create')
                ->helperText(fn (string $operation) => $operation === 'edit'
                    ? 'Leave blank to keep the current password.'
                    : null),
            Select::make('role')
                ->options(fn (?User $record) => static::roleOptions($record))
                ->required()
                ->default(UserRole::Subscriber->value)
                ->live()
                ->afterStateUpdated(function ($set, ?string $state): void {
                    if ($state !== UserRole::Author->value) {
                        $set('requires_approval', false);
                    }
                }),
            Toggle::make('is_active')
                ->label('Account active')
                ->default(true)
                ->helperText('Inactive users cannot log in. Their published news stays on the site.'),
            Toggle::make('requires_approval')
                ->label('Require approval for all news')
                ->default(false)
                ->helperText('When on, this reporter cannot publish live. Every article needs editor or admin approval.')
                ->visible(fn ($get) => $get('role') === UserRole::Author->value),
            Toggle::make('subscription_active')->label('Premium subscription'),
        ]);
    }

    /** @return array<string, string> */
    protected static function roleOptions(?User $record): array
    {
        $options = [
            UserRole::Subscriber->value => UserRole::Subscriber->label(),
            UserRole::Author->value => UserRole::Author->label(),
            UserRole::Editor->value => UserRole::Editor->label(),
        ];

        $canAssignAdmin = ! AdminService::hasAdmin() || ($record && $record->role === UserRole::Admin);

        if ($canAssignAdmin) {
            $options[UserRole::Admin->value] = UserRole::Admin->label();
        }

        return $options;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_path')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->height(40)
                    ->width(40)
                    ->defaultImageUrl(asset('images/admin-news-placeholder.svg')),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (User $record): string => $record->email),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('role')->badge()->formatStateUsing(fn (UserRole $state) => $state->label()),
                TextColumn::make('published_articles_count')
                    ->label('Published news')
                    ->sortable()
                    ->numeric()
                    ->alignCenter()
                    ->description(fn (User $record): ?string => $record->articles_count > $record->published_articles_count
                        ? ($record->articles_count - $record->published_articles_count).' draft/pending'
                        : null),
                IconColumn::make('is_active')->boolean()->label('Active'),
                IconColumn::make('requires_approval')->boolean()->label('Approval lock'),
                IconColumn::make('subscription_active')->boolean()->label('Premium'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->modalHeading('Edit user')
                    ->successNotificationTitle('User updated'),
                DeleteAction::make()
                    ->label('Delete')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete user')
                    ->modalDescription(fn (User $record): string => UserContentTransferService::deleteModalDescription($record))
                    ->form(fn (User $record): array => static::deleteForm($record))
                    ->using(function (User $record, array $data): bool {
                        UserContentTransferService::delete(
                            $record,
                            isset($data['transfer_to']) ? (int) $data['transfer_to'] : null,
                        );

                        return true;
                    })
                    ->disabled(fn (User $record): bool => $record->id === auth()->id())
                    ->tooltip(fn (User $record): ?string => $record->id === auth()->id()
                        ? 'You cannot delete your own account here.'
                        : null)
                    ->successNotificationTitle('User deleted'),
            ])
            ->defaultSort('name');
    }

    /** @return list<\Filament\Forms\Components\Component> */
    protected static function deleteForm(User $record): array
    {
        if (! UserContentTransferService::hasTransferableContent($record)) {
            return [];
        }

        $counts = UserContentTransferService::contentCounts($record);

        return [
            Placeholder::make('content_summary')
                ->label('Content owned by this user')
                ->content(collect([
                    $counts['published_articles'].' published news',
                    $counts['articles'] > $counts['published_articles']
                        ? ($counts['articles'] - $counts['published_articles']).' draft/pending news'
                        : null,
                    $counts['videos'] > 0 ? $counts['videos'].' video'.($counts['videos'] === 1 ? '' : 's') : null,
                    $counts['epaper'] > 0 ? $counts['epaper'].' ePaper edition'.($counts['epaper'] === 1 ? '' : 's') : null,
                ])->filter()->join(' · ')),
            Select::make('transfer_to')
                ->label('Transfer content to')
                ->options(fn (): array => UserContentTransferService::transferTargetOptions($record))
                ->required()
                ->searchable()
                ->native(false)
                ->helperText('News, videos, and ePaper editions will appear under the selected staff member\'s name.'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
