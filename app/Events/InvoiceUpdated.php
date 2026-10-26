<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $action,     // created | verified | rejected | updated | paid
        public ?int $invoiceId = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('admin.billing'),
            new Channel('tenant.billing'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'invoice.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'action'     => $this->action,
            'invoice_id' => $this->invoiceId,
            'at'         => now()->toIso8601String(),
        ];
    }
}