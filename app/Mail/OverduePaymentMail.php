<?php
// app/Mail/OverduePaymentMail.php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OverduePaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public int $daysOverdue = 1
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ OVERDUE: Invoice ' . $this->invoice->transaction_no . ' - Immediate Action Required',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.overdue-payment',
        );
    }
}