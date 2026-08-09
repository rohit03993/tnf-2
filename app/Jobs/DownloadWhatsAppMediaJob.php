<?php

namespace App\Jobs;

use App\Models\WhatsappMessage;
use App\Services\WhatsAppMediaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DownloadWhatsAppMediaJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $messageId,
    ) {}

    public function handle(WhatsAppMediaService $media): void
    {
        $message = WhatsappMessage::query()->find($this->messageId);

        if (! $message) {
            return;
        }

        $media->downloadInboundMedia($message);
    }
}
