<?php
// app/Mail/PaymentDueReminderMail.php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentDueReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public int $daysUntilDue = 3
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⏰ Payment Reminder - Invoice ' . $this->invoice->transaction_no . ' Due Soon',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-due-reminder',
        );
    }
}