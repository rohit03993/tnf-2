<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSubmissionNotification extends Notification
{
    use Queueable;

    public function __construct(public Submission $submission) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New member submission — '.$this->submission->title)
            ->line('A member submitted a news story for review.')
            ->line('Title: '.$this->submission->title)
            ->line('Member: '.$this->submission->user->name)
            ->action('Review in admin', url('/admin/submissions'));
    }
}
