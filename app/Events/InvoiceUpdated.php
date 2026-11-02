<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $action,
        public ?int $invoiceId = null
    ) {
        \App\Http\Controllers\AdminNotificationController::flushCache();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.billing'),
            new PrivateChannel('tenant.billing'),
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
