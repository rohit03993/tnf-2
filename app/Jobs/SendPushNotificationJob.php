<?php

namespace App\Jobs;

use App\Services\OneSignalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPushNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $url,
    ) {}

    public function handle(OneSignalService $oneSignal): void
    {
        $oneSignal->send($this->title, $this->message, $this->url);
    }
}
