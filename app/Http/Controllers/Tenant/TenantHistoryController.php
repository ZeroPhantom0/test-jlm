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
     * Display the tenant's activity history (real data, tenant-scoped).
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tenant = Tenant::with(['room', 'approvedReservation'])
            ->where('user_id', $user->id)
            ->first();

        if (!$tenant) {
            // No tenant record yet — show empty page
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

        // ============================================================
        // Build the unified history feed
        // ============================================================
        $items = collect()
            ->merge($this->payments($tenant))
            ->merge($this->paidReservations($tenant, $user->id))
            ->merge($this->maintenance($tenant))
            ->merge($this->roomChanges($tenant))
            ->merge($this->stayExtensions($tenant))
            ->merge($this->reservations($tenant, $user->id))
            ->sortByDesc('timestamp')
            ->values();

        // Convert to keyed map for React modal
        $historyItems = $items->mapWithKeys(function ($item) {
            return [$item['key'] => $item];
        })->toArray();

        // ============================================================
        // Metrics
        // ============================================================
        $paidInvoices      = $items->where('cat', 'payments')->where('status', 'paid');
        $pastReservations  = $items->where('cat', 'reservation');
        $serviceRequests   = $items->where('cat', 'maintenance')->where('status', 'completed');

        $metrics = [
            'total_paid'        => (float) $paidInvoices->sum(fn ($i) => $this->numericAmount($i['amount'] ?? 0)),
            'total_paid_count'  => $paidInvoices->count(),
            'past_reservations' => $pastReservations->count(),
            'service_requests'  => $serviceRequests->count(),
        ];

        return view('tenants.history', compact('historyItems', 'metrics'));
    }

    // ============================================================
    // SOURCES
    // ============================================================

    /**
     * Every invoice that has any money attached:
     *  - Paid    → fully paid (a payment happened)
     *  - Partial → some money was paid, some remains
     *  - Pending → tenant submitted proof, admin hasn't verified yet
     */
    private function payments(Tenant $tenant)
    {
        return Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['Paid', 'Partial', 'Pending'])
            ->latest()
            ->get()
            ->map(function (Invoice $inv) {
                $isPaid     = $inv->status === 'Paid';
                $isPending  = $inv->status === 'Pending';

                $methodLabel = $inv->payment_method ?? 'Not Set';
                $paidAmount  = (float) $inv->amount_paid;
                $amountLabel = '₱' . number_format($paidAmount, 0);

                if ($isPaid) {
                    $status = 'paid';
                    $color  = 'green';
                } elseif ($isPending) {
                    $status = 'pending';
                    $color  = 'blue';
                } else {
                    $status = 'paid';
                    $color  = 'green';
                }

                $desc = match (true) {
                    $isPaid    => 'Payment of ' . $amountLabel . ' via ' . strtolower($methodLabel),
                    $isPending => 'Payment proof submitted — awaiting verification',
                    default    => 'Partial payment of ' . $amountLabel . ' via ' . strtolower($methodLabel)
                                  . ' — remaining ₱' . number_format((float) $inv->balance, 0),
                };

                return [
                    'key'      => 'pay-' . $inv->id,
                    'cat'      => 'payments',
                    'icon'     => 'fa-solid fa-peso-sign',
                    'color'    => $color,
                    'title'    => $inv->description,
                    'desc'     => $desc,
                    'date'     => $inv->date_paid?->format('M d, Y')
                                  ?? $inv->updated_at?->format('M d, Y · h:i A')
                                  ?? $inv->created_at?->format('M d, Y · h:i A'),
                    'timestamp'=> $inv->date_paid ?? $inv->updated_at ?? $inv->created_at,
                    'amount'   => $isPaid ? $amountLabel : null,
                    'status'   => $status,
                    'details'  => [
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
                    'receipt' => $isPaid ? [
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

    /**
     * Paid reservation down-payments — SHOW them as paid history even
     * though they don't live in the invoices table.
     *
     * Only Approved reservations with a verified proof of payment.
     */
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
                    'key'      => 'rsv-paid-' . $r->id,
                    'cat'      => 'payments',
                    'icon'     => 'fa-solid fa-peso-sign',
                    'color'    => 'green',
                    'title'    => 'Reservation Down-Payment — ' . $r->reference_no,
                    'desc'     => 'Paid ' . $amountLabel . ' reservation fee for unit ' . $r->desired_room,
                    'date'     => $r->processed_at?->format('M d, Y · h:i A')
                                  ?? $r->updated_at?->format('M d, Y · h:i A')
                                  ?? $r->created_at?->format('M d, Y · h:i A'),
                    'timestamp'=> $r->processed_at ?? $r->updated_at ?? $r->created_at,
                    'amount'   => $amountLabel,
                    'status'   => 'paid',
                    'details'  => [
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

    private function maintenance(Tenant $tenant)
    {
        return MaintenanceRequest::with('room')
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->get()
            ->map(function (MaintenanceRequest $m) {
                $status = match ($m->status) {
                    'Completed' => 'completed',
                    'Cancelled' => 'cancelled',
                    default     => 'pending',
                };
                $color = match ($status) {
                    'completed' => 'green',
                    'cancelled' => 'red',
                    default     => 'yellow',
                };

                return [
                    'key'       => 'mt-' . $m->id,
                    'cat'       => 'maintenance',
                    'icon'      => 'fa-solid fa-wrench',
                    'color'     => $color,
                    'title'     => $m->title,
                    'desc'      => $m->description,
                    'date'      => $m->created_at?->format('M d, Y · h:i A'),
                    'timestamp' => $m->completed_at ?? $m->created_at,
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

    private function roomChanges(Tenant $tenant)
    {
        return RoomChangeRequest::where('tenant_id', $tenant->id)
            ->latest()
            ->get()
            ->map(function (RoomChangeRequest $r) {
                $status = match ($r->status) {
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                    default     => 'pending',
                };
                $color = match ($status) {
                    'approved' => 'blue',
                    'rejected' => 'red',
                    default     => 'yellow',
                };

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

    private function stayExtensions(Tenant $tenant)
    {
        return StayExtensionRequest::with('room')
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->get()
            ->map(function (StayExtensionRequest $s) {
                $status = match ($s->status) {
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                    default     => 'pending',
                };
                $color = match ($status) {
                    'approved' => 'blue',
                    'rejected' => 'red',
                    default     => 'yellow',
                };

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

    private function reservations(Tenant $tenant, int $userId)
    {
        // Show reservations that belong to the user OR match their name
        return Reservation::where(function ($q) use ($tenant, $userId) {
                $q->where('user_id', $userId)
                  ->orWhere('tenant_name', $tenant->user->name ?? '');
            })
            ->latest()
            ->get()
            ->map(function (Reservation $r) {
                $status = match ($r->status) {
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                    'Expired'  => 'expired',
                    default     => 'pending',
                };
                $color = match ($status) {
                    'approved' => 'blue',
                    'rejected' => 'red',
                    'expired'  => 'yellow',
                    default     => 'blue',
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