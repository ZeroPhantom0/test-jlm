<?php
// app/Services/HistoryService.php

namespace App\Services;

use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Reservation;
use App\Models\RoomChangeRequest;
use App\Models\StayExtensionRequest;
use Illuminate\Support\Collection;

class HistoryService
{
    /**
     * Build the unified activity timeline.
     *
     * Each "event" is normalized to a common shape:
     *  - category: 'payment' | 'reservation' | 'maintenance' | 'room-change' | 'stay-extension'
     *  - icon:     FontAwesome class
     *  - color:    semantic color key (green/blue/purple/red/yellow/gray)
     *  - title:    short headline
     *  - tenant:   tenant display name
     *  - room:     room/bed label
     *  - desc:     one-line description
     *  - date:     formatted date string
     *  - timestamp: raw Carbon timestamp for sorting
     *  - amount:   optional amount string
     *  - status:   paid | approved | rejected | completed | cancelled | expired | pending
     *  - details:  array of ['label' => ..., 'value' => ...] shown in expanded view
     *
     * @param  array  $filters  ['search' => string, 'category' => string]
     * @return Collection
     */
    public function build(array $filters = []): Collection
    {
        $events = collect()
            ->merge($this->payments())
            ->merge($this->reservations())
            ->merge($this->maintenance())
            ->merge($this->roomChanges())
            ->merge($this->stayExtensions());

        // Apply category filter
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $events = $events->where('category', $filters['category']);
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $q = strtolower($filters['search']);
            $events = $events->filter(function ($e) use ($q) {
                return str_contains(strtolower($e['title'] ?? ''), $q)
                    || str_contains(strtolower($e['tenant'] ?? ''), $q)
                    || str_contains(strtolower($e['room'] ?? ''), $q)
                    || str_contains(strtolower($e['desc'] ?? ''), $q);
            });
        }

        // Sort newest first
        return $events->sortByDesc('timestamp')->values();
    }

    /**
     * Compute the summary metrics for the top of the history page.
     */
    public function metrics(): array
    {
        $paidTotal = Invoice::where('status', 'Paid')->sum('amount_paid');
        $paidCount = Invoice::where('status', 'Paid')->count();

        return [
            'total_payments'       => (float) $paidTotal,
            'total_payments_count' => $paidCount,
            'past_reservations'    => Reservation::count(),
            'service_requests'     => MaintenanceRequest::where('status', 'Completed')->count(),
            'approved_requests'    => RoomChangeRequest::where('status', 'Approved')->count()
                                    + StayExtensionRequest::where('status', 'Approved')->count(),
        ];
    }

    // ============================================================
    // SOURCES — each returns a Collection of normalized events
    // ============================================================

    protected function payments(): Collection
    {
        return Invoice::whereIn('status', ['Paid', 'Partial'])
            ->latest()
            ->get()
            ->map(function (Invoice $inv) {
                return [
                    'category'  => 'payment',
                    'icon'      => 'fa-solid fa-peso-sign',
                    'color'     => 'green',
                    'title'     => $inv->description,
                    'tenant'    => $inv->tenant_name,
                    'room'      => 'Room ' . $inv->room_number,
                    'desc'      => 'Payment of ' . $inv->formatted_paid
                                 . ' via ' . strtolower($inv->payment_method),
                    'date'      => $inv->created_at?->format('M d, Y · h:i A'),
                    'timestamp' => $inv->date_paid ?? $inv->created_at,
                    'amount'    => $inv->formatted_paid,
                    'status'    => 'paid',
                    'details'   => [
                        ['label' => 'Invoice ID',       'value' => $inv->transaction_no],
                        ['label' => 'Tenant',           'value' => $inv->tenant_name],
                        ['label' => 'Unit',             'value' => $inv->room_number],
                        ['label' => 'Amount',           'value' => $inv->formatted_paid],
                        ['label' => 'Payment Method',   'value' => strtoupper($inv->payment_method)],
                        ['label' => 'Reference Number', 'value' => $inv->receipt_no ?? '—'],
                        ['label' => 'Paid Date',        'value' => $inv->date_paid?->format('M d, Y') ?? '—'],
                    ],
                ];
            });
    }

    protected function reservations(): Collection
    {
        return Reservation::latest()
            ->get()
            ->map(function (Reservation $res) {
                $statusMap = [
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                    'Expired'  => 'expired',
                    'Pending'  => 'pending',
                ];
                $status = $statusMap[$res->status] ?? 'pending';

                $color = match ($status) {
                    'approved' => 'blue',
                    'rejected' => 'red',
                    'expired'  => 'yellow',
                    default    => 'gray',
                };

                return [
                    'category'  => 'reservation',
                    'icon'      => 'fa-regular fa-calendar-check',
                    'color'     => $color,
                    'title'     => 'Reservation - ' . $res->desired_room,
                    'tenant'    => $res->tenant_name,
                    'room'      => $res->desired_room,
                    'desc'      => 'Reservation ' . strtolower($res->status),
                    'date'      => $res->created_at?->format('M d, Y · h:i A'),
                    'timestamp' => $res->processed_at ?? $res->created_at,
                    'amount'    => $res->formatted_fee,
                    'status'    => $status,
                    'details'   => [
                        ['label' => 'Reservation ID', 'value' => $res->reference_no],
                        ['label' => 'Tenant',         'value' => $res->tenant_name],
                        ['label' => 'Unit',           'value' => $res->desired_room],
                        ['label' => 'Fee',            'value' => $res->formatted_fee],
                        ['label' => 'Status',         'value' => $res->status],
                    ],
                ];
            });
    }

    protected function maintenance(): Collection
    {
        return MaintenanceRequest::with(['tenant.user', 'room'])
            ->latest()
            ->get()
            ->map(function (MaintenanceRequest $m) {
                $status = match ($m->status) {
                    'Completed' => 'completed',
                    'Cancelled' => 'cancelled',
                    default     => 'pending',
                };
                $color = match ($status) {
                    'completed' => 'blue',
                    'cancelled' => 'gray',
                    default     => 'yellow',
                };

                return [
                    'category'  => 'maintenance',
                    'icon'      => 'fa-solid fa-wrench',
                    'color'     => $color,
                    'title'     => $m->title,
                    'tenant'    => $m->tenant->user->name ?? 'Unknown',
                    'room'      => $m->room ? 'Room ' . $m->room->room_number : 'N/A',
                    'desc'      => $m->description,
                    'date'      => $m->created_at?->format('M d, Y · h:i A'),
                    'timestamp' => $m->completed_at ?? $m->created_at,
                    'amount'    => null,
                    'status'    => $status,
                    'details'   => [
                        ['label' => 'Request ID',   'value' => 'mnt-' . $m->id],
                        ['label' => 'Tenant',       'value' => $m->tenant->user->name ?? 'Unknown'],
                        ['label' => 'Unit',         'value' => $m->room?->room_number ?? 'N/A'],
                        ['label' => 'Category',     'value' => $m->category],
                        ['label' => 'Priority',     'value' => $m->priority],
                        ['label' => 'Description',  'value' => $m->description],
                    ],
                ];
            });
    }

    protected function roomChanges(): Collection
    {
        return RoomChangeRequest::with(['tenant.user'])
            ->latest()
            ->get()
            ->map(function (RoomChangeRequest $r) {
                $status = match ($r->status) {
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                    default     => 'pending',
                };
                $color = match ($status) {
                    'approved' => 'purple',
                    'rejected' => 'red',
                    default     => 'gray',
                };

                return [
                    'category'  => 'room-change',
                    'icon'      => 'fa-solid fa-house',
                    'color'     => $color,
                    'title'     => 'Room Change: ' . $r->current_room . ' → ' . $r->requested_room,
                    'tenant'    => $r->tenant->user->name ?? 'Unknown',
                    'room'      => 'Room ' . $r->current_room,
                    'desc'      => $r->reason,
                    'date'      => $r->created_at?->format('M d, Y · h:i A'),
                    'timestamp' => $r->processed_at ?? $r->created_at,
                    'amount'    => null,
                    'status'    => $status,
                    'details'   => [
                        ['label' => 'Request ID', 'value' => 'rmc-' . $r->id],
                        ['label' => 'Tenant',     'value' => $r->tenant->user->name ?? 'Unknown'],
                        ['label' => 'From Unit',  'value' => $r->current_room],
                        ['label' => 'To Unit',    'value' => $r->requested_room],
                        ['label' => 'Reason',     'value' => $r->reason],
                    ],
                ];
            });
    }

    protected function stayExtensions(): Collection
    {
        return StayExtensionRequest::with(['tenant.user', 'room'])
            ->latest()
            ->get()
            ->map(function (StayExtensionRequest $s) {
                $status = match ($s->status) {
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                    default     => 'pending',
                };
                $color = match ($status) {
                    'approved' => 'yellow',
                    'rejected' => 'red',
                    default     => 'gray',
                };

                return [
                    'category'  => 'stay-extension',
                    'icon'      => 'fa-regular fa-calendar-plus',
                    'color'     => $color,
                    'title'     => 'Stay Extension - ' . ($s->room?->room_number ? 'Room ' . $s->room->room_number : 'N/A'),
                    'tenant'    => $s->tenant->user->name ?? 'Unknown',
                    'room'      => $s->room?->room_number ? 'Room ' . $s->room->room_number : 'N/A',
                    'desc'      => $s->reason,
                    'date'      => $s->created_at?->format('M d, Y · h:i A'),
                    'timestamp' => $s->processed_at ?? $s->created_at,
                    'amount'    => null,
                    'status'    => $status,
                    'details'   => [
                        ['label' => 'Request ID',       'value' => 'ext-' . $s->id],
                        ['label' => 'Tenant',           'value' => $s->tenant->user->name ?? 'Unknown'],
                        ['label' => 'Unit',             'value' => $s->room?->room_number ?? 'N/A'],
                        ['label' => 'Current End',      'value' => $s->current_lease_end?->format('M d, Y') ?? '—'],
                        ['label' => 'Requested End',    'value' => $s->requested_lease_end?->format('M d, Y') ?? '—'],
                        ['label' => 'Extension',        'value' => $s->extension_label],
                        ['label' => 'Reason',           'value' => $s->reason],
                    ],
                ];
            });
    }
}