<?php
// app/Events/MaintenanceUpdated.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaintenanceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $action,
        public ?int $requestId = null,
        public ?int $tenantId = null // <-- Added this
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new Channel('admin.services'),
        ];

        if ($this->tenantId) {
            $channels[] = new Channel('tenant.' . $this->tenantId . '.services');
        } else {
            $channels[] = new Channel('tenant.services');
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'maintenance.updated';
    }
}