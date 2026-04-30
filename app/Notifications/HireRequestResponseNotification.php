<?php

namespace App\Notifications;

use App\Models\HireRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HireRequestResponseNotification extends Notification
{
    use Queueable;

    public function __construct(private HireRequest $hireRequest) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $status      = $this->hireRequest->status;
        $providerName = $this->hireRequest->provider->first_name . ' ' . $this->hireRequest->provider->last_name;
        $inboxUrl    = env('FRONTEND_URL', 'http://localhost:5173') . '/profile/hire-requests';

        $isAccepted = $status === 'accepted';

        return (new MailMessage)
            ->subject('Hire Request ' . ucfirst($status) . ' — Research Connect')
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('Your hire request **"' . $this->hireRequest->title . '"** has been **' . $status . '** by ' . $providerName . '.')
            ->when($isAccepted, fn($m) => $m->line('You can now contact ' . $providerName . ' directly to proceed with the project.'))
            ->when(!$isAccepted, fn($m) => $m->line('You may browse other providers on Research Connect.'))
            ->action('View My Requests', $inboxUrl)
            ->line('Thank you for using Research Connect!');
    }
}