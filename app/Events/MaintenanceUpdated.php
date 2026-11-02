<?php
// app/Events/MaintenanceUpdated.php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaintenanceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $action,
        public ?int $requestId = null,
        public ?int $tenantId = null
    ) {}

    public function broadcastOn(): array
    {
        // We ignore $this->tenantId here and always broadcast to the 
        // general channels so that any connected admin or tenant page updates.
        return [
            new PrivateChannel('admin.services'),
            new PrivateChannel('tenant.services'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'maintenance.updated';
    }
}