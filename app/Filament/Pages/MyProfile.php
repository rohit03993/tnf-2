<?php

namespace App\Filament\Pages;

use App\Support\TnfImageUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\Rule;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class MyProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'My Profile';

    protected static ?string $title = 'My Profile';

    protected static ?string $slug = 'my-profile';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.my-profile';

    public ?array $data = [];

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
                Section::make('Profile')->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->rule(fn () => Rule::unique('users', 'email')->ignore(auth()->id())),
                    TnfImageUpload::applyTo(
                        FileUpload::make('avatar_path')
                            ->label('Profile photo')
                            ->image()
                            ->avatar()
                            ->disk('public')
                            ->directory('users/avatars')
                    ),
                ])->columns(2),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'avatar_path' => $data['avatar_path'] ?? null,
        ]);

        Notification::make()->title('Profile updated')->success()->send();
    }
}
