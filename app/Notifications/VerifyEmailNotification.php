<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
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

        $frontendUrl = config('app.frontend_url')
            . '/verify-email?'
            . http_build_query([
                'id'        => $notifiable->getKey(),
                'hash'      => sha1($notifiable->getEmailForVerification()),
                'expires'   => $params['expires'],
                'signature' => $params['signature'],
            ]);

        // Also write the link to the application log. Gmail SMTP delivery is
        // slow and lands in spam often enough that testers need a way to get
        // at the link without waiting for the inbox.
        //
        // ⚠ This link verifies the account on its own. Anyone who can read the
        //   log can verify that address, so keep production log access tight —
        //   or wrap this call in `if (! app()->isProduction())`.
        Log::info('Email verification link generated', [
            'user_id'    => $notifiable->getKey(),
            'email'      => $notifiable->getEmailForVerification(),
            'expires_at' => Carbon::now()->addMinutes(60)->toDateTimeString(),
            'url'        => $frontendUrl,
        ]);

        return (new MailMessage)
            ->subject('Verify Your Email — Innlaunch')
            ->view('emails.verify-email', [
                'url'       => $frontendUrl,
                'firstName' => $notifiable->first_name,
            ]);
    }
}