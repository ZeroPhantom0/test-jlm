<?php
// app/Http/Controllers/Admin/BillingController.php

namespace App\Http\Controllers\Admin;

use App\Support\InputRules;
use App\Events\InvoiceUpdated;
use App\Events\ReservationUpdated;
use App\Http\Controllers\Controller;
use App\Mail\OverduePaymentMail;
use App\Mail\PaymentApprovedMail;
use App\Mail\PaymentDueReminderMail;
use App\Mail\PaymentRejectedMail;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class BillingController extends Controller
{
    private const ACTIONABLE_STATUSES = ['Pending', 'Partial', 'Unpaid'];

    public function index(Request $request)
    {
        $request->validate(['status' => ['nullable', 'string', 'max:30']]);
        $filters = ['status' => $request->query('status')];

        $rejectedRefs = \App\Models\Reservation::whereIn('status', ['Rejected', 'Cancelled'])->pluck('reference_no')->toArray();

        $query = Invoice::query()->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->whereIn('status', static::ACTIONABLE_STATUSES);
        }

        foreach ($rejectedRefs as $ref) {
            $query->where('description', 'NOT LIKE', "%{$ref}%");
        }

        $invoices = $query->get();

        $invoices->transform(function ($inv) {
            $inv->formatted_total   = '₱' . number_format($inv->total_amount, 0);
            $inv->formatted_paid    = '₱' . number_format($inv->amount_paid, 0);
            $inv->formatted_balance = '₱' . number_format($inv->balance, 0);
            $inv->formatted_rate    = '₱' . number_format($inv->rate_per_day, 0) . '/day';
            $inv->formatted_days    = $inv->days . ' day' . ($inv->days == 1 ? '' : 's');
            $inv->status_class      = match ($inv->status) {
                'Paid'    => 'bg-green-100 text-green-600',
                'Pending' => 'bg-blue-100 text-blue-600',
                'Partial' => 'bg-yellow-100 text-yellow-700',
                default   => 'bg-amber-100 text-amber-700',
            };
            return $inv;
        });

        $metricsQuery = Invoice::query();
        foreach ($rejectedRefs as $ref) {
            $metricsQuery->where('description', 'NOT LIKE', "%{$ref}%");
        }

        $metrics = [
            'collected' => (clone $metricsQuery)->where('status', 'Paid')
                ->whereMonth('date_paid', now()->month)
                ->whereYear('date_paid', now()->year)
                ->sum('amount_paid'),
            'pending'   => (clone $metricsQuery)->whereIn('status', ['Pending', 'Partial', 'Unpaid'])
                ->sum('balance'),
        ];

        $invoicesForReact = $invoices
            ->keyBy('transaction_no')
            ->map(fn($inv) => $this->reactPayload($inv));

        $availableStatuses = static::ACTIONABLE_STATUSES;

        return view('admin.billing', compact(
            'invoices',
            'metrics',
            'filters',
            'invoicesForReact',
            'availableStatuses'
        ));
    }

    public function data(Request $request)
    {
        $request->validate(['status' => ['nullable', 'string', 'max:30']]);
        $status = $request->query('status');
        $query = Invoice::query()->latest();

        if (! empty($status)) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', static::ACTIONABLE_STATUSES);
        }

        $rejectedRefs = \App\Models\Reservation::whereIn('status', ['Rejected', 'Cancelled'])->pluck('reference_no')->toArray();
        foreach ($rejectedRefs as $ref) {
            $query->where('description', 'NOT LIKE', "%{$ref}%");
        }

        $invoices = $query->get();

        $metricsQuery = Invoice::query();
        foreach ($rejectedRefs as $ref) {
            $metricsQuery->where('description', 'NOT LIKE', "%{$ref}%");
        }

        $metrics = [
            'collected' => (clone $metricsQuery)->where('status', 'Paid')
                ->whereMonth('date_paid', now()->month)
                ->whereYear('date_paid', now()->year)
                ->sum('amount_paid'),
            'pending'   => (clone $metricsQuery)->whereIn('status', ['Pending', 'Partial', 'Unpaid'])->sum('balance'),
        ];

        $rows = $invoices->map(function (Invoice $inv) {
            return [
                'id'                => $inv->id,
                'transaction_no'    => $inv->transaction_no,
                'description'       => $inv->description,
                'tenant_name'       => $inv->tenant_name,
                'room_number'       => $inv->room_number,
                'formatted_total'   => '₱' . number_format($inv->total_amount, 0),
                'formatted_rate'    => '₱' . number_format($inv->rate_per_day, 0) . '/day',
                'formatted_paid'    => '₱' . number_format($inv->amount_paid, 0),
                'formatted_days'    => $inv->days . ' day' . ($inv->days == 1 ? '' : 's'),
                'formatted_balance' => '₱' . number_format($inv->balance, 0),
                'due_date'          => $inv->due_date?->format('M d, Y') ?? '-',
                'status'            => $inv->status,
                'status_class'      => match ($inv->status) {
                    'Paid'    => 'bg-green-100 text-green-600',
                    'Pending' => 'bg-blue-100 text-blue-600',
                    'Partial' => 'bg-yellow-100 text-yellow-700',
                    default   => 'bg-amber-100 text-amber-700',
                },
            ];
        })->values();

        $reactMap = $invoices
            ->keyBy('transaction_no')
            ->map(fn($inv) => $this->reactPayload($inv))
            ->toArray();

        return response()->json(['rows' => $rows, 'metrics' => $metrics, 'react' => $reactMap]);
    }

    // ============================================================
    // CRUD
    // ============================================================

    /** Statuses that mean money has been verified on the invoice. */
    private const VERIFIED_STATUSES = ['Paid', 'Partial'];

    /** Longest stay a single invoice can cover (10 years, in days). */
    private const MAX_INVOICE_DAYS = 3650;

    /**
     * Create a manual invoice for a tenant (e.g. damages, extra charges).
     * POST /admin/billing
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id'      => ['required', 'integer', 'exists:tenants,id'],
            'description'    => InputRules::text(InputRules::SHORT_TEXT),
            'rate_per_day'   => InputRules::money(true, InputRules::MONEY_MAX),
            'days'           => ['required', 'integer', 'min:1', 'max:' . self::MAX_INVOICE_DAYS],
            'check_in'       => ['nullable', 'date'],
            'check_out'      => ['nullable', 'date', 'after_or_equal:check_in'],
            'due_date'       => ['nullable', 'date', 'after_or_equal:today'],
            'payment_method' => ['nullable', 'in:Not Set,GCash,Over The Counter,Bank Transfer'],
        ]);

        $tenant = Tenant::with(['user', 'room'])->findOrFail($validated['tenant_id']);
        $total  = round($validated['rate_per_day'] * $validated['days'], 2);

        if ($total > InputRules::MONEY_MAX) {
            return response()->json([
                'message' => 'Invoice total cannot exceed ₱' . number_format(InputRules::MONEY_MAX) . '.',
                'errors'  => ['rate_per_day' => ['Rate × days exceeds the maximum invoice total.']],
            ], 422);
        }

        $checkIn  = isset($validated['check_in']) ? Carbon::parse($validated['check_in']) : null;
        $checkOut = isset($validated['check_out']) ? Carbon::parse($validated['check_out']) : null;

        $invoice = Invoice::create([
            'transaction_no' => Invoice::generateTransactionNo(),
            'tenant_id'      => $tenant->id,
            'room_id'        => $tenant->room_id,
            'tenant_name'    => $tenant->user?->name ?? 'Tenant',
            'room_number'    => $tenant->room?->room_number ?? 'N/A',
            'description'    => $validated['description'],
            'check_in'       => $checkIn,
            'check_out'      => $checkOut,
            'rate_per_day'   => $validated['rate_per_day'],
            'days'           => $validated['days'],
            'total_amount'   => $total,
            'amount_paid'    => 0,
            'pending_amount' => 0,
            'balance'        => $total,
            'due_date'       => isset($validated['due_date'])
                ? Carbon::parse($validated['due_date'])
                : $this->computeDueDate($checkIn, $checkOut),
            'payment_method' => $validated['payment_method'] ?? 'Not Set',
            'status'         => 'Unpaid',
        ]);

        AuditLogger::log([
            'action'       => 'CREATE',
            'module'       => 'BILLING',
            'description'  => 'Created invoice ' . $invoice->transaction_no . ' for ' . $invoice->tenant_name
                . ' — ₱' . number_format($total, 2) . ' · ' . $invoice->description,
            'subject_type' => Invoice::class,
            'subject_id'   => $invoice->id,
        ]);

        event(new InvoiceUpdated('created', $invoice->id));

        return response()->json([
            'message' => 'Invoice created.',
            'invoice' => $this->reactPayload($invoice),
        ], 201);
    }

    /**
     * GET /admin/billing/{invoice}
     */
    public function show(Invoice $invoice)
    {
        return response()->json([
            'success' => true,
            'data'    => $invoice->load(['tenant.user', 'room']),
            'react'   => $this->reactPayload($invoice),
        ]);
    }

    /**
     * Correct an invoice's details from the Edit Invoice modal.
     * PUT /admin/billing/{invoice}
     *
     * Status is derived from the amounts (Paid / Partial / Unpaid) rather than
     * trusted from the form. While an invoice is Pending verification the
     * money fields are locked — it must be settled through Verify / Reject.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $dateRule = function (string $attribute, $value, \Closure $fail) {
            if ($value !== null && $value !== '' && $value !== '-' && $this->parseInvoiceDate($value) === null) {
                $fail('The ' . str_replace('_', ' ', $attribute) . ' is not a valid date (e.g. Nov 15, 2026).');
            }
        };

        $validated = $request->validate([
            'tenant_name'    => InputRules::text(120),
            'room_number'    => InputRules::text(InputRules::ROOM_LABEL),
            'description'    => InputRules::text(InputRules::SHORT_TEXT),
            'check_in'       => ['nullable', 'string', 'max:30', $dateRule],
            'check_out'      => ['nullable', 'string', 'max:30', $dateRule],
            'rate_per_day'   => InputRules::money(true, InputRules::MONEY_MAX),
            'days'           => ['required', 'integer', 'min:1', 'max:' . self::MAX_INVOICE_DAYS],
            'amount_paid'    => InputRules::money(true, InputRules::MONEY_MAX),
            'due_date'       => ['nullable', 'string', 'max:30', $dateRule],
            'payment_method' => ['required', 'in:Not Set,GCash,Over The Counter,Bank Transfer'],
            'status'         => ['required', 'in:Unpaid,Overdue,Partial,Pending,Paid'],
        ]);

        $isPending = $invoice->status === 'Pending';
        $total     = round($validated['rate_per_day'] * $validated['days'], 2);

        if ($total > InputRules::MONEY_MAX) {
            return response()->json([
                'message' => 'Invoice total cannot exceed ₱' . number_format(InputRules::MONEY_MAX) . '.',
                'errors'  => ['rate_per_day' => ['Rate × days exceeds the maximum invoice total.']],
            ], 422);
        }

        // Pending: the tenant's submission is matched against the current
        // total, so the money side of the invoice can't change until it's settled.
        if ($isPending && abs($total - (float) $invoice->total_amount) > 0.005) {
            return response()->json([
                'message' => 'This invoice has a payment awaiting verification, so its rate and days can\'t be changed. Verify or reject the payment first.',
                'errors'  => ['rate_per_day' => ['Locked while a payment is pending verification.']],
            ], 422);
        }

        $paid = $isPending ? (float) $invoice->amount_paid : (float) $validated['amount_paid'];

        if ($paid > $total + 0.005) {
            return response()->json([
                'message' => 'Amount paid (₱' . number_format($paid, 2) . ') cannot be more than the invoice total (₱' . number_format($total, 2) . ').',
                'errors'  => ['amount_paid' => ['Amount paid cannot exceed the total.']],
            ], 422);
        }

        if ($isPending) {
            $status  = 'Pending';
            $balance = (float) $invoice->balance;
        } else {
            $status = match (true) {
                $total > 0 && $paid + 0.005 >= $total => 'Paid',
                $paid > 0                             => 'Partial',
                // Keep an Overdue flag on an invoice that's still unpaid
                $invoice->status === 'Overdue' || $validated['status'] === 'Overdue' => 'Overdue',
                default                               => 'Unpaid',
            };
            $balance = $status === 'Paid' ? 0.0 : max(0, round($total - $paid, 2));
        }

        $checkIn  = $this->resolveInvoiceDate($validated['check_in'] ?? null, $invoice->check_in);
        $checkOut = $this->resolveInvoiceDate($validated['check_out'] ?? null, $invoice->check_out);

        if ($checkIn && $checkOut && $checkOut->lt($checkIn->copy()->startOfDay())) {
            return response()->json([
                'message' => 'Check-out cannot be before check-in.',
                'errors'  => ['check_out' => ['Check-out cannot be before check-in.']],
            ], 422);
        }

        $dueDate = $this->parseInvoiceDate($validated['due_date'] ?? null)
            ?? $this->computeDueDate($checkIn, $checkOut);

        $wasVerified  = in_array($invoice->status, self::VERIFIED_STATUSES, true);
        $nowVerified  = in_array($status, self::VERIFIED_STATUSES, true);
        $becamePaid   = $invoice->status !== 'Paid' && $status === 'Paid';
        $statusChange = $invoice->status !== $status;

        DB::beginTransaction();
        try {
            $invoice->update([
                'tenant_name'    => $validated['tenant_name'],
                'room_number'    => $validated['room_number'],
                'description'    => $validated['description'],
                'check_in'       => $checkIn,
                'check_out'      => $checkOut,
                'rate_per_day'   => $validated['rate_per_day'],
                'days'           => $validated['days'],
                'total_amount'   => $total,
                'amount_paid'    => $paid,
                'pending_amount' => $isPending ? $invoice->pending_amount : 0,
                'balance'        => $balance,
                'due_date'       => $dueDate,
                'payment_method' => $validated['payment_method'],
                'status'         => $status,
                'date_paid'      => $status === 'Paid' ? ($invoice->date_paid ?? now()->toDateString()) : null,
                'verified_by'    => $nowVerified ? ($invoice->verified_by ?? Auth::id()) : null,
                'verified_at'    => $nowVerified ? ($invoice->verified_at ?? now()) : null,
            ]);

            $invoice->refresh();

            // Money was confirmed by this edit → run the same follow-ups as Verify.
            $reservation = null;
            if (! $wasVerified && $nowVerified) {
                $reservation = $this->applyConfirmedPayment($invoice);
            } elseif ($wasVerified && $nowVerified) {
                // Amounts corrected on an already-verified invoice: keep the
                // linked reservation's remaining balance in step.
                $this->deductFromReservation($invoice, $this->reservationForInvoice($invoice));
            }

            AuditLogger::log([
                'action'       => 'UPDATE',
                'module'       => 'BILLING',
                'description'  => 'Edited invoice ' . $invoice->transaction_no . ' (' . $invoice->tenant_name . ')'
                    . ' — total ₱' . number_format($total, 2) . ', paid ₱' . number_format($paid, 2)
                    . ($statusChange ? ', status → ' . $status : ''),
                'subject_type' => Invoice::class,
                'subject_id'   => $invoice->id,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['message' => 'Failed to update invoice. Please try again.'], 500);
        }

        $this->afterConfirmedPayment($invoice, $reservation, $becamePaid);
        event(new InvoiceUpdated('updated', $invoice->id));

        $message = 'Invoice updated.';
        if (! $isPending && $validated['status'] !== $status) {
            $message .= " Status set to {$status} based on the amount paid.";
        }

        return response()->json(['message' => $message, 'status' => $status, 'invoice' => $this->reactPayload($invoice)]);
    }

    /**
     * Record a full cash / over-the-counter payment in one click.
     * POST /admin/billing/{invoice}/mark-paid
     */
    public function markPaid(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'payment_method' => ['nullable', 'in:GCash,Over The Counter,Bank Transfer'],
            'receipt_no'     => InputRules::reference(false),
        ]);

        if ($invoice->status === 'Paid') {
            return response()->json(['message' => 'Invoice is already paid.'], 422);
        }
        if ($invoice->status === 'Pending') {
            return response()->json([
                'message' => 'This invoice has a payment awaiting verification. Use Verify or Reject instead.',
            ], 422);
        }

        $wasVerified = in_array($invoice->status, self::VERIFIED_STATUSES, true);
        $collected   = max(0, (float) $invoice->total_amount - (float) $invoice->amount_paid);

        DB::beginTransaction();
        try {
            $invoice->update([
                'status'         => 'Paid',
                'amount_paid'    => $invoice->total_amount,
                'pending_amount' => 0,
                'balance'        => 0,
                'date_paid'      => now()->toDateString(),
                'payment_method' => $validated['payment_method'] ?? ($invoice->payment_method !== 'Not Set' ? $invoice->payment_method : 'Over The Counter'),
                'receipt_no'     => $validated['receipt_no'] ?? $invoice->receipt_no,
                'verified_by'    => Auth::id(),
                'verified_at'    => now(),
            ]);

            $invoice->refresh();

            $reservation = $wasVerified
                ? tap($this->reservationForInvoice($invoice), fn ($r) => $this->deductFromReservation($invoice, $r))
                : $this->applyConfirmedPayment($invoice);

            AuditLogger::log([
                'action'       => 'APPROVE',
                'module'       => 'BILLING',
                'description'  => 'Marked invoice ' . $invoice->transaction_no . ' as Paid (₱' . number_format($collected, 2)
                    . ' collected from ' . $invoice->tenant_name . ')',
                'status'       => 'success',
                'subject_type' => Invoice::class,
                'subject_id'   => $invoice->id,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['message' => 'Failed to mark invoice as paid. Please try again.'], 500);
        }

        $this->afterConfirmedPayment($invoice, $wasVerified ? null : $reservation, true);
        event(new InvoiceUpdated('paid', $invoice->id));

        return response()->json([
            'success' => true,
            'message' => 'Invoice marked as paid.',
            'invoice' => $this->reactPayload($invoice),
        ]);
    }

    /**
     * Archive (soft-delete) an invoice that has no verified money on it.
     * DELETE /admin/billing/{invoice}
     */
    public function destroy(Invoice $invoice)
    {
        if (in_array($invoice->status, self::VERIFIED_STATUSES, true)) {
            return response()->json([
                'message' => 'Cannot delete an invoice with a verified payment (' . $invoice->status . ').',
            ], 422);
        }
        if ($invoice->status === 'Pending') {
            return response()->json([
                'message' => 'This invoice has a payment awaiting verification. Reject it first so the tenant is notified.',
            ], 422);
        }

        // Keep the proof-of-payment file in the private archive, never destroy it
        if ($invoice->receipt_path) {
            BillingService::archiveReceipt($invoice->receipt_path);
        }

        $invoice->delete();

        AuditLogger::log([
            'action'       => 'DELETE',
            'module'       => 'BILLING',
            'description'  => 'Archived invoice ' . $invoice->transaction_no . ' (' . $invoice->tenant_name
                . ', ₱' . number_format((float) $invoice->total_amount, 2) . ')',
            'status'       => 'warning',
            'subject_type' => Invoice::class,
            'subject_id'   => $invoice->id,
        ]);

        event(new InvoiceUpdated('deleted', $invoice->id));

        return response()->json([
            'success' => true,
            'message' => 'Invoice archived.',
        ]);
    }

    /**
     * Side effects of money being confirmed on an invoice — the same work
     * verifyPayment() does: update the linked reservation (and auto-approve
     * it), or approve the linked room change / stay extension.
     * Must run inside the caller's DB transaction.
     */
    private function applyConfirmedPayment(Invoice $invoice): ?Reservation
    {
        $reservation = $this->reservationForInvoice($invoice);

        if ($reservation) {
            $this->deductFromReservation($invoice, $reservation);
            $this->syncLinkedReservation($invoice, $reservation);
        } else {
            $this->syncServiceRequests($invoice);
        }

        return $reservation;
    }

    /** Post-commit follow-ups: balance-reminder policy, tenant notification, email. */
    private function afterConfirmedPayment(Invoice $invoice, ?Reservation $reservation, bool $sendEmail): void
    {
        if ($reservation && $reservation->fresh()?->status === 'Approved') {
            try {
                app(\App\Services\BalanceReminderService::class)->handleApproval($reservation);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($sendEmail && $invoice->exists && $invoice->status === 'Paid') {
            $this->notifyTenant($invoice, 'verified');
            try {
                $email = $this->getTenantEmail($invoice);
                if ($email) {
                    Mail::to($email)->queue(new PaymentApprovedMail($invoice->fresh()));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /** Parse the dates the Edit modal sends ("Nov 15, 2026", "2026-11-15", "11/15/2026"). */
    private function parseInvoiceDate($value): ?Carbon
    {
        if ($value === null) return null;
        $value = trim((string) $value);
        if ($value === '' || $value === '-') return null;

        foreach (['M j, Y', 'M d, Y', 'F j, Y', 'Y-m-d', 'm/d/Y', 'm-d-Y', 'm-d-Y h:i A', 'Y-m-d H:i:s'] as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $value);
                // Reject overflow like "Feb 31" that createFromFormat silently rolls over
                if ($d !== false && strcasecmp($d->format($fmt), $value) === 0) {
                    return str_contains($fmt, 'H') || str_contains($fmt, 'h') ? $d : $d->startOfDay();
                }
            } catch (\Throwable $e) {
                // try next format
            }
        }

        return null;
    }

    /** Keep the stored time-of-day when the admin didn't change the date. */
    private function resolveInvoiceDate($value, ?Carbon $current): ?Carbon
    {
        $parsed = $this->parseInvoiceDate($value);
        if (! $parsed) {
            // Field cleared ("-" or empty) → clear it; otherwise unchanged
            return ($value === null || trim((string) $value) === '' || trim((string) $value) === '-') ? null : $current;
        }

        return ($current && $current->isSameDay($parsed)) ? $current : $parsed;
    }

    public function verifyPayment(Request $request, Invoice $invoice)
    {
        if ($invoice->status !== 'Pending') {
            return response()->json(['message' => 'Invoice is not pending verification.'], 422);
        }

        $submitted = BillingService::submittedAmount($invoice);

        DB::beginTransaction();
        try {
            // A remaining-balance payment becomes part of amount_paid only now
            if ((float) $invoice->pending_amount > 0) {
                $invoice->amount_paid    = (float) $invoice->amount_paid + (float) $invoice->pending_amount;
                $invoice->pending_amount = 0;
            }

            [$status, $balance, $datePaid, $verified] = $this->deriveInvoiceStatus($invoice);
            $reservation = $this->reservationForInvoice($invoice);

            $invoice->update([
                'amount_paid'    => $invoice->amount_paid,
                'pending_amount' => 0,
                'status'      => $status,
                'balance'     => $balance,
                'date_paid'   => $datePaid,
                'verified_by' => $verified ? Auth::id() : null,
                'verified_at' => $verified ? now() : null,
            ]);

            if ($reservation) {
                $this->deductFromReservation($invoice, $reservation);
                $this->syncLinkedReservation($invoice, $reservation);
            } else {
                // Try syncing Room Change or Stay Extension
                $this->syncServiceRequests($invoice);
            }

            DB::commit();

            AuditLogger::log([
                'action'       => 'APPROVE',
                'module'       => 'BILLING',
                'description'  => 'Approved payment of ₱' . number_format($submitted, 2)
                    . ' from ' . $invoice->tenant_name . ' — ' . $invoice->transaction_no
                    . ' (' . $status . ($status === 'Partial' ? ', balance ₱' . number_format((float) $balance, 2) : '') . ')'
                    . ($invoice->description ? ' · ' . $invoice->description : ''),
                'status'       => 'success',
                'subject_type' => Invoice::class,
                'subject_id'   => $invoice->id,
            ]);

            // A reservation approved through Billing must follow the same
            // payment policy (3-hour reminder / cancel at check-in) as one
            // approved from the Reservations page.
            if ($reservation && $reservation->fresh()?->status === 'Approved') {
                try {
                    app(\App\Services\BalanceReminderService::class)->handleApproval($reservation);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $this->notifyTenant($invoice, 'verified');

            if ($status === 'Paid' || $status === 'Partial') {
                $email = $this->getTenantEmail($invoice);
                if ($email) {
                    Mail::to($email)->queue(new PaymentApprovedMail($invoice->fresh()));
                }
            }

            event(new InvoiceUpdated('verified', $invoice->id));

            $message = match ($status) {
                'Paid'    => 'Payment verified successfully. Invoice is now fully Paid.',
                'Partial' => 'Partial payment verified. Invoice status changed to Partial.',
                'Unpaid'  => 'Payment verified — nothing was paid on this invoice.',
                default   => 'Payment verified.',
            };

            return response()->json(['message' => $message, 'status' => $status, 'invoice' => $invoice->fresh()]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['message' => 'Verification failed: ' . $e->getMessage()], 500);
        }
    }

    public function rejectPayment(Request $request, Invoice $invoice)
    {
        $validated = $request->validate(['admin_notes' => InputRules::text(InputRules::NOTES)]);

        if ($invoice->status !== 'Pending') {
            return response()->json(['message' => 'Invoice is not pending verification.'], 422);
        }

        $tenantEmail     = $this->getTenantEmail($invoice) ?? $invoice->tenant_name;
        $submittedAmount = BillingService::submittedAmount($invoice); // before the rejection reverts it

        DB::beginTransaction();
        try {
            $reservation = $this->reservationForInvoice($invoice);

            if ($reservation && $reservation->status === 'Pending') {
                if ($reservation->proof_of_payment) BillingService::archiveProofOfPayment($reservation->proof_of_payment);
                $reservation->update([
                    'status' => 'Rejected',
                    'admin_notes' => $validated['admin_notes'] ?: $reservation->admin_notes,
                    'processed_by' => Auth::id(),
                    'processed_at' => now(),
                    'proof_of_payment' => null,
                ]);
                $reservation->room?->syncStatus();
                $invoices = Invoice::query()->where('description', 'like', '%' . $reservation->reference_no . '%')->whereIn('status', ['Unpaid', 'Partial', 'Pending'])->get();
                foreach ($invoices as $inv) {
                    if ($inv->receipt_path) BillingService::archiveReceipt($inv->receipt_path);
                    $inv->delete();
                }
            } else {
                // Handle Room Change / Stay Extension Rejection
                $this->syncServiceRequestsReject($invoice, $validated['admin_notes']);

                if ($invoice->receipt_path) BillingService::archiveReceipt($invoice->receipt_path);
                $invoice->update(BillingService::revertSubmission($invoice) + [
                    'receipt_path' => null,
                    'receipt_no' => null,
                    'verified_by' => null,
                    'verified_at' => null,
                ]);
            }

            AuditLogger::log([
                'action'       => 'REJECT',
                'module' => 'BILLING',
                'description'  => 'Rejected payment of ₱' . number_format($submittedAmount, 2)
                    . ' from ' . ($invoice->tenant_name ?: $tenantEmail) . ' — ' . $invoice->transaction_no
                    . ($invoice->description ? ' · ' . $invoice->description : '')
                    . ' — Reason: ' . $validated['admin_notes'],
                'status'       => 'warning',
                'subject_type' => Invoice::class,
                'subject_id' => $invoice->id,
            ]);

            DB::commit();
            $this->notifyTenant($invoice, 'rejected', $validated['admin_notes']);

            $email = $this->getTenantEmail($invoice);
            if ($email) Mail::to($email)->queue(new PaymentRejectedMail($invoice->fresh(), $validated['admin_notes']));

            event(new InvoiceUpdated('rejected', $invoice->id));

            return response()->json(['message' => 'Payment rejected. Request cancelled. Tenant has been notified.', 'status' => 'Rejected', 'invoice' => null]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['message' => 'Rejection failed: ' . $e->getMessage()], 500);
        }
    }

    public function sendReminder(Invoice $invoice)
    {
        if ($invoice->status === 'Paid') {
            return response()->json([
                'message' => 'Cannot send reminder for a paid invoice.',
            ], 422);
        }

        try {
            $this->sendPaymentDueReminderEmail($invoice);

            AuditLogger::log([
                'action'       => 'REMIND',
                'module'       => 'BILLING',
                'description'  => 'Sent payment reminder to ' . $invoice->tenant_name
                    . ' for invoice ' . $invoice->transaction_no,
                'status'       => 'success',
                'subject_type' => Invoice::class,
                'subject_id'   => $invoice->id,
            ]);

            return response()->json([
                'message' => "Reminder sent to {$invoice->tenant_name}.",
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Failed to send reminder: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ============================================================
    // HELPERS
    // ============================================================

    private function reactPayload(Invoice $inv): array
    {
        return [
            'tn'             => $inv->transaction_no,
            'description'    => $inv->description,
            'tenant'         => $inv->tenant_name,
            'room'           => $inv->room_number,
            'total'          => '₱' . number_format($inv->total_amount, 0),
            'rate'           => '₱ ' . number_format($inv->rate_per_day, 0) . '/day',
            'paid'           => '₱' . number_format($inv->amount_paid, 0),
            // Amount the tenant submitted and that is awaiting verification
            'submitted'      => $inv->status === 'Pending'
                ? '₱' . number_format(BillingService::submittedAmount($inv), 0)
                : null,
            'paid_after'     => '₱' . number_format((float) $inv->amount_paid + (float) $inv->pending_amount, 0),
            'days'           => $inv->days . ' days',
            'balance'        => '₱' . number_format($inv->balance, 0),
            'due'            => $inv->due_date?->format('M j, Y') ?? '-',
            'status'         => $inv->status,
            'check_in'       => $inv->check_in?->format('M j, Y') ?? '-',
            'check_out'      => $inv->check_out?->format('M j, Y') ?? '-',
            'payment_method' => $inv->payment_method,
            'receipt_url'    => $inv->receipt_path
                ? Storage::url($inv->receipt_path)
                : null,
            'date_paid'      => $inv->date_paid?->format('M j, Y'),
            'receipt_no'     => $inv->receipt_no,
            'tenant_note'    => null,
        ];
    }

    private function deductFromReservation(Invoice $invoice, ?Reservation $reservation): void
    {
        // The reservation's remaining = what's still owed on its invoice.
        // (Subtracting the cumulative amount_paid deducted payments twice.)
        if ($reservation) {
            $reservation->update(['remaining' => max(0, (float) $invoice->fresh()->balance)]);
        }
    }

    private function computeDueDate(?Carbon $checkIn, ?Carbon $checkOut): Carbon
    {
        if (! $checkIn) {
            return now()->addDays(7);
        }

        $out  = $checkOut ?: $checkIn->copy()->addDay();
        $days = max(1, $checkIn->diffInDays($out));

        if ($days <= 1) {
            return $out->copy();
        }
        if ($days <= 6) {
            return $checkIn->copy()->addDays(3);
        }
        if ($days <= 31) {
            return $checkIn->copy()->addDays(7);
        }
        if ($days <= 100) {
            return $checkIn->copy()->addMonth();
        }
        if ($days <= 364) {
            return $checkIn->copy()->addMonths(2);
        }
        return $checkIn->copy()->addMonths(3);
    }

    private function deriveInvoiceStatus(Invoice $invoice): array
    {
        $total = (float) $invoice->total_amount;
        $paid  = (float) $invoice->amount_paid;

        if ($paid <= 0) {
            return ['Unpaid', $total, null, false];
        }

        if ($paid + 0.005 >= $total) {
            return ['Paid', 0.0, now()->toDateString(), true];
        }

        return ['Partial', max(0, $total - $paid), null, true];
    }

    private function syncLinkedReservation(Invoice $invoice, ?Reservation $reservation): void
    {
        $reservation = $this->reservationForInvoice($invoice);
        if (! $reservation) return;

        if ($reservation->status === 'Rejected') {
            if ($invoice->receipt_path) BillingService::archiveReceipt($invoice->receipt_path);
            $invoice->forceDelete();
            return;
        }

        if (in_array($reservation->room?->room_type, ['Bedspace', 'Barkada Room'], true) && $reservation->room_bed_id) {
            $in  = $reservation->check_in;
            $out = $reservation->check_out;
            $conflict = Reservation::where('room_id', $reservation->room_id)
                ->where('room_bed_id', $reservation->room_bed_id)->where('id', '!=', $reservation->id)
                ->where('status', 'Approved')->when($in && $out, function ($q) use ($in, $out) {
                    $q->whereDate('check_in', '<', $out->toDateString())
                        ->where(function ($q2) use ($in) {
                            $q2->whereDate('check_out', '>', $in->toDateString())->orWhereNull('check_out');
                        });
                })->exists();
            if ($conflict) return;
        }

        $reservation->update(['status' => 'Approved', 'processed_by' => Auth::id(), 'processed_at' => now()]);
        if ($reservation->user_id) {
            Tenant::updateOrCreate(['user_id' => $reservation->user_id], [
                'room_id' => $reservation->room_id, 'approved_reservation_id' => $reservation->id,
                'rent_rate' => $reservation->total_charges, 'status' => 'active',
            ]);
        }
        $reservation->room?->syncStatus();
        AuditLogger::log(['action' => 'APPROVE', 'module' => 'RESERVATIONS', 'description' => 'Reservation auto-approved', 'subject_type' => Reservation::class, 'subject_id' => $reservation->id]);
    }

    /**
     * Sync Room Change and Stay Extension Requests upon payment verification.
     *
     * IMPORTANT: When a Room Change is approved via payment verification, we must:
     *   1. Expire old approved reservations on the OLD room
     *   2. Move the tenant to the new room
     *   3. Create a NEW approved reservation for the new room
     *   4. Repoint tenant->approved_reservation_id to that new reservation
     *   5. Resync both rooms so status badges reflect reality
     */
    private function syncServiceRequests(Invoice $invoice): void
    {
        $description = (string) $invoice->description;

        // ── 1. ROOM CHANGE ──
        if (preg_match('/#RC-\d+/', $description, $m)) {
            $roomChange = \App\Models\RoomChangeRequest::where('reference_no', $m[0])->first();

            if ($roomChange && $roomChange->status === 'Pending') {
                $tenant  = $roomChange->tenant;
                $newRoom = \App\Models\Room::find($roomChange->requested_room_id);

                if ($newRoom && $tenant) {
                    $oldRoom = $tenant->room;

                    // 1. Expire old approved reservations on the previous room
                    \App\Models\Reservation::where('user_id', $tenant->user_id)
                        ->where('status', 'Approved')
                        ->where('room_id', '!=', $newRoom->id)
                        ->update([
                            'status'       => 'Expired',
                            'processed_by' => Auth::id(),
                            'processed_at' => now(),
                        ]);

                    // 2. Move tenant
                    $tenant->update(['room_id' => $newRoom->id]);

                    // 3. Create a new approved reservation on the new room
                    $oldReservation = \App\Models\Reservation::where('user_id', $tenant->user_id)
                        ->where('status', 'Expired')
                        ->latest()
                        ->first();

                    if ($oldReservation) {
                        $newReservation = \App\Models\Reservation::create([
                            'reference_no'       => \App\Models\Reservation::generateReferenceNo(),
                            'reservation_number' => \App\Models\Reservation::generateReservationNumber(),
                            'user_id'            => $tenant->user_id,
                            'room_id'            => $newRoom->id,
                            'tenant_name'        => $oldReservation->tenant_name,
                            'tenant_initial'     => $oldReservation->tenant_initial,
                            'avatar_bg'          => $oldReservation->avatar_bg,
                            'avatar_color'       => $oldReservation->avatar_color,
                            'email'              => $oldReservation->email,
                            'phone'              => $oldReservation->phone,
                            'desired_room'       => $newRoom->room_number,
                            'room_bed_id'        => $oldReservation->room_bed_id,
                            'check_in'           => $oldReservation->check_in,
                            'check_in_time'      => $oldReservation->check_in_time,
                            'check_out'          => $oldReservation->check_out,
                            'check_out_time'     => $oldReservation->check_out_time,
                            'has_companion'      => $oldReservation->has_companion,
                            'total_charges'      => $oldReservation->total_charges,
                            'reservation_fee'    => $oldReservation->reservation_fee,
                            'payment_option'     => $oldReservation->payment_option,
                            'payment_method'     => $oldReservation->payment_method,
                            'remaining'          => $oldReservation->remaining,
                            'request_details'    => 'Room change via payment verification',
                            'status'             => 'Approved',
                            'processed_by'       => Auth::id(),
                            'processed_at'       => now(),
                        ]);

                        // 4. Repoint tenant to the new reservation
                        $tenant->update(['approved_reservation_id' => $newReservation->id]);
                    }

                    // 5. Resync both rooms
                    if ($oldRoom) $oldRoom->syncStatus();
                    $newRoom->syncStatus();

                    $roomChange->update([
                        'status'       => 'Approved',
                        'processed_by' => Auth::id(),
                        'processed_at' => now(),
                    ]);

                    event(new \App\Events\ReservationUpdated('updated', null));
                }
            }
        }

        // ── 2. STAY EXTENSION ──
        if (preg_match('/#SE-\d+/', $description, $m)) {
            $stayExtension = \App\Models\StayExtensionRequest::where('reference_no', $m[0])->first();

            if ($stayExtension && $stayExtension->status === 'Pending') {
                $tenant      = $stayExtension->tenant;
                $reservation = $tenant?->approvedReservation;

                if ($reservation) {
                    $reservation->update([
                        'check_out' => $stayExtension->requested_lease_end,
                    ]);
                }

                $stayExtension->update([
                    'status'       => 'Approved',
                    'processed_by' => Auth::id(),
                    'processed_at' => now(),
                ]);

                event(new \App\Events\ReservationUpdated('updated', $reservation?->id));
            }
        }
    }

    /**
     * Reject Room Change or Stay Extension Requests upon payment rejection.
     */
    private function syncServiceRequestsReject(Invoice $invoice, string $reason): void
    {
        $description = (string) $invoice->description;

        // 1. Room Change
        if (preg_match('/#RC-\d+/', $description, $m)) {
            $roomChange = \App\Models\RoomChangeRequest::where('reference_no', $m[0])->first();
            if ($roomChange && $roomChange->status === 'Pending') {
                $roomChange->update([
                    'status'       => 'Rejected',
                    'admin_notes'  => $reason,
                    'processed_by' => Auth::id(),
                    'processed_at' => now(),
                ]);
                event(new \App\Events\ServiceRequestUpdated('room_change', 'rejected', $roomChange->id));
            }
        }

        // 2. Stay Extension
        if (preg_match('/#SE-\d+/', $description, $m)) {
            $stayExtension = \App\Models\StayExtensionRequest::where('reference_no', $m[0])->first();
            if ($stayExtension && $stayExtension->status === 'Pending') {
                $stayExtension->update([
                    'status'       => 'Rejected',
                    'admin_notes'  => $reason,
                    'processed_by' => Auth::id(),
                    'processed_at' => now(),
                ]);
                event(new \App\Events\ServiceRequestUpdated('stay_extension', 'rejected', $stayExtension->id));
            }
        }
    }

    private function reservationForInvoice(Invoice $invoice): ?Reservation
    {
        if (! preg_match('/#RSV-\d+/', (string) $invoice->description, $m)) {
            return null;
        }

        return Reservation::where('reference_no', $m[0])->first();
    }

    private function notifyTenant(Invoice $invoice, string $type, string $notes = ''): void
    {
        try {
            $tenant = Tenant::with('user')->find($invoice->tenant_id);
            $tenantUser = $tenant?->user;

            if (! $tenantUser) {
                return;
            }

            if ($type === 'verified' && class_exists('App\\Notifications\\PaymentVerifiedNotification')) {
                $tenantUser->notify(new \App\Notifications\PaymentVerifiedNotification($invoice));
            } elseif ($type === 'rejected' && class_exists('App\\Notifications\\PaymentRejectedNotification')) {
                $tenantUser->notify(new \App\Notifications\PaymentRejectedNotification($invoice, $notes));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function getTenantEmail(Invoice $invoice): ?string
    {
        if (! $invoice->tenant_id) return null;

        return Tenant::join('users', 'tenants.user_id', '=', 'users.id')
            ->where('tenants.id', $invoice->tenant_id)
            ->value('users.email');
    }

    private function sendPaymentApprovedEmail(Invoice $invoice, bool $isPartial = false): void
    {
        try {
            $email = $this->getTenantEmail($invoice);
            if ($email) {
                Mail::to($email)->send(new PaymentApprovedMail($invoice));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function sendPaymentRejectedEmail(Invoice $invoice, string $reason = ''): void
    {
        try {
            $email = $this->getTenantEmail($invoice);
            if ($email) {
                Mail::to($email)->send(new PaymentRejectedMail($invoice, $reason));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function sendPaymentDueReminderEmail(Invoice $invoice): void
    {
        try {
            $email = $this->getTenantEmail($invoice);
            if ($email) {
                $daysUntilDue = $invoice->due_date
                    ? max(0, now()->diffInDays($invoice->due_date, false))
                    : 7;

                Mail::to($email)->send(new PaymentDueReminderMail($invoice, (int) $daysUntilDue));
            }
        } catch (\Throwable $e) {
            report($e);
            throw $e;
        }
    }

    public function sendOverdueNotice(Invoice $invoice): void
    {
        try {
            $email = $this->getTenantEmail($invoice);
            if ($email) {
                $daysOverdue = $invoice->due_date
                    ? (int) now()->diffInDays($invoice->due_date)
                    : 0;

                Mail::to($email)->send(new OverduePaymentMail($invoice, $daysOverdue));

                // Marks the invoice so the automated nightly cron doesn't email them again today.
                $invoice->update(['was_overdue_notified_at' => now()]);
            }
        } catch (\Throwable $e) {
            report($e);
            throw $e;
        }
    }
}