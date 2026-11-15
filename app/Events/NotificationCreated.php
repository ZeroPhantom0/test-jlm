<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * "You have a new bell notification" — sent right after the row is stored,
 * on the recipient's private channel. Sent immediately (no queue worker needed).
 */
class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public array $payload = [] // optional: could include title, id, etc.
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->userId}")];
    }

    // Dot-prefixed name so the frontend listens with ".notification.created"
    public function broadcastAs(): string
    {
        return 'notification.created';
    }
}