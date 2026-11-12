<?php
// app/Mail/TenantInactiveMail.php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once when a tenant is marked Inactive after 6 months without a
 * reservation (Privacy Policy → Account Inactivity, "Notification – After
 * 6 Months"). Account access is NOT removed — booking again reactivates.
 */
class TenantInactiveMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?Carbon $lastActivityAt = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your JLM Cozy Cabin account is now inactive',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-inactive',
            with: [
                'userName'       => $this->user->name ?? 'there',
                'lastActivity'   => $this->lastActivityAt?->format('F j, Y') ?? 'more than 6 months ago',
                'reserveUrl'     => url('/tenants/reservations'),
            ],
        );
    }
}
