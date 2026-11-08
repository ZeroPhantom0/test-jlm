<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SettingsVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $userName,
        public string $actionType
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Security Verification Code - JLM Cozy Cabin',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.settings-verification-code',
            with: [
                'code' => $this->code,
                'userName' => $this->userName,
                'actionType' => $this->actionType,
            ],
        );
    }
}