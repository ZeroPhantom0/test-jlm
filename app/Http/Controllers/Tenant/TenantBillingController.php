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

        $pendingInvoices = Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['Unpaid', 'Overdue', 'Partial'])
            ->orderBy('due_date')
            ->get();

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

        $reservationDue      = null;
        $unbilledRemaining   = 0.0;

        $approvedReservation = Reservation::where('user_id', $user->id)
            ->where('status', 'Approved')
            ->where('remaining', '>', 0)
            ->latest()
            ->first();

        if ($approvedReservation) {
            $billedRemaining = (float) Invoice::where('tenant_id', $tenant->id)
                ->whereIn('status', ['Unpaid', 'Partial', 'Overdue', 'Pending', 'Paid'])
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
            $invoice = Invoice::where('transaction_no', $validated['invoice_ref'])
                ->where('tenant_id', $tenant->id)
                ->whereIn('status', ['Unpaid', 'Overdue', 'Partial'])
                ->lockForUpdate()
                ->first();

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

            $receiptPath = null;
            if ($request->hasFile('receipt')) {
                $receiptPath = $request->file('receipt')->store('receipts', 'public');
            }

            if ($invoice) {
                // Add the current balance to the amount_paid
                $newAmountPaid = (float) $invoice->amount_paid + (float) $invoice->balance;
                
                $invoice->update([
                    'payment_method' => $method,
                    'receipt_no'     => $validated['reference_number'] ?? $invoice->receipt_no,
                    'receipt_path'   => $receiptPath ?? $invoice->receipt_path,
                    'status'         => 'Pending',
                    'amount_paid'    => $newAmountPaid, 
                ]);
            } else {
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

        // Revert the amount_paid back by subtracting the current balance
        $revertedAmount = max(0, (float) $invoice->amount_paid - (float) $invoice->balance);
        
        // Determine if it should go back to Partial or Unpaid
        $revertStatus = $revertedAmount > 0 ? 'Partial' : 'Unpaid';

        $invoice->update([
            'status'       => $revertStatus,
            'receipt_path' => null,
            'receipt_no'   => null,
            'amount_paid'  => $revertedAmount,
        ]);

        return back()->with('success', 'Submission cancelled. You can pay again.');
    }
}