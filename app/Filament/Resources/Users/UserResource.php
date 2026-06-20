<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Services\AdminService;
use App\Support\TnfImageUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

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
                fn (?string $state) => filled($state) ? Hash::make($state) : null
            )->dehydrated(fn (?string $state) => filled($state))->required(fn (string $operation) => $operation === 'create'),
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
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role')->badge()->formatStateUsing(fn (UserRole $state) => $state->label()),
                IconColumn::make('is_active')->boolean()->label('Active'),
                IconColumn::make('requires_approval')->boolean()->label('Approval lock'),
                IconColumn::make('subscription_active')->boolean()->label('Premium'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
