<?php

namespace App\Filament\Pages;

use App\Support\TnfImageUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rule;

class MyProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'My Profile';

    protected static ?string $title = 'My Profile';

    protected static ?string $slug = 'my-profile';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.my-profile';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->role->canAccessAdmin() ?? false;
    }

    public function mount(): void
    {
        $user = auth()->user();

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'avatar_path' => $user->avatar_path,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Profile photo & name')
                    ->description('This name and photo appear in the admin panel and on news bylines when you are the author.')
                    ->schema([
                        TnfImageUpload::applyTo(
                            FileUpload::make('avatar_path')
                                ->label('Profile photo')
                                ->image()
                                ->avatar()
                                ->disk('public')
                                ->directory('users/avatars')
                                ->columnSpanFull()
                        ),
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->rule(fn () => Rule::unique('users', 'email')->ignore(auth()->id())),
                        Placeholder::make('role_label')
                            ->label('Role')
                            ->content(fn (): string => auth()->user()?->role->label() ?? '—'),
                    ])->columns(2),
                Section::make('Change password')
                    ->description('Leave blank unless you want a new password.')
                    ->schema([
                        TextInput::make('password')
                            ->label('New password')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->confirmed()
                            ->nullable()
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        TextInput::make('password_confirmation')
                            ->label('Confirm new password')
                            ->password()
                            ->revealable()
                            ->dehydrated(false),
                    ])->columns(2),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'avatar_path' => $data['avatar_path'] ?? null,
        ];

        if (filled($data['password'] ?? null)) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        Notification::make()->title('Profile updated')->success()->send();
    }
}
