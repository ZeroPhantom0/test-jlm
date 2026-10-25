<?php
// app/Mail/AccountRegisteredMail.php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to JLM Cozy Cabin — Account Created',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-registered',
            with: [
                'userName' => $this->user->name ?? 'there',
                'userEmail' => $this->user->email,
                'loginUrl'  => url('/login'),
            ],
        );
    }
}