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
     * Only finalized states are included:
     *   Payments        → Paid | Rejected
     *   Reservations    → Approved | Rejected | Expired
     *   Maintenance     → Completed | Cancelled
     *   Room Changes    → Approved | Rejected
     *   Stay Extensions → Approved | Rejected
     */
    public function build(array $filters = []): Collection
    {
        $events = collect()
            ->merge($this->payments())
            ->merge($this->reservations())
            ->merge($this->maintenance())
            ->merge($this->roomChanges())
            ->merge($this->stayExtensions());

        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $events = $events->where('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $q = strtolower($filters['search']);
            $events = $events->filter(function ($e) use ($q) {
                return str_contains(strtolower($e['title'] ?? ''), $q)
                    || str_contains(strtolower($e['tenant'] ?? ''), $q)
                    || str_contains(strtolower($e['room'] ?? ''), $q)
                    || str_contains(strtolower($e['desc'] ?? ''), $q);
            });
        }

        return $events->sortByDesc('timestamp')->values();
    }

    /**
     * Summary metrics for the top cards.
     */
    public function metrics(): array
    {
        // Only fully Paid invoices count as "total payments"
        $paidTotal = Invoice::where('status', 'Paid')->sum('amount_paid');
        $paidCount = Invoice::where('status', 'Paid')->count();

        return [
            'total_payments'       => (float) $paidTotal,
            'total_payments_count' => $paidCount,
            'past_reservations'    => Reservation::whereIn('status', ['Approved', 'Rejected', 'Expired'])->count(),
            'service_requests'     => MaintenanceRequest::whereIn('status', ['Completed', 'Cancelled'])->count(),
            'approved_requests'    => RoomChangeRequest::whereIn('status', ['Approved', 'Rejected'])->count()
                                    + StayExtensionRequest::whereIn('status', ['Approved', 'Rejected'])->count(),
        ];
    }

    // ============================================================
    // PAYMENTS — only Paid and Rejected
    // ============================================================
    protected function payments(): Collection
    {
        return Invoice::whereIn('status', ['Paid', 'Rejected'])
            ->latest()
            ->get()
            ->map(function (Invoice $inv) {
                $isPaid = $inv->status === 'Paid';

                $methodLabel = $inv->payment_method ?? 'Not Set';
                $paidAmount  = (float) $inv->amount_paid;
                $amountLabel = '₱' . number_format($paidAmount, 0);

                $desc = $isPaid
                    ? 'Payment of ' . $amountLabel . ' via ' . strtolower($methodLabel)
                    : 'Payment rejected — ' . ($inv->receipt_no ? 'ref ' . $inv->receipt_no : 'no reference');

                return [
                    'category'  => 'payment',
                    'icon'      => 'fa-solid fa-peso-sign',
                    'color'     => $isPaid ? 'green' : 'red',
                    'title'     => $inv->description,
                    'tenant'    => $inv->tenant_name,
                    'room'      => 'Room ' . $inv->room_number,
                    'desc'      => $desc,
                    'date'      => $inv->date_paid?->format('M d, Y · h:i A')
                                 ?? $inv->updated_at?->format('M d, Y · h:i A')
                                 ?? $inv->created_at?->format('M d, Y · h:i A'),
                    'timestamp' => $inv->date_paid ?? $inv->updated_at ?? $inv->created_at,
                    'amount'    => $isPaid ? $amountLabel : null,
                    'status'    => $isPaid ? 'paid' : 'rejected',
                    'details'   => [
                        ['label' => 'Invoice ID',       'value' => $inv->transaction_no],
                        ['label' => 'Tenant',           'value' => $inv->tenant_name],
                        ['label' => 'Unit',             'value' => $inv->room_number],
                        ['label' => 'Total Amount',     'value' => $inv->formatted_total],
                        ['label' => 'Amount Paid',      'value' => $inv->formatted_paid],
                        ['label' => 'Payment Method',   'value' => strtoupper($methodLabel)],
                        ['label' => 'Reference Number', 'value' => $inv->receipt_no ?? '—'],
                        ['label' => 'Paid Date',        'value' => $inv->date_paid?->format('M d, Y') ?? '—'],
                    ],
                ];
            });
    }

    // ============================================================
    // RESERVATIONS — only Approved, Rejected, Expired
    // ============================================================
    protected function reservations(): Collection
    {
        return Reservation::whereIn('status', ['Approved', 'Rejected', 'Expired'])
            ->latest()
            ->get()
            ->map(function (Reservation $res) {
                $statusMap = [
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                    'Expired'  => 'expired',
                ];
                $status = $statusMap[$res->status] ?? 'expired';

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

    // ============================================================
    // MAINTENANCE — only Completed and Cancelled
    // ============================================================
    protected function maintenance(): Collection
    {
        return MaintenanceRequest::with(['tenant.user', 'room'])
            ->whereIn('status', ['Completed', 'Cancelled'])
            ->latest()
            ->get()
            ->map(function (MaintenanceRequest $m) {
                $status = $m->status === 'Completed' ? 'completed' : 'cancelled';
                $color  = $m->status === 'Completed' ? 'blue' : 'gray';

                return [
                    'category'  => 'maintenance',
                    'icon'      => 'fa-solid fa-wrench',
                    'color'     => $color,
                    'title'     => $m->title,
                    'tenant'    => $m->tenant->user->name ?? 'Unknown',
                    'room'      => $m->room ? 'Room ' . $m->room->room_number : 'N/A',
                    'desc'      => $m->description,
                    'date'      => $m->created_at?->format('M d, Y · h:i A'),
                    'timestamp' => $m->completed_at ?? $m->updated_at ?? $m->created_at,
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

    // ============================================================
    // ROOM CHANGES — only Approved and Rejected
    // ============================================================
    protected function roomChanges(): Collection
    {
        return RoomChangeRequest::with(['tenant.user'])
            ->whereIn('status', ['Approved', 'Rejected'])
            ->latest()
            ->get()
            ->map(function (RoomChangeRequest $r) {
                $status = $r->status === 'Approved' ? 'approved' : 'rejected';
                $color  = $r->status === 'Approved' ? 'purple' : 'red';

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

    // ============================================================
    // STAY EXTENSIONS — only Approved and Rejected
    // ============================================================
    protected function stayExtensions(): Collection
    {
        return StayExtensionRequest::with(['tenant.user', 'room'])
            ->whereIn('status', ['Approved', 'Rejected'])
            ->latest()
            ->get()
            ->map(function (StayExtensionRequest $s) {
                $status = $s->status === 'Approved' ? 'approved' : 'rejected';
                $color  = $s->status === 'Approved' ? 'yellow' : 'red';

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