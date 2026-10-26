<?php

namespace App\Events;

use App\Models\Reservation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $action,      // created | approved | rejected | updated | deleted
        public ?int $reservationId = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('admin.reservations'),
            new Channel('tenant.reservations'),
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