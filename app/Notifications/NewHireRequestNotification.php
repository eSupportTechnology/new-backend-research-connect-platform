<?php

namespace App\Notifications;

use App\Models\HireRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewHireRequestNotification extends Notification
{
    use Queueable;

    public function __construct(private HireRequest $hireRequest) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $inboxUrl = env('FRONTEND_URL') . '/profile/hire-requests';

        return (new MailMessage)
            ->subject('New Hire Request on Research Connect')
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('You have received a new hire request from **' . $this->hireRequest->requester->first_name . ' ' . $this->hireRequest->requester->last_name . '**.')
            ->line('**Project:** ' . $this->hireRequest->title)
            ->line('**Description:** ' . $this->hireRequest->description)
            ->when($this->hireRequest->budget, fn($m) => $m->line('**Budget:** Rs ' . number_format($this->hireRequest->budget, 2)))
            ->when($this->hireRequest->start_date, fn($m) => $m->line('**Start Date:** ' . $this->hireRequest->start_date->format('M d, Y')))
            ->when($this->hireRequest->deadline, fn($m) => $m->line('**Deadline:** ' . $this->hireRequest->deadline->format('M d, Y')))
            ->action('View Hire Request', $inboxUrl)
            ->line('Please log in to accept or decline this request.');
    }
}