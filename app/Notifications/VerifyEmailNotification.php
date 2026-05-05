<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        parse_str(parse_url($verifyUrl, PHP_URL_QUERY), $params);

        $frontendUrl = env('FRONTEND_URL')
            . '/verify-email?'
            . http_build_query([
                'id'        => $notifiable->getKey(),
                'hash'      => sha1($notifiable->getEmailForVerification()),
                'expires'   => $params['expires'],
                'signature' => $params['signature'],
            ]);

        return (new MailMessage)
            ->subject('Verify Your Email — Innlaunch')
            ->view('emails.verify-email', [
                'url'       => $frontendUrl,
                'firstName' => $notifiable->first_name,
            ]);
    }
}