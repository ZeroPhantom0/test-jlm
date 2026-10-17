<?php
// app/Http/Controllers/Tenant/TenantBillingController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TenantBillingController extends Controller
{
    /**
     * Display the tenant's billing dashboard.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tenant = Tenant::with('room')
            ->where('user_id', $user->id)
            ->first();

        $invoices = Invoice::where('tenant_id', $tenant?->id)
            ->latest()
            ->get();

        // Unpaid / Partial / Overdue (actionable by tenant)
        $pendingInvoices = $invoices
            ->whereIn('status', ['Unpaid', 'Partial', 'Overdue'])
            ->sortBy('due_date');

        // Submitted for verification (locked from resubmission)
        $verifyingInvoices = $invoices->where('status', 'Pending');

        // Paid history
        $paidInvoices = $invoices
            ->where('status', 'Paid')
            ->sortByDesc('date_paid');

        $totalDue      = (float) $pendingInvoices->sum('balance');
        $paymentStatus = $pendingInvoices->count() > 0 ? 'Unpaid' : 'Paid';
        $nextDueDate   = $pendingInvoices->first()?->due_date;

        $nextInvoice = $pendingInvoices->first();

        $paymentPayload = $nextInvoice ? [
            'reference'    => $nextInvoice->transaction_no,
            'title'        => $nextInvoice->description,
            'amount'       => (float) $nextInvoice->balance,
            'due_date'     => $nextInvoice->due_date?->format('M j, Y') ?? '—',
            'gcash_number' => '0917-123-4567',
        ] : null;

        $summary = [
            'total_due'       => $totalDue,
            'status'          => $paymentStatus,
            'next_due_date'   => $nextDueDate?->format('F j, Y') ?? '—',
            'pending_count'   => $pendingInvoices->count(),
            'verifying_count' => $verifyingInvoices->count(),
            'paid_count'      => $paidInvoices->count(),
        ];

        return view('tenants.billing', compact(
            'tenant',
            'invoices',
            'pendingInvoices',
            'verifyingInvoices',
            'paidInvoices',
            'summary',
            'paymentPayload'
        ));
    }

    /**
     * Submit a payment proof for an invoice.
     * Sets the invoice to "Pending" awaiting admin verification.
     */
    public function pay(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $tenant = Tenant::where('user_id', $user->id)->first();

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

        $invoice = Invoice::where('tenant_id', $tenant->id)
            ->where('transaction_no', $validated['invoice_ref'])
            ->first();

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

        // Store the uploaded receipt (replace old one if any)
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

        // Clean up the uploaded receipt
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