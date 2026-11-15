<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * "Tenant data changed" — a tenant record, account or profile was created,
 * edited or removed (admin edits, removals, sign-ups, inactivity job, room
 * assignment on approval, profile updates…).
 *
 * Goes to the admin tenants channel and to each affected tenant's own
 * private channel, so the Admin → Tenants table and that tenant's dashboard
 * refresh live. The payload is only a signal — pages re-fetch their data —
 * so no personal information is broadcast.
 */
class TenantsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /** @var array<int,true> user ids touched during this request */
    private static array $pending = [];
    private static bool $queued = false;

    /** @param int[] $userIds */
    public function __construct(public array $userIds = []) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('admin.tenants')];
        foreach ($this->userIds as $id) {
            $channels[] = new PrivateChannel("user.{$id}");
        }
        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'tenant.updated';
    }

    public function broadcastWith(): array
    {
        return ['at' => now()->toIso8601String()];
    }

    /**
     * Coalesce: one request often saves several rows (user + tenant + profile),
     * so send a single broadcast after the response instead of one per save.
     */
    public static function dispatchOnce(?int $userId = null): void
    {
        if ($userId) {
            self::$pending[$userId] = true;
        }

        $send = function () {
            $ids = array_keys(self::$pending);
            self::$pending = [];
            self::$queued = false;
            try {
                event(new self(array_slice($ids, 0, 20)));
            } catch (\Throwable $e) {
                report($e); // broadcasting must never break a save
            }
        };

        // Console / queue workers may never "terminate" — send right away.
        if (app()->runningInConsole()) {
            $send();
            return;
        }

        if (self::$queued) {
            return;
        }
        self::$queued = true;
        app()->terminating($send);
    }
}
