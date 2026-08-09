<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Services\WhatsAppCloudService;
use App\Services\WhatsAppConversationService;
use App\Services\WhatsAppMediaService;
use App\Support\PhoneNumber;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class WhatsAppInboxPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?string $navigationLabel = 'WhatsApp Inbox';

    protected static ?string $title = 'WhatsApp Inbox';

    protected static string|\UnitEnum|null $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'whatsapp-inbox';

    protected string $view = 'filament.pages.whatsapp-inbox';

    public string $search = '';

    public ?string $selectedPhone = null;

    public string $replyBody = '';

    /** @var list<array<string, mixed>> */
    public array $conversations = [];

    /** @var list<array<string, mixed>> */
    public array $messages = [];

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Editor, UserRole::Admin], true);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = \App\Models\WhatsappMessage::unreadInboundCount();

        return $count > 0 ? (string) $count : null;
    }

    public function mount(): void
    {
        $this->loadInbox();
    }

    public function updatedSearch(): void
    {
        $this->loadInbox();
    }

    public function loadInbox(): void
    {
        $this->conversations = app(WhatsAppConversationService::class)
            ->recentConversations($this->search)
            ->values()
            ->all();
    }

    public function selectConversation(string $phone): void
    {
        $phone = PhoneNumber::normalize($phone) ?? $phone;
        $this->selectedPhone = $phone;
        $this->replyBody = '';
        $this->loadThread();
        $this->loadInbox();
    }

    public function loadThread(): void
    {
        if (! filled($this->selectedPhone)) {
            $this->messages = [];

            return;
        }

        $media = app(WhatsAppMediaService::class);
        $service = app(WhatsAppConversationService::class);
        $service->markThreadRead($this->selectedPhone);

        $this->messages = $service->thread($this->selectedPhone)
            ->map(function ($message) use ($media): array {
                $message = $media->ensureStored($message);

                return [
                    'id' => $message->id,
                    'direction' => $message->direction,
                    'type' => $message->type,
                    'body' => $message->body,
                    'caption' => $message->caption,
                    'status' => $message->status,
                    'is_image' => $message->isImage(),
                    'is_media' => $message->isMedia(),
                    'media_url' => $media->mediaUrl($message),
                    'media_filename' => $message->media_filename,
                    'at' => ($message->provider_timestamp ?? $message->created_at)
                        ?->timezone(config('app.timezone'))
                        ->format('M j, g:i A'),
                ];
            })
            ->values()
            ->all();
    }

    public function sendReply(): void
    {
        $body = trim($this->replyBody);
        if ($body === '' || ! filled($this->selectedPhone)) {
            return;
        }

        $ok = app(WhatsAppCloudService::class)->sendText($this->selectedPhone, $body);

        if (! $ok) {
            Notification::make()
                ->title('Reply failed')
                ->body('Check WhatsApp API settings and the 24-hour customer window.')
                ->danger()
                ->send();

            return;
        }

        $this->replyBody = '';
        $this->loadThread();
        $this->loadInbox();

        Notification::make()->title('Reply sent')->success()->send();
    }

    public function getSelectedTitleProperty(): string
    {
        if (! filled($this->selectedPhone)) {
            return '';
        }

        return app(WhatsAppConversationService::class)->displayName($this->selectedPhone);
    }

    public function getSelectedPhoneDisplayProperty(): string
    {
        return PhoneNumber::formatDisplay($this->selectedPhone) ?? (string) $this->selectedPhone;
    }
}
