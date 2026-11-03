<?php
// app/Services/BillingService.php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;

class BillingService
{
    /**
     * The canonical list of "amount due" sources for a tenant.
     *
     * Returns an array of normalized "due" items combining:
     *   - Invoices with status in (Unpaid, Partial, Overdue)
     *   - Approved reservation `remaining` (only the unbilled portion)
     *
     * Each item shape:
     *   [
     *     'kind'       => 'invoice' | 'reservation-remaining',
     *     'reference'  => string  (transaction_no or reservation reference_no),
     *     'invoice_id' => ?int,
     *     'reservation_id' => ?int,
     *     'title'      => string,
     *     'description'=> string,
     *     'amount'     => float,  (what's still owed)
     *     'total'      => float,
     *     'paid'       => float,
     *     'balance'    => float,  (same as amount)
     *     'due_date'   => ?Carbon,
     *     'status'     => string,
     *     'invoice'    => ?Invoice,
     *     'reservation'=> ?Reservation,
     *   ]
     */
    public function duesForTenant(?Tenant $tenant): array
    {
        if (!$tenant) {
            return [];
        }

        $dues = [];

        // 1. Outstanding invoices
        $unpaid = Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['Unpaid', 'Partial', 'Overdue'])
            ->orderBy('due_date')
            ->get();

        foreach ($unpaid as $inv) {
            $dues[] = [
                'kind'           => 'invoice',
                'reference'      => $inv->transaction_no,
                'invoice_id'     => $inv->id,
                'reservation_id' => null,
                'title'          => $inv->description,
                'description'    => $inv->description,
                'amount'         => (float) $inv->balance,
                'total'          => (float) $inv->total_amount,
                'paid'           => (float) $inv->amount_paid,
                'balance'        => (float) $inv->balance,
                'due_date'       => $inv->due_date,
                'status'         => $inv->status,
                'invoice'        => $inv,
                'reservation'    => null,
            ];
        }

        // 2. Reservation remaining (unbilled portion only)
        $approved = $tenant->approvedReservation;

        if ($approved
            && $approved->status === 'Approved'
            && (float) $approved->remaining > 0) {

            // How much of the remaining has already been invoiced?
            $billedRemaining = (float) Invoice::where('tenant_id', $tenant->id)
                ->where(function ($q) use ($approved) {
                    $q->where('description', 'like', '%remaining%')
                      ->orWhere('description', 'like', '%' . $approved->reference_no . '%');
                })
                ->sum('balance');

            $unbilledRemaining = max(0, (float) $approved->remaining - $billedRemaining);

            if ($unbilledRemaining > 0.005) {
                $dues[] = [
                    'kind'           => 'reservation-remaining',
                    'reference'      => $approved->reference_no,
                    'invoice_id'     => null,
                    'reservation_id' => $approved->id,
                    'title'          => 'Remaining Balance — Reservation ' . $approved->reference_no,
                    'description'    => 'Unpaid balance from reservation ' . $approved->reservation_number,
                    'amount'         => $unbilledRemaining,
                    'total'          => (float) $approved->total_charges,
                    'paid'           => (float) $approved->reservation_fee,
                    'balance'        => $unbilledRemaining,
                    'due_date'       => $approved->check_in,
                    'status'         => 'Unpaid',
                    'invoice'        => null,
                    'reservation'    => $approved,
                ];
            }
        }

        return $dues;
    }

    /**
     * Total outstanding balance for a tenant.
     */
    public function totalDue(?Tenant $tenant): float
    {
        return collect($this->duesForTenant($tenant))
            ->sum(fn ($d) => $d['amount']);
    }

    /**
     * Total amount collected from a tenant (Paid invoices only).
     */
    public function totalPaid(?Tenant $tenant): float
    {
        if (!$tenant) {
            return 0.0;
        }

        return (float) Invoice::where('tenant_id', $tenant->id)
            ->where('status', 'Paid')
            ->sum('amount_paid');
    }

    /**
     * Convert a reservation-remaining due into an Unpaid invoice so it
     * becomes a real billing record the tenant can pay via the modal.
     *
     * Idempotent — safe to call multiple times.
     */
    public function materializeReservationRemaining(Reservation $reservation, ?Tenant $tenant): ?Invoice
    {
        if ((float) $reservation->remaining <= 0) {
            return null;
        }

        // Already invoiced?
        $existing = Invoice::where('tenant_id', $tenant?->id)
            ->where(function ($q) use ($reservation) {
                $q->where('description', 'like', '%remaining%')
                  ->orWhere('description', 'like', '%' . $reservation->reference_no . '%');
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        $roomNumber = $reservation->room?->room_number
            ?? $reservation->desired_room
            ?? 'N/A';

        $days = $reservation->stay_duration ?? 1;
        $rate = (float) ($reservation->room?->daily_rent
            ?? ($reservation->total_charges / max($days, 1)));

        return Invoice::create([
            'transaction_no' => Invoice::generateTransactionNo(),
            'tenant_id'      => $tenant?->id,
            'room_id'        => $reservation->room_id,
            'tenant_name'    => $reservation->tenant_name,
            'room_number'    => $roomNumber,
            'description'    => 'Remaining Balance — Reservation ' . $reservation->reference_no,
            'check_in'       => $reservation->check_in,
            'check_out'      => $reservation->check_out,
            'rate_per_day'   => $rate,
            'days'           => $days,
            'total_amount'   => (float) $reservation->total_charges,
            'amount_paid'    => (float) $reservation->reservation_fee,
            'balance'        => (float) $reservation->remaining,
            'due_date'       => now()->addDays(7)->toDateString(),
            'payment_method' => $reservation->payment_method ?? 'Not Set',
            'status'         => 'Unpaid',
        ]);
    }

    // ============================================================
    // RECEIPT ARCHIVING (soft-delete support)
    // ============================================================

    /**
     * Move a receipt/proof file from the public disk into a private
     * archive directory instead of deleting it.
     *
     * Returns the new relative path (inside storage/app/private/rejected/)
     * or null if the source file didn't exist.
     *
     * Safe to call repeatedly — if the file is already gone, returns null.
     */
    public static function archiveReceipt(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $public = Storage::disk('public');

        if (!$public->exists($path)) {
            return null;
        }

        $filename = basename($path);
        $archived = 'rejected/' . now()->format('Y/m') . '/' . $filename;

        // Copy contents to private disk, then remove from public.
        Storage::disk('local')->put($archived, $public->get($path));
        $public->delete($path);

        return $archived;
    }

    /**
     * Archive a reservation's uploaded proof-of-payment file.
     * Delegates to archiveReceipt() so both use the same archive layout.
     */
    public static function archiveProofOfPayment(?string $path): ?string
    {
        return self::archiveReceipt($path);
    }
}