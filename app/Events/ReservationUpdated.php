<?php
// app/Events/ReservationUpdated.php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $action,
        public ?int $reservationId = null
    ) {
        \App\Http\Controllers\AdminNotificationController::flushCache();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.reservations'),
            new PrivateChannel('tenant.reservations'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'reservation.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'action'         => $this->action,
            'reservation_id' => $this->reservationId,
            'at'             => now()->toIso8601String(),
        ];
    }
}