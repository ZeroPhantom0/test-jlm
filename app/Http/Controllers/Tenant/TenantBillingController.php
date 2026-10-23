<?php
// app/Http/Controllers/Tenant/TenantBillingController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TenantBillingController extends Controller
{
    public function index()
    {
        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        // ── Outstanding invoices ─────────────────────────────────────────
        // Pending invoices are shown separately below (awaiting verification).
        // Unpaid / Partial / Overdue are what the tenant still owes.
        $pendingInvoices = Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['Unpaid', 'Overdue', 'Partial'])
            ->orderBy('due_date')
            ->get();

        // ── Awaiting verification ────────────────────────────────────────
        $verifyingInvoices = Invoice::where('tenant_id', $tenant->id)
            ->where('status', 'Pending')
            ->latest()
            ->get()
            ->map(function ($inv) {
                $inv->receipt_url = $inv->receipt_path
                    ? Storage::url($inv->receipt_path)
                    : null;
                return $inv;
            });

        // ── Paid history ─────────────────────────────────────────────────
        $paidInvoices = Invoice::where('tenant_id', $tenant->id)
            ->where('status', 'Paid')
            ->latest('date_paid')
            ->get()
            ->map(function ($inv) {
                $inv->receipt_url = $inv->receipt_path
                    ? Storage::url($inv->receipt_path)
                    : null;
                return $inv;
            });

        // ── Reservation remaining — ONLY if it isn't already invoiced ────
        // After approval, an invoice is created for the remaining balance.
        // We only want to surface a reservation-remaining "due" here when
        // there is *no* invoice yet (edge case: old data, or a reservation
        // approved before the invoice-sync logic existed).
        $reservationDue      = null;
        $unbilledRemaining   = 0.0;

        $approvedReservation = Reservation::where('user_id', $user->id)
            ->where('status', 'Approved')
            ->where('remaining', '>', 0)
            ->latest()
            ->first();

        if ($approvedReservation) {
            $billedRemaining = (float) Invoice::where('tenant_id', $tenant->id)
                ->whereIn('status', ['Unpaid', 'Partial', 'Overdue', 'Pending'])
                ->where(function ($q) {
                    $q->where('description', 'like', '%remaining%')
                      ->orWhere('description', 'like', '%reservation%');
                })
                ->sum('balance');

            $unbilledRemaining = max(
                0,
                (float) $approvedReservation->remaining - $billedRemaining
            );

            if ($unbilledRemaining > 0.005) {
                $reservationDue = [
                    'reference' => $approvedReservation->reference_no,
                    'title'     => 'Reservation Remaining – ' . $approvedReservation->desired_room,
                    'amount'    => $unbilledRemaining,
                    'due_date'  => $approvedReservation->check_in,
                ];
            }
        }

        // ── Summary cards ────────────────────────────────────────────────
        $totalDue = $pendingInvoices->sum('balance') + $unbilledRemaining;

        $status = 'Paid';
        if ($pendingInvoices->contains('status', 'Overdue')) {
            $status = 'Overdue';
        } elseif ($pendingInvoices->count() || $unbilledRemaining > 0.005) {
            $status = 'Unpaid';
        }

        $nextDue = $pendingInvoices->first()?->due_date
            ?? ($reservationDue['due_date'] ?? null);

        $summary = [
            'total_due'              => $totalDue,
            'reservation_remaining'  => $unbilledRemaining,
            'status'                 => $status,
            'next_due_date'          => $nextDue
                ? $nextDue->format('M j, Y')
                : 'No pending dues',
        ];

        // First pending invoice becomes the default modal payload
        $next = $pendingInvoices->first();
        $paymentPayload = $next ? [
            'reference'    => $next->transaction_no,
            'title'        => $next->description,
            'amount'       => (float) $next->balance,
            'due_date'     => $next->due_date?->format('M j, Y') ?? '—',
            'gcash_number' => '0917-123-4567',
        ] : null;

        return view('tenants.billing', compact(
            'summary',
            'pendingInvoices',
            'verifyingInvoices',
            'paidInvoices',
            'reservationDue',
            'paymentPayload'
        ));
    }

    /**
     * Tenant submits a payment (GCash / Bank / Counter).
     * POST /tenants/billing/pay
     */
    public function pay(Request $request)
    {
        $validated = $request->validate([
            'invoice_ref'      => ['required', 'string'],
            'payment_method'   => ['required', 'in:gcash,bank,counter,GCash,Bank Transfer,Over The Counter'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'receipt'          => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif,bmp', 'max:10240'],
        ], [
            'receipt.image' => 'The receipt must be an image file (JPG, PNG, WEBP, GIF, BMP).',
            'receipt.mimes' => 'The receipt must be a JPG, PNG, WEBP, GIF, or BMP file.',
            'receipt.max'   => 'The receipt must be 10 MB or smaller.',
        ]);

        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        $method = match ($validated['payment_method']) {
            'gcash', 'GCash'              => 'GCash',
            'bank',  'Bank Transfer'      => 'Bank Transfer',
            'counter', 'Over The Counter' => 'Over The Counter',
            default                       => 'GCash',
        };

        DB::beginTransaction();
        try {
            // ── 1) Try invoices first ────────────────────────────────
            $invoice = Invoice::where('transaction_no', $validated['invoice_ref'])
                ->where('tenant_id', $tenant->id)
                ->whereIn('status', ['Unpaid', 'Overdue', 'Partial'])
                ->lockForUpdate()
                ->first();

            // ── 2) Otherwise try reservations ────────────────────────
            $reservation = null;
            if (! $invoice) {
                $reservation = Reservation::where('reference_no', $validated['invoice_ref'])
                    ->where('user_id', $user->id)
                    ->where('status', 'Approved')
                    ->where('remaining', '>', 0)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $invoice && ! $reservation) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Invoice or reservation not found, or already paid/pending.',
                ], 404);
            }

            // ── 3) Store receipt ────────────────────────────────────
            $receiptPath = null;
            if ($request->hasFile('receipt')) {
                $receiptPath = $request->file('receipt')->store('receipts', 'public');
            }

            if ($invoice) {
                $invoice->update([
                    'payment_method' => $method,
                    'receipt_no'     => $validated['reference_number'] ?? $invoice->receipt_no,
                    'receipt_path'   => $receiptPath ?? $invoice->receipt_path,
                    'status'         => 'Pending',
                ]);
            } else {
                // Reservation: create a corresponding invoice so admin
                // can verify it through the same billing table.
                $invoice = Invoice::create([
                    'transaction_no' => 'INV-' . strtoupper(uniqid()),
                    'tenant_id'      => $tenant->id,
                    'room_id'        => $reservation->room_id,
                    'tenant_name'    => $reservation->tenant_name,
                    'room_number'    => $reservation->desired_room,
                    'description'    => 'Reservation balance – ' . $reservation->reference_no,
                    'total_amount'   => $reservation->remaining,
                    'amount_paid'    => 0,
                    'balance'        => $reservation->remaining,
                    'due_date'       => $reservation->check_in,
                    'payment_method' => $method,
                    'receipt_no'     => $validated['reference_number'] ?? null,
                    'receipt_path'   => $receiptPath,
                    'status'         => 'Pending',
                ]);
                $reservation->update([
                    'payment_method'   => $method,
                    'proof_of_payment' => $receiptPath,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Payment submitted. Awaiting admin verification.',
                'invoice' => $invoice->fresh(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'message' => 'Failed to submit payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tenant cancels their pending submission so they can resubmit.
     */
    public function cancelPayment(Request $request)
    {
        $validated = $request->validate([
            'invoice_ref' => ['required', 'string'],
        ]);

        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        $invoice = Invoice::where('transaction_no', $validated['invoice_ref'])
            ->where('tenant_id', $tenant->id)
            ->where('status', 'Pending')
            ->first();

        if (! $invoice) {
            return back()->with('error', 'Pending invoice not found.');
        }

        if ($invoice->receipt_path && Storage::disk('public')->exists($invoice->receipt_path)) {
            Storage::disk('public')->delete($invoice->receipt_path);
        }

        $revertStatus = $invoice->amount_paid > 0 ? 'Partial' : 'Unpaid';
        $invoice->update([
            'status'       => $revertStatus,
            'receipt_path' => null,
            'receipt_no'   => null,
        ]);

        return back()->with('success', 'Submission cancelled. You can pay again.');
    }
}