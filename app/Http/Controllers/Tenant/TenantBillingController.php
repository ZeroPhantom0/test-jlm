<?php
// app/Http/Controllers/Tenant/TenantBillingController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TenantBillingController extends Controller
{
    public function __construct(protected BillingService $billing) {}

    /**
     * Display the tenant's billing dashboard.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tenant = Tenant::with(['room', 'approvedReservation'])
            ->where('user_id', $user->id)
            ->first();

        $invoices = Invoice::where('tenant_id', $tenant?->id)
            ->latest()
            ->get();

        $pendingInvoices = $invoices
            ->whereIn('status', ['Unpaid', 'Partial', 'Overdue'])
            ->sortBy('due_date')
            ->values();

        $verifyingInvoices = $invoices->where('status', 'Pending')->values();

        $paidInvoices = $invoices
            ->where('status', 'Paid')
            ->sortByDesc('date_paid')
            ->values();

        // Canonical dues (invoices + reservation remaining)
        $dues = $this->billing->duesForTenant($tenant);
        $totalDue = collect($dues)->sum('amount');

        $reservationDue = collect($dues)
            ->firstWhere('kind', 'reservation-remaining');

        $reservationRemaining = $reservationDue['amount'] ?? 0;

        // Payment status
        $paymentStatus = $totalDue > 0 ? 'Unpaid' : 'Paid';

        // Next due date: earliest of invoice due dates or reservation check-in
        $nextDueDate = $pendingInvoices->first()?->due_date
            ?? ($reservationDue['due_date'] ?? null);

        // Payment modal payload — prefer earliest invoice, else reservation remaining
        $nextInvoice = $pendingInvoices->first();

        if ($nextInvoice) {
            $paymentPayload = [
                'reference'    => $nextInvoice->transaction_no,
                'title'        => $nextInvoice->description,
                'amount'       => (float) $nextInvoice->balance,
                'due_date'     => $nextInvoice->due_date?->format('M j, Y') ?? '—',
                'gcash_number' => '0917-123-4567',
            ];
        } elseif ($reservationDue) {
            $paymentPayload = [
                'reference'    => $reservationDue['reference'],
                'title'        => $reservationDue['title'],
                'amount'       => $reservationDue['amount'],
                'due_date'     => $reservationDue['due_date']?->format('M j, Y') ?? '—',
                'gcash_number' => '0917-123-4567',
            ];
        } else {
            $paymentPayload = null;
        }

        $summary = [
            'total_due'             => $totalDue,
            'status'                => $paymentStatus,
            'next_due_date'         => $nextDueDate?->format('F j, Y') ?? '—',
            'pending_count'         => $pendingInvoices->count() + ($reservationRemaining > 0 ? 1 : 0),
            'verifying_count'       => $verifyingInvoices->count(),
            'paid_count'            => $paidInvoices->count(),
            'reservation_remaining' => $reservationRemaining,
        ];

        return view('tenants.billing', compact(
            'tenant',
            'invoices',
            'pendingInvoices',
            'verifyingInvoices',
            'paidInvoices',
            'summary',
            'paymentPayload',
            'reservationRemaining',
            'reservationDue'
        ));
    }

    /**
     * Submit a payment proof for an invoice OR a reservation remaining.
     */
    public function pay(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tenant = Tenant::with('approvedReservation')->where('user_id', $user->id)->first();

        if (!$tenant) {
            return response()->json([
                'message' => 'You are not yet assigned as a tenant.',
            ], 422);
        }

        $validated = $request->validate([
            'invoice_ref'      => ['required', 'string'],
            'payment_method'   => ['required', Rule::in(['gcash', 'bank', 'counter'])],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'receipt'          => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
        ]);

        // Try to find existing invoice
        $invoice = Invoice::where('tenant_id', $tenant->id)
            ->where('transaction_no', $validated['invoice_ref'])
            ->first();

        // If not, check if it's a reservation reference — materialize it
        if (
            !$invoice
            && $tenant->approvedReservation
            && $tenant->approvedReservation->reference_no === $validated['invoice_ref']
        ) {

            $approved = $tenant->approvedReservation;

            if ($approved->status !== 'Approved' || (float) $approved->remaining <= 0) {
                return response()->json(['message' => 'No remaining balance to pay.'], 422);
            }

            $invoice = $this->billing->materializeReservationRemaining($approved, $tenant);
        }

        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found.'], 404);
        }

        if ($invoice->status === 'Paid') {
            return response()->json(['message' => 'This invoice is already paid.'], 422);
        }

        if ($invoice->status === 'Pending') {
            return response()->json([
                'message' => 'A payment is already awaiting verification for this invoice.',
            ], 422);
        }

        $receiptPath = $invoice->receipt_path;

        if ($request->hasFile('receipt')) {
            if ($receiptPath) {
                Storage::disk('public')->delete($receiptPath);
            }
            $receiptPath = $request->file('receipt')->store('receipts/' . $tenant->id, 'public');
        }

        $paymentMethodLabel = match ($validated['payment_method']) {
            'gcash'   => 'GCash',
            'bank'    => 'Bank Transfer',
            'counter' => 'Over The Counter',
        };

        $invoice->update([
            'payment_method' => $paymentMethodLabel,
            'status'         => 'Pending',
            'receipt_no'     => $validated['reference_number'] ?: $invoice->receipt_no,
            'receipt_path'   => $receiptPath,
            'date_paid'      => now()->toDateString(), // <-- Record the submission date immediately
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment submitted. Awaiting admin verification.',
            'data'    => $invoice->fresh()->toReactArray(),
        ]);
    }

    /**
     * Cancel a pending payment (allows tenant to resubmit).
     */
    public function cancelPayment(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found.'], 404);
        }

        $validated = $request->validate([
            'invoice_ref' => ['required', 'string'],
        ]);

        $invoice = Invoice::where('tenant_id', $tenant->id)
            ->where('transaction_no', $validated['invoice_ref'])
            ->first();

        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found.'], 404);
        }

        if ($invoice->status !== 'Pending') {
            return response()->json([
                'message' => 'Only pending payments can be cancelled.',
            ], 422);
        }

        if ($invoice->receipt_path) {
            Storage::disk('public')->delete($invoice->receipt_path);
        }

        $invoice->update([
            'status'       => $invoice->amount_paid > 0 ? 'Partial' : 'Unpaid',
            'receipt_path' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pending payment cancelled. You can now resubmit.',
        ]);
    }
}
