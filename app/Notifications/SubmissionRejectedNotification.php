<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionRejectedNotification extends Notification
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
        $message = (new MailMessage)
            ->subject('Update on your submission — '.$this->submission->title)
            ->line('Your submission was not approved at this time.');

        if ($this->submission->rejection_reason) {
            $message->line('Reason: '.$this->submission->rejection_reason);
        }

        return $message->action('Go to My Account', route('account'));
    }
}
