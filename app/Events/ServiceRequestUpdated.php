<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceRequestUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $type,
        public string $action,
        public ?int $requestId = null
    ) {
        \App\Http\Controllers\AdminNotificationController::flushCache();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.services'),
            new PrivateChannel('tenant.services'),
        ];
    }

    public function broadcastAs(): string
    {
        return $this->type === 'room_change' ? 'room-change.updated' : 'stay-extension.updated';
    }
}
