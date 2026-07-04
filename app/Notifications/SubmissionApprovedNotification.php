<?php

namespace App\Notifications;

use App\Models\Article;
use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Submission $submission,
        public Article $article,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your story is live — '.$this->article->title)
            ->line('Great news! Your submission has been approved and published on TNF Today.')
            ->action('Read your article', route('article.show', $this->article));
    }
}
