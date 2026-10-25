<?php
// app/Mail/PaymentRejectedMail.php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $reason = ''
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✗ Payment Rejected - Invoice ' . $this->invoice->transaction_no,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-rejected',
        );
    }
}