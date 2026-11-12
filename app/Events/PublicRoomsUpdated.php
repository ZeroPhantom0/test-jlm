<?php
// app/Events/PublicRoomsUpdated.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Tells the public landing page that the room list changed (status,
 * price, details, or a virtual tour was published / unpublished).
 *
 * Broadcast on a PUBLIC channel because landing visitors are guests.
 * The payload is only a signal — the page re-requests its own props —
 * so nothing private ever goes over the public channel.
 */
class PublicRoomsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    private static bool $queued = false;

    public function broadcastOn(): array
    {
        return [new Channel('public.rooms')];
    }

    public function broadcastAs(): string
    {
        return 'rooms.updated';
    }

    public function broadcastWith(): array
    {
        return ['at' => now()->toIso8601String()];
    }

    /**
     * Coalesce: one request (e.g. approving a reservation re-syncs several
     * rooms) sends a single broadcast after the response, not one per save.
     */
    public static function dispatchOnce(): void
    {
        // Console / queue workers may never "terminate" — send right away.
        if (app()->runningInConsole()) {
            try {
                event(new self());
            } catch (\Throwable $e) {
                report($e);
            }
            return;
        }

        if (self::$queued) {
            return;
        }
        self::$queued = true;

        app()->terminating(function () {
            self::$queued = false;
            try {
                event(new self());
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }
}
