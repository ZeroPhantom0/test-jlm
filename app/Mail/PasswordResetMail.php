<?php
// app/Mail/PasswordResetMail.php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $resetToken,
        public string $resetUrl,
        public int $expiresInMinutes = 60
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your JLM Cozy Cabin Password',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
            with: [
                'userName'     => $this->user->name ?? 'there',
                'resetUrl'     => $this->resetUrl,
                'resetToken'   => $this->resetToken,
                'expiresIn'    => $this->expiresInMinutes . ' minutes',
            ],
        );
    }
}