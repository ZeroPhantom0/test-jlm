<?php
// app/Events/MessageSent.php

namespace App\Events;

use App\Http\Controllers\AdminNotificationController;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $tenantId,
        public int $userId,
        public string $senderRole,
        public string $senderName,
        public string $body,
        public ?int $messageId = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            // Admin-wide inbox
            new PrivateChannel('admin.messages'),

            // Tenant-scoped channel (primary)
            new PrivateChannel('tenant.' . $this->tenantId . '.messages'),

            // Per-user fallback — works even if the Tenant row
            // is created later (legacy users, shell rows, etc.)
            new PrivateChannel('user.' . $this->userId . '.messages'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message_id'      => $this->messageId,
            'tenant_id'       => $this->tenantId,
            'sender_role'     => $this->senderRole,
            'sender_name'     => $this->senderName,
            'body'            => $this->body,
            'ts'              => now()->toIso8601String(),
        ];
    }
}