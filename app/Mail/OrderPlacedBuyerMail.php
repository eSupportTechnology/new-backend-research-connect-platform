<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlacedBuyerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $order,
        public $item,
        public $seller
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Confirmed — ' . $this->item->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order_placed_buyer',
        );
    }
}