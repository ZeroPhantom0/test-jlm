<?php
// app/Events/MessageSent.php

namespace App\Events;

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
        public string $senderRole,
        public string $senderName,
        public string $body
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.messages'),
            new PrivateChannel('tenant.' . $this->tenantId . '.messages'),
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
            'sender_role'     => $this->senderRole,
            'sender_name'     => $this->senderName,
            'body'            => $this->body,
            'ts'              => now()->toIso8601String(),
        ];
    }
}