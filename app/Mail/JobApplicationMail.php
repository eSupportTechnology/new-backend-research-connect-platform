<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $job,
        public $applicant,
        public string $profileUrl,
        public string $applicantMessage
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Application for "' . $this->job->title . '" — ' . $this->applicant->first_name . ' ' . $this->applicant->last_name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.job_application');
    }
}