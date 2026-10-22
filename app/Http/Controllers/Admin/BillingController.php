<?php
// app/Http/Controllers/Admin/BillingController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\AuditLogger;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function __construct(protected BillingService $billing) {}

    /**
     * Display the billing & payments list.
     * 
     * Paid invoices are hidden because they are archived in the System History.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'payment_method']);

        // Hide Paid invoices — they are moved to History.
        $invoices = Invoice::filter($filters)
            ->where('status', '!=', 'Paid')
            ->latest()
            ->get();

        $startOfMonth = now()->startOfMonth();

        $metrics = [
            'collected' => Invoice::where('status', 'Paid')
                ->where('date_paid', '>=', $startOfMonth)
                ->sum('amount_paid'),
            'pending'   => Invoice::whereIn('status', ['Pending', 'Partial', 'Unpaid'])
                ->sum('balance'),
            'overdue'   => Invoice::where('status', 'Overdue')
                ->sum('balance'),
        ];

        $invoicesForReact = $invoices->mapWithKeys(function ($inv) {
            return [$inv->transaction_no => $inv->toReactArray()];
        })->toArray();

        return view('admin.billing', compact('invoices', 'metrics', 'filters', 'invoicesForReact'));
    }

    /**
     * Store a newly created invoice.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_name'    => ['required', 'string', 'max:120'],
            'room_number'    => ['required', 'string', 'max:50'],
            'description'    => ['required', 'string', 'max:255'],
            'check_in'       => ['nullable', 'date'],
            'check_out'      => ['nullable', 'date', 'after:check_in'],
            'rate_per_day'   => ['required', 'numeric', 'min:0'],
            'days'           => ['required', 'integer', 'min:1'],
            'amount_paid'    => ['nullable', 'numeric', 'min:0'],
            'due_date'       => ['nullable', 'date'],
            'payment_method' => ['nullable', Rule::in(['Not Set', 'GCash', 'Over The Counter', 'Bank Transfer'])],
        ]);

        $total   = $validated['rate_per_day'] * $validated['days'];
        $paid    = $validated['amount_paid'] ?? 0;
        $balance = max(0, $total - $paid);

        $status = 'Unpaid';
        if ($paid >= $total && $total > 0) {
            $status = 'Paid';
        } elseif ($paid > 0) {
            $status = 'Partial';
        }

        $invoice = Invoice::create([
            ...$validated,
            'transaction_no' => Invoice::generateTransactionNo(),
            'total_amount'   => $total,
            'amount_paid'    => $paid,
            'balance'        => $balance,
            'status'         => $status,
            'payment_method' => $validated['payment_method'] ?? 'Not Set',
            'date_paid'      => $status === 'Paid' ? now()->toDateString() : null,
        ]);

        AuditLogger::log([
            'action'       => 'CREATE',
            'module'       => 'BILLING',
            'description'  => 'Created invoice ' . $invoice->transaction_no . ' for ' . $invoice->tenant_name
                             . ' (₱' . number_format((float) $invoice->total_amount) . ')',
            'subject_type' => Invoice::class,
            'subject_id'   => $invoice->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invoice created successfully.',
            'data'    => $invoice,
        ]);
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice)
    {
        return response()->json([
            'success' => true,
            'data'    => $invoice->toReactArray(),
        ]);
    }

    /**
     * Update the specified invoice.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'tenant_name'    => ['sometimes', 'string', 'max:120'],
            'room_number'    => ['sometimes', 'string', 'max:50'],
            'description'    => ['sometimes', 'string', 'max:255'],
            'check_in'       => ['nullable', 'date'],
            'check_out'      => ['nullable', 'date', 'after:check_in'],
            'rate_per_day'   => ['sometimes', 'numeric', 'min:0'],
            'days'           => ['sometimes', 'integer', 'min:1'],
            'amount_paid'    => ['sometimes', 'numeric', 'min:0'],
            'due_date'       => ['nullable', 'date'],
            'payment_method' => ['nullable', Rule::in(['Not Set', 'GCash', 'Over The Counter', 'Bank Transfer'])],
            'status'         => ['sometimes', Rule::in(['Unpaid', 'Overdue', 'Partial', 'Pending', 'Paid'])],
        ]);

        $rate    = $validated['rate_per_day'] ?? $invoice->rate_per_day;
        $days    = $validated['days'] ?? $invoice->days;
        $paid    = $validated['amount_paid'] ?? $invoice->amount_paid;

        $total   = $rate * $days;
        $balance = max(0, $total - $paid);

        if (!isset($validated['status'])) {
            if ($paid >= $total && $total > 0) {
                $validated['status'] = 'Paid';
            } elseif ($paid > 0) {
                $validated['status'] = 'Partial';
            } else {
                $validated['status'] = 'Unpaid';
            }
        }

        if (($validated['status'] ?? null) === 'Paid' && $invoice->status !== 'Paid') {
            $validated['verified_by'] = $request->user()->id;
            $validated['verified_at'] = now();
            $validated['date_paid']   = now()->toDateString();
        }

        $invoice->update([
            ...$validated,
            'total_amount' => $total,
            'amount_paid'  => $paid,
            'balance'      => $balance,
        ]);

        AuditLogger::log([
            'action'       => 'UPDATE',
            'module'       => 'BILLING',
            'description'  => 'Updated invoice ' . $invoice->transaction_no . ' for ' . $invoice->tenant_name,
            'subject_type' => Invoice::class,
            'subject_id'   => $invoice->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated successfully.',
            'data'    => $invoice->fresh()->toReactArray(),
        ]);
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Invoice $invoice)
    {
        AuditLogger::log([
            'action'       => 'DELETE',
            'module'       => 'BILLING',
            'description'  => 'Deleted invoice ' . $invoice->transaction_no . ' for ' . $invoice->tenant_name,
            'status'       => 'warning',
            'subject_type' => Invoice::class,
            'subject_id'   => $invoice->id,
        ]);

        if ($invoice->receipt_path) {
            Storage::disk('public')->delete($invoice->receipt_path);
        }
        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted successfully.',
        ]);
    }

    /**
     * Mark an invoice as paid (manual override).
     */
    public function markPaid(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'payment_method' => ['nullable', Rule::in(['Not Set', 'GCash', 'Over The Counter', 'Bank Transfer'])],
        ]);

        $invoice->update([
            'amount_paid'    => $invoice->total_amount,
            'balance'        => 0,
            'status'         => 'Paid',
            'payment_method' => $validated['payment_method'] ?? $invoice->payment_method,
            'date_paid'      => now()->toDateString(),
            'receipt_no'     => $invoice->receipt_no ?? 'OTC-' . now()->format('Ymd') . '-' . str_pad($invoice->id, 4, '0', STR_PAD_LEFT),
            'verified_by'    => $request->user()->id,
            'verified_at'    => now(),
        ]);

        AuditLogger::log([
            'action'       => 'UPDATE',
            'module'       => 'BILLING',
            'description'  => 'Marked invoice ' . $invoice->transaction_no . ' as paid (' . $invoice->tenant_name . ')',
            'subject_type' => Invoice::class,
            'subject_id'   => $invoice->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invoice marked as paid.',
            'data'    => $invoice->fresh()->toReactArray(),
        ]);
    }

    /**
     * Verify a tenant-submitted payment (invoice.status === 'Pending').
     */
    public function verifyPayment(Request $request, Invoice $invoice)
    {
        if ($invoice->status !== 'Pending') {
            return response()->json([
                'message' => 'Only pending payments can be verified.',
            ], 422);
        }

        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string'],
        ]);

        $balance = max(0, $invoice->total_amount - $invoice->amount_paid);
        $newStatus = ($balance <= 0.005) ? 'Paid' : 'Partial';

        $invoice->update([
            'balance'     => $balance,
            'status'      => $newStatus,
            'date_paid'   => now()->toDateString(),
            'receipt_no'  => $invoice->receipt_no
                ?? 'OTC-' . now()->format('Ymd') . '-' . str_pad($invoice->id, 4, '0', STR_PAD_LEFT),
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        AuditLogger::log([
            'action'       => 'VERIFY',
            'module'       => 'BILLING',
            'description'  => 'Verified ' . $invoice->payment_method . ' payment for '
                             . $invoice->tenant_name . ' invoice ' . $invoice->transaction_no,
            'subject_type' => Invoice::class,
            'subject_id'   => $invoice->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment verified successfully.',
            'data'    => $invoice->fresh()->toReactArray(),
        ]);
    }

    /**
     * Reject a tenant-submitted payment and revert to unpaid/partial.
     */
    public function rejectPayment(Request $request, Invoice $invoice)
    {
        if ($invoice->status !== 'Pending') {
            return response()->json([
                'message' => 'Only pending payments can be rejected.',
            ], 422);
        }

        $validated = $request->validate([
            'admin_notes' => ['required', 'string'],
        ]);

        if ($invoice->receipt_path) {
            Storage::disk('public')->delete($invoice->receipt_path);
        }

        $invoice->update([
            'status'       => $invoice->amount_paid > 0 ? 'Partial' : 'Unpaid',
            'receipt_path' => null,
            'date_paid'    => null,
        ]);

        AuditLogger::log([
            'action'       => 'REJECT',
            'module'       => 'BILLING',
            'description'  => 'Rejected payment for invoice ' . $invoice->transaction_no
                             . ' (' . $invoice->tenant_name . '): ' . $validated['admin_notes'],
            'status'       => 'warning',
            'subject_type' => Invoice::class,
            'subject_id'   => $invoice->id,
        ]);

        return response()->json([
            'success'     => true,
            'message'     => 'Payment rejected.',
            'admin_notes' => $validated['admin_notes'],
        ]);
    }

    /**
     * Send a reminder for an invoice (placeholder).
     */
    public function sendReminder(Request $request, Invoice $invoice)
    {
        AuditLogger::log([
            'action'       => 'SUBMIT',
            'module'       => 'BILLING',
            'description'  => 'Sent payment reminder to ' . $invoice->tenant_name
                             . ' for invoice ' . $invoice->transaction_no,
            'subject_type' => Invoice::class,
            'subject_id'   => $invoice->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reminder sent to ' . $invoice->tenant_name . '.',
        ]);
    }
}