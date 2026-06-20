<?php

namespace App\Filament\Pages\Settings;

use App\Enums\UserRole;
use App\Filament\Pages\Settings\Concerns\ManagesSettings;
use App\Services\PdfClient;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManagePdfSettings extends SettingsPage
{
    use ManagesSettings;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;

    protected static ?string $navigationLabel = 'PDF Service';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'PDF Service';

    protected static ?string $slug = 'settings/pdf';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function settingKeys(): array
    {
        return [
            'pdf_service_url' => config('tnf.pdf_service_url'),
            'pdf_service_secret' => config('tnf.pdf_service_secret'),
            'pdf_callback_secret' => config('tnf.pdf_callback_secret'),
            'pdf_use_queue' => config('tnf.pdf_use_queue', false),
        ];
    }

    protected function secretKeys(): array
    {
        return ['pdf_service_secret', 'pdf_callback_secret'];
    }

    public function mount(): void
    {
        $data = $this->loadSettings();
        $data['pdf_service_secret'] = '';
        $data['pdf_callback_secret'] = '';

        $this->form->fill($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testPdfService')
                ->label('Test connection')
                ->icon(Heroicon::OutlinedSignal)
                ->requiresConfirmation()
                ->visible(fn (): bool => app(PdfClient::class)->isConfigured())
                ->action(function (): void {
                    $result = app(PdfClient::class)->testConnection();

                    $notification = Notification::make()
                        ->title($result['ok'] ? 'PDF service reachable' : 'PDF service unreachable')
                        ->body($result['message']);

                    if ($result['ok']) {
                        $notification->success();
                    } else {
                        $notification->danger();
                    }

                    $notification->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('FastAPI PDF microservice')
                ->description('Values saved here override .env. Leave secret fields blank to keep the current value.')
                ->schema([
                    TextInput::make('pdf_service_url')
                        ->label('Service URL')
                        ->url()
                        ->placeholder('https://pdf.example.com')
                        ->helperText('Callback URL is always: '.url('/api/v1/internal/pdf-job-complete')),
                    TextInput::make('pdf_service_secret')
                        ->label('Service secret')
                        ->password()
                        ->revealable(),
                    TextInput::make('pdf_callback_secret')
                        ->label('Callback secret')
                        ->password()
                        ->revealable()
                        ->helperText('Sent as X-Callback-Secret from the PDF service.'),
                    Toggle::make('pdf_use_queue')
                        ->label('Process PDFs in background queue')
                        ->helperText('Enable only when Redis/database queue workers are running.'),
                ]),
        ]);
    }
}
