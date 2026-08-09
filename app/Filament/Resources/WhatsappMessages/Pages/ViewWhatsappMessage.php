<?php

namespace App\Filament\Resources\WhatsappMessages\Pages;

use App\Filament\Resources\WhatsappMessages\WhatsappMessageResource;
use App\Services\WhatsAppCloudService;
use App\Support\PhoneNumber;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewWhatsappMessage extends ViewRecord
{
    protected static string $resource = WhatsappMessageResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->isInbound()) {
            $this->record->markAsRead();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Reply on WhatsApp')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->visible(fn (): bool => app(WhatsAppCloudService::class)->isConfigured())
                ->form([
                    Textarea::make('body')
                        ->label('Reply')
                        ->required()
                        ->rows(4)
                        ->helperText('Works best within 24 hours of the user’s last message (Meta customer-care window).'),
                ])
                ->action(function (array $data): void {
                    $phone = PhoneNumber::normalize($this->record->phone);
                    $sent = app(WhatsAppCloudService::class)->sendText($phone ?? '', $data['body']);

                    if ($sent) {
                        Notification::make()->title('Reply sent')->success()->send();

                        return;
                    }

                    Notification::make()
                        ->title('Reply failed')
                        ->body('Check WhatsApp Inbox for the error, or Meta template / 24h window rules.')
                        ->danger()
                        ->send();
                }),
            Action::make('thread')
                ->label('Same number thread')
                ->url(fn (): string => WhatsappMessageResource::getUrl('index', [
                    'tableSearch' => $this->record->phone,
                ]))
                ->icon(Heroicon::OutlinedChatBubbleLeftRight),
        ];
    }
}
