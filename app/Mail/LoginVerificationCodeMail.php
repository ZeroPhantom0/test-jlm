<?php
// app/Mail/LoginVerificationCodeMail.php

namespace App\Mail;

use App\Models\LoginVerificationCode;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LoginVerificationCode $verificationCode
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your JLM Cozy Cabin Login Code: ' . $this->verificationCode->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.login-verification-code',
            with: [
                'code'      => $this->verificationCode->code,
                'userName'  => $this->verificationCode->user->name ?? 'there',
                'expiresIn' => '10 minutes',
            ],
        );
    }
}