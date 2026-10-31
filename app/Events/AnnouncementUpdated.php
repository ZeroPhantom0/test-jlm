<?php
// app/Events/AnnouncementUpdated.php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnouncementUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $action,
        public int $announcementId,
    ) {}

    /**
     * Both channels — admin + tenants.
     * These names must match routes/channels.php exactly.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.announcements'),
            new PrivateChannel('tenant.announcements'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'announcement.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'action'          => $this->action,   // created|updated|deleted|pinned
            'announcement_id' => $this->announcementId,
            'ts'              => now()->toIso8601String(),
        ];
    }
}