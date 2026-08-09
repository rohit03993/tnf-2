<?php

namespace App\Filament\Resources\WhatsappCampaigns\Pages;

use App\Enums\WhatsAppCampaignStatus;
use App\Filament\Resources\WhatsappCampaigns\WhatsappCampaignResource;
use App\Services\WhatsAppCampaignService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewWhatsappCampaign extends ViewRecord
{
    protected static string $resource = WhatsappCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label('Send / resume')
                ->icon('heroicon-o-paper-airplane')
                ->visible(fn (): bool => in_array($this->record->status, [
                    WhatsAppCampaignStatus::Draft,
                    WhatsAppCampaignStatus::Paused,
                ], true))
                ->requiresConfirmation()
                ->action(function (): void {
                    try {
                        if ($this->record->status === WhatsAppCampaignStatus::Paused) {
                            app(WhatsAppCampaignService::class)->resumeCampaign($this->record, Auth::user());
                        } else {
                            app(WhatsAppCampaignService::class)->queueCampaign($this->record, Auth::user());
                        }

                        Notification::make()->title('Campaign queued')->success()->send();
                        $this->refreshFormData(['status', 'sent_count', 'failed_count', 'total_recipients']);
                        $this->record->refresh();
                    } catch (\RuntimeException $e) {
                        Notification::make()->title('Could not send')->body($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('pause')
                ->label('Pause')
                ->color('warning')
                ->visible(fn (): bool => in_array($this->record->status, [
                    WhatsAppCampaignStatus::Queued,
                    WhatsAppCampaignStatus::Running,
                ], true))
                ->action(function (): void {
                    app(WhatsAppCampaignService::class)->pauseCampaign($this->record);
                    Notification::make()->title('Campaign paused')->success()->send();
                    $this->record->refresh();
                }),
            Action::make('refresh')
                ->label('Refresh')
                ->color('gray')
                ->action(fn () => $this->record->refresh()),
        ];
    }
}
