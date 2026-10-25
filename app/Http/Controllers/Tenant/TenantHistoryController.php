<?php
// app/Http/Controllers/Tenant/TenantHistoryController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Reservation;
use App\Models\RoomChangeRequest;
use App\Models\StayExtensionRequest;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantHistoryController extends Controller
{
    /**
     * Display the tenant's activity history.
     *
     * ONLY finalized states are surfaced here:
     *   Payments        → Paid | Rejected
     *   Reservations    → Approved | Rejected | Expired
     *   Maintenance     → Completed | Cancelled
     *   Room Changes    → Approved | Rejected
     *   Stay Extensions → Approved | Rejected
     *
     * In-flight states (Pending, Partial, Unpaid, Overdue, etc.)
     * are excluded from history — those live on their own pages.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tenant = Tenant::with(['room', 'approvedReservation'])
            ->where('user_id', $user->id)
            ->first();

        if (!$tenant) {
            return view('tenants.history', [
                'historyItems' => [],
                'metrics'      => [
                    'total_paid'          => 0,
                    'total_paid_count'    => 0,
                    'past_reservations'   => 0,
                    'service_requests'    => 0,
                ],
            ]);
        }

        $items = collect()
            ->merge($this->payments($tenant))
            ->merge($this->paidReservations($tenant, $user->id))
            ->merge($this->maintenance($tenant))
            ->merge($this->roomChanges($tenant))
            ->merge($this->stayExtensions($tenant))
            ->merge($this->reservations($tenant, $user->id))
            ->sortByDesc('timestamp')
            ->values();

        $historyItems = $items->mapWithKeys(function ($item) {
            return [$item['key'] => $item];
        })->toArray();

        // ── Metrics ─────────────────────────────────────────────
        // Only Paid invoices (not Partial / Pending / Unpaid)
        $paidInvoices = $items
            ->where('cat', 'payments')
            ->where('status', 'paid')
            ->filter(fn ($i) => ($i['payment_state'] ?? null) === 'Paid');

        $pastReservations = $items->where('cat', 'reservation');
        $serviceRequests  = $items->where('cat', 'maintenance')->where('status', 'completed');

        $metrics = [
            'total_paid'        => (float) $paidInvoices->sum(fn ($i) => $this->numericAmount($i['amount'] ?? 0)),
            'total_paid_count'  => $paidInvoices->count(),
            'past_reservations' => $pastReservations->count(),
            'service_requests'  => $serviceRequests->count(),
        ];

        return view('tenants.history', compact('historyItems', 'metrics'));
    }

    // ============================================================
    // PAYMENTS — only Paid and Rejected
    // ============================================================
    private function payments(Tenant $tenant)
    {
        return Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['Paid', 'Rejected'])
            ->latest()
            ->get()
            ->map(function (Invoice $inv) {
                $statusMap = [
                    'Paid'     => ['paid',     'green'],
                    'Rejected' => ['rejected', 'red'],
                ];

                [$historyStatus, $color] = $statusMap[$inv->status] ?? ['paid', 'green'];

                $methodLabel = $inv->payment_method ?? 'Not Set';
                $paidAmount  = (float) $inv->amount_paid;
                $amountLabel = '₱' . number_format($paidAmount, 0);

                $desc = $inv->status === 'Paid'
                    ? 'Payment of ' . $amountLabel . ' via ' . strtolower($methodLabel)
                    : 'Payment rejected — ' . ($inv->receipt_no ? 'ref ' . $inv->receipt_no : 'no reference');

                return [
                    'key'           => 'pay-' . $inv->id,
                    'cat'           => 'payments',
                    'icon'          => 'fa-solid fa-peso-sign',
                    'color'         => $color,
                    'title'         => $inv->description,
                    'desc'          => $desc,
                    'date'          => $inv->date_paid?->format('M d, Y')
                                       ?? $inv->updated_at?->format('M d, Y · h:i A')
                                       ?? $inv->created_at?->format('M d, Y · h:i A'),
                    'timestamp'     => $inv->date_paid ?? $inv->updated_at ?? $inv->created_at,
                    'amount'        => $inv->status === 'Paid' ? $amountLabel : null,
                    'status'        => $historyStatus,
                    'payment_state' => $inv->status,
                    'details'       => [
                        ['Invoice ID',       $inv->transaction_no],
                        ['Unit',             $inv->room_number],
                        ['Total Amount',     '₱' . number_format((float) $inv->total_amount, 2)],
                        ['Amount Paid',      '₱' . number_format($paidAmount, 2)],
                        ['Balance',          '₱' . number_format((float) $inv->balance, 2)],
                        ['Payment Method',   strtoupper($methodLabel)],
                        ['Reference Number', $inv->receipt_no ?? '—'],
                        ['Status',           strtoupper($inv->status)],
                        ['Paid Date',        $inv->date_paid?->format('M d, Y') ?? '—'],
                    ],
                    'receipt' => $inv->status === 'Paid' ? [
                        'reference'   => $inv->receipt_no ?? $inv->transaction_no,
                        'unit'        => $inv->room_number,
                        'description' => $inv->description,
                        'amount'      => $amountLabel,
                        'method'      => $methodLabel,
                        'paid_date'   => $inv->date_paid?->format('M d, Y') ?? '—',
                        'tenant'      => $inv->tenant_name,
                    ] : null,
                ];
            });
    }

    // ============================================================
    // PAID RESERVATION DOWN-PAYMENTS (historical)
    // ============================================================
    private function paidReservations(Tenant $tenant, int $userId)
    {
        return Reservation::where(function ($q) use ($tenant, $userId) {
                $q->where('user_id', $userId)
                  ->orWhere('tenant_name', $tenant->user->name ?? '');
            })
            ->where('status', 'Approved')
            ->whereNotNull('proof_of_payment')
            ->latest()
            ->get()
            ->map(function (Reservation $r) {
                $fee = (float) $r->reservation_fee;
                $amountLabel = '₱' . number_format($fee, 0);

                return [
                    'key'           => 'rsv-paid-' . $r->id,
                    'cat'           => 'payments',
                    'icon'          => 'fa-solid fa-peso-sign',
                    'color'         => 'green',
                    'title'         => 'Reservation Down-Payment — ' . $r->reference_no,
                    'desc'          => 'Paid ' . $amountLabel . ' reservation fee for unit ' . $r->desired_room,
                    'date'          => $r->processed_at?->format('M d, Y · h:i A')
                                       ?? $r->updated_at?->format('M d, Y · h:i A')
                                       ?? $r->created_at?->format('M d, Y · h:i A'),
                    'timestamp'     => $r->processed_at ?? $r->updated_at ?? $r->created_at,
                    'amount'        => $amountLabel,
                    'status'        => 'paid',
                    'payment_state' => 'Paid',
                    'details'       => [
                        ['Reservation ID',   $r->reference_no],
                        ['Reservation No.',  $r->reservation_number],
                        ['Unit',             $r->desired_room],
                        ['Total Charges',    '₱' . number_format((float) $r->total_charges, 2)],
                        ['Down Payment',     $amountLabel],
                        ['Remaining',        '₱' . number_format((float) $r->remaining, 2)],
                        ['Payment Method',   strtoupper($r->payment_method ?? 'GCash')],
                        ['Status',           'PAID'],
                    ],
                    'receipt' => [
                        'reference'   => $r->reference_no,
                        'unit'        => $r->desired_room,
                        'description' => 'Reservation down-payment',
                        'amount'      => $amountLabel,
                        'method'      => $r->payment_method ?? 'GCash',
                        'paid_date'   => $r->processed_at?->format('M d, Y') ?? '—',
                        'tenant'      => $r->tenant_name,
                    ],
                ];
            });
    }

    // ============================================================
    // MAINTENANCE — only Completed and Cancelled
    // ============================================================
    private function maintenance(Tenant $tenant)
    {
        return MaintenanceRequest::with('room')
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['Completed', 'Cancelled'])
            ->latest()
            ->get()
            ->map(function (MaintenanceRequest $m) {
                $status = $m->status === 'Completed' ? 'completed' : 'cancelled';
                $color  = $m->status === 'Completed' ? 'green' : 'red';

                return [
                    'key'       => 'mt-' . $m->id,
                    'cat'       => 'maintenance',
                    'icon'      => 'fa-solid fa-wrench',
                    'color'     => $color,
                    'title'     => $m->title,
                    'desc'      => $m->description,
                    'date'      => $m->created_at?->format('M d, Y · h:i A'),
                    'timestamp' => $m->completed_at ?? $m->updated_at ?? $m->created_at,
                    'amount'    => null,
                    'status'    => $status,
                    'details'   => [
                        ['Request ID',    'sr-' . $m->id],
                        ['Unit',          $m->room?->room_number ?? 'N/A'],
                        ['Category',      strtoupper($m->category)],
                        ['Priority',      strtoupper($m->priority)],
                        ['Assigned Staff', $m->assigned_staff ?? 'N/A'],
                        ['Created At',    $m->created_at?->format('M d, Y') ?? '—'],
                    ],
                ];
            });
    }

    // ============================================================
    // ROOM CHANGES — only Approved and Rejected
    // ============================================================
    private function roomChanges(Tenant $tenant)
    {
        return RoomChangeRequest::where('tenant_id', $tenant->id)
            ->whereIn('status', ['Approved', 'Rejected'])
            ->latest()
            ->get()
            ->map(function (RoomChangeRequest $r) {
                $status = $r->status === 'Approved' ? 'approved' : 'rejected';
                $color  = $r->status === 'Approved' ? 'blue' : 'red';

                return [
                    'key'       => 'rc-' . $r->id,
                    'cat'       => 'room-changes',
                    'icon'      => 'fa-solid fa-house',
                    'color'     => $color,
                    'title'     => 'Room Change: ' . $r->current_room . ' → ' . $r->requested_room,
                    'desc'      => $r->reason,
                    'date'      => $r->created_at?->format('M d, Y · h:i A'),
                    'timestamp' => $r->processed_at ?? $r->created_at,
                    'amount'    => null,
                    'status'    => $status,
                    'details'   => [
                        ['Request ID',  'rc-' . $r->id],
                        ['From Room',   $r->current_room],
                        ['To Room',     $r->requested_room],
                        ['Status',      strtoupper($r->status)],
                        ['Notes',       $r->admin_notes ?? '—'],
                        ['Requested At', $r->created_at?->format('M d, Y') ?? '—'],
                    ],
                ];
            });
    }

    // ============================================================
    // STAY EXTENSIONS — only Approved and Rejected
    // ============================================================
    private function stayExtensions(Tenant $tenant)
    {
        return StayExtensionRequest::with('room')
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['Approved', 'Rejected'])
            ->latest()
            ->get()
            ->map(function (StayExtensionRequest $s) {
                $status = $s->status === 'Approved' ? 'approved' : 'rejected';
                $color  = $s->status === 'Approved' ? 'blue' : 'red';

                return [
                    'key'       => 'se-' . $s->id,
                    'cat'       => 'stay-extensions',
                    'icon'      => 'fa-regular fa-calendar-check',
                    'color'     => $color,
                    'title'     => 'Stay Extension - Unit ' . ($s->room?->room_number ?? 'N/A'),
                    'desc'      => $s->reason,
                    'date'      => $s->created_at?->format('M d, Y · h:i A'),
                    'timestamp' => $s->processed_at ?? $s->created_at,
                    'amount'    => null,
                    'status'    => $status,
                    'details'   => [
                        ['Request ID',          'se-' . $s->id],
                        ['Unit',                $s->room?->room_number ?? 'N/A'],
                        ['Current Lease End',   $s->current_lease_end?->format('M d, Y') ?? '—'],
                        ['Requested Lease End', $s->requested_lease_end?->format('M d, Y') ?? '—'],
                        ['Status',              strtoupper($s->status)],
                        ['Notes',               $s->admin_notes ?? '—'],
                    ],
                ];
            });
    }

    // ============================================================
    // RESERVATIONS — only Approved, Rejected, Expired
    // ============================================================
    private function reservations(Tenant $tenant, int $userId)
    {
        return Reservation::where(function ($q) use ($tenant, $userId) {
                $q->where('user_id', $userId)
                  ->orWhere('tenant_name', $tenant->user->name ?? '');
            })
            ->whereIn('status', ['Approved', 'Rejected', 'Expired'])
            ->latest()
            ->get()
            ->map(function (Reservation $r) {
                $status = match ($r->status) {
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                    'Expired'  => 'expired',
                    default     => 'expired',
                };
                $color = match ($status) {
                    'approved' => 'blue',
                    'rejected' => 'red',
                    'expired'  => 'yellow',
                    default     => 'yellow',
                };

                return [
                    'key'       => 'rsv-' . $r->id,
                    'cat'       => 'reservation',
                    'icon'      => 'fa-regular fa-calendar-check',
                    'color'     => $color,
                    'title'     => 'Reservation - Unit ' . $r->desired_room,
                    'desc'      => $r->reservation_number . ' · ' . $r->status,
                    'date'      => $r->created_at?->format('M d, Y · h:i A'),
                    'timestamp' => $r->processed_at ?? $r->created_at,
                    'amount'    => '₱' . number_format((float) $r->reservation_fee, 0),
                    'status'    => $status,
                    'details'   => [
                        ['Reservation ID',   $r->reference_no],
                        ['Tenant Name',      $r->tenant_name],
                        ['Unit Requested',   $r->desired_room],
                        ['Room Type',        $r->room_bed_id ? 'Bed ' . $r->room_bed_id : 'Solo Room'],
                        ['Deposit Amount',   '₱' . number_format((float) $r->reservation_fee, 2)],
                        ['Reservation Date', $r->check_in?->format('M d, Y') ?? '—'],
                        ['Request Date',     $r->created_at?->format('M d, Y') ?? '—'],
                        ['Status',           strtoupper($r->status)],
                        ['Notes',            $r->admin_notes ?? '—'],
                    ],
                ];
            });
    }

    // ============================================================
    // HELPERS
    // ============================================================
    private function numericAmount($str): float
    {
        if (!$str) return 0;
        return (float) preg_replace('/[^\d.]/', '', (string) $str);
    }
}