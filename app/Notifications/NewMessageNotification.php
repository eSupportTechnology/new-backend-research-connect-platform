<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(private Message $message) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $profileUrl = env('FRONTEND_URL') . '/profile/inbox';

        return (new MailMessage)
            ->subject('You have a new message on Research Connect')
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('You received a new message from **' . $this->message->sender_name . '** (' . $this->message->sender_email . ').')
            ->line('**Message:**')
            ->line($this->message->message)
            ->action('View Your Inbox', $profileUrl)
            ->line('You can reply by contacting the sender directly at ' . $this->message->sender_email . '.')
            ->line('Thank you for using Research Connect!');
    }
}