<?php
// app/Events/AnnouncementUpdated.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnouncementUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $action,           // created | updated | deleted | pinned | unpublished
        public ?int $announcementId = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('admin.announcements'),
            new Channel('tenant.announcements'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'announcement.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'action'          => $this->action,
            'announcement_id' => $this->announcementId,
            'at'              => now()->toIso8601String(),
        ];
    }
}