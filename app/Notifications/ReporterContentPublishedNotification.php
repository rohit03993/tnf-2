<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReporterContentPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $url,
        public string $contentType = 'article',
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->contentType === 'video' ? 'video' : 'article';

        return (new MailMessage)
            ->subject('Your '.$label.' is live — '.$this->title)
            ->line('Your '.$label.' has been approved and published on TNF Today.')
            ->action('View on site', $this->url);
    }
}
