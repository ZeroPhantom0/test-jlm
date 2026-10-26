<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceRequestUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $type, // 'room_change' or 'stay_extension'
        public string $action,
        public ?int $requestId = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('admin.services'),
            new Channel('tenant.services'),
        ];
    }

    public function broadcastAs(): string
    {
        return $this->type === 'room_change' ? 'room-change.updated' : 'stay-extension.updated';
    }
}