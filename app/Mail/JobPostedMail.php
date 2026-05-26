<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobPostedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $job,
        public bool $isAdmin = false
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isAdmin
            ? 'New Job Post Pending Review: ' . $this->job->title
            : 'Your Job Post Has Been Submitted: ' . $this->job->title;

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: $this->isAdmin ? 'emails.admin_job_notification' : 'emails.job_posted',
        );
    }
}