<?php
// app/Http/Controllers/Admin/BillingController.php

namespace App\Http\Controllers\Admin;

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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class BillingController extends Controller
{
    private const ACTIONABLE_STATUSES = ['Pending', 'Partial', 'Overdue', 'Unpaid'];

    public function index(Request $request)
    {
        $filters = [
            'status' => $request->query('status'),
        ];

        $query = Invoice::query()->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->whereIn('status', self::ACTIONABLE_STATUSES);
        }

        $invoices = $query->paginate(50);

        $invoices->getCollection()->transform(function ($inv) {
            $inv->formatted_total   = '₱' . number_format($inv->total_amount, 0);
            $inv->formatted_paid    = '₱' . number_format($inv->amount_paid, 0);
            $inv->formatted_balance = '₱' . number_format($inv->balance, 0);
            $inv->formatted_rate    = '₱' . number_format($inv->rate_per_day, 0) . '/day';
            $inv->formatted_days    = $inv->days . ' day' . ($inv->days == 1 ? '' : 's');
            $inv->status_class      = match ($inv->status) {
                'Paid'    => 'bg-green-100 text-green-600',
                'Pending' => 'bg-blue-100 text-blue-600',
                'Partial' => 'bg-yellow-100 text-yellow-700',
                'Overdue' => 'bg-rose-100 text-rose-600',
                default   => 'bg-amber-100 text-amber-700',
            };
            return $inv;
        });

        $metrics = [
            'collected' => Invoice::where('status', 'Paid')
                ->whereMonth('date_paid', now()->month)
                ->whereYear('date_paid', now()->year)
                ->sum('amount_paid'),
            'pending'   => Invoice::whereIn('status', ['Pending', 'Partial'])
                ->sum('balance'),
            'overdue'   => Invoice::where('status', 'Overdue')
                ->sum('balance'),
        ];

        $invoicesForReact = $invoices->getCollection()
            ->keyBy('transaction_no')
            ->map(fn($inv) => $this->reactPayload($inv));

        $availableStatuses = self::ACTIONABLE_STATUSES;

        return view('admin.billing', compact(
            'invoices',
            'metrics',
            'filters',
            'invoicesForReact',
            'availableStatuses'
        ));
    }

    /**
     * Live data endpoint for polling / Echo refresh.
     * GET /admin/billing/data
     */
    public function data(Request $request)
    {
        $status = $request->query('status');

        $query = Invoice::query()->latest();

        if (! empty($status)) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', self::ACTIONABLE_STATUSES);
        }

        $invoices = $query->paginate(50);

        $metrics = [
            'collected' => Invoice::where('status', 'Paid')
                ->whereMonth('date_paid', now()->month)
                ->whereYear('date_paid', now()->year)
                ->sum('amount_paid'),
            'pending'   => Invoice::whereIn('status', ['Pending', 'Partial'])->sum('balance'),
            'overdue'   => Invoice::where('status', 'Overdue')->sum('balance'),
        ];

        $rows = $invoices->getCollection()->map(function (Invoice $inv) {
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
                    'Overdue' => 'bg-rose-100 text-rose-600',
                    default   => 'bg-amber-100 text-amber-700',
                },
            ];
        })->values();

        $reactMap = $invoices->getCollection()
            ->keyBy('transaction_no')
            ->map(fn($inv) => $this->reactPayload($inv))
            ->toArray();

        return response()->json([
            'rows'    => $rows,
            'metrics' => $metrics,
            'react'   => $reactMap,
        ]);
    }

    public function store(Request $request)
    {
        return response()->json(['message' => 'Not implemented yet.'], 501);
    }

    public function show(Invoice $invoice)
    {
        return response()->json([
            'success' => true,
            'data'    => $invoice->load(['tenant', 'room']),
            'react'   => $this->reactPayload($invoice),
        ]);
    }

    public function destroy(Invoice $invoice)
    {
        // Optional: guard on paid invoices
        if ($invoice->status === 'Paid') {
            return response()->json(['message' => 'Cannot delete a paid invoice.'], 422);
        }

        $invoice->delete();

        event(new \App\Events\InvoiceUpdated('deleted', $invoice->id));

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted.',
        ]);
    }

    public function markPaid(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'Paid') {
            return response()->json(['message' => 'Invoice is already paid.'], 422);
        }

        $invoice->update([
            'status'      => 'Paid',
            'balance'     => 0,
            'amount_paid' => $invoice->total_amount,
            'date_paid'   => now()->toDateString(),
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        event(new \App\Events\InvoiceUpdated('paid', $invoice->id));

        return response()->json([
            'success' => true,
            'message' => 'Invoice marked as paid.',
            'invoice' => $invoice->fresh(),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'tenant_name'    => ['required', 'string', 'max:120'],
            'room_number'    => ['required', 'string', 'max:50'],
            'description'    => ['required', 'string', 'max:255'],
            'check_in'       => ['nullable', 'string'],
            'check_out'      => ['nullable', 'string'],
            'rate_per_day'   => ['required', 'numeric', 'min:0'],
            'days'           => ['required', 'integer', 'min:1'],
            'amount_paid'    => ['required', 'numeric', 'min:0'],
            'due_date'       => ['nullable', 'string'],
            'payment_method' => ['required', 'in:Not Set,GCash,Over The Counter,Bank Transfer'],
            'status'         => ['required', 'in:Unpaid,Overdue,Partial,Pending,Paid'],
        ]);

        $total   = round($validated['rate_per_day'] * $validated['days'], 2);
        $paid    = (float) $validated['amount_paid'];
        $balance = max(0, round($total - $paid, 2));

        $status = $validated['status'];

        if ($status === 'Paid' && $paid + 0.005 < $total) {
            $status = $paid > 0 ? 'Partial' : 'Unpaid';
        } elseif ($status !== 'Paid' && $total > 0 && $paid + 0.005 >= $total) {
            $status = 'Paid';
        }

        $parseDate = function ($value) {
            if (! $value || $value === '-') return null;
            foreach (['M j, Y', 'Y-m-d', 'Y-m-d H:i:s'] as $fmt) {
                try {
                    $d = \Carbon\Carbon::createFromFormat($fmt, trim($value));
                    if ($d !== false) return $d;
                } catch (\Throwable $e) {
                }
            }
            try {
                return \Carbon\Carbon::parse($value);
            } catch (\Throwable $e) {
                return null;
            }
        };

        $parsedCheckIn  = $parseDate($validated['check_in'])  ?? $invoice->check_in;
        $parsedCheckOut = $parseDate($validated['check_out']) ?? $invoice->check_out;

        $computedDueDate = $parseDate($validated['due_date'])
            ?? $this->computeDueDate($parsedCheckIn, $parsedCheckOut);

        DB::beginTransaction();
        try {
            $wasPaid    = $invoice->status === 'Paid';
            $wasPending = $invoice->status === 'Pending';

            $invoice->update([
                'tenant_name'    => $validated['tenant_name'],
                'room_number'    => $validated['room_number'],
                'description'    => $validated['description'],
                'check_in'       => $parsedCheckIn,
                'check_out'      => $parsedCheckOut,
                'rate_per_day'   => $validated['rate_per_day'],
                'days'           => $validated['days'],
                'total_amount'   => $total,
                'amount_paid'    => $paid,
                'balance'        => $status === 'Paid' ? 0.0 : $balance,
                'due_date'       => $computedDueDate,
                'payment_method' => $validated['payment_method'],
                'status'         => $status,

                'date_paid'   => $status === 'Paid'
                    ? ($invoice->date_paid ?? now()->toDateString())
                    : null,
                'verified_by' => $status === 'Paid'
                    ? ($invoice->verified_by ?? Auth::id())
                    : null,
                'verified_at' => $status === 'Paid'
                    ? ($invoice->verified_at ?? now())
                    : null,
            ]);

            if (!$wasPaid && $status === 'Paid') {
                $this->deductFromReservation($invoice->fresh());
            }

            $this->syncLinkedReservation($invoice->fresh());

            AuditLogger::log([
                'action'       => 'UPDATE',
                'module'       => 'BILLING',
                'description'  => 'Manually edited invoice ' . $invoice->transaction_no
                    . ' (' . $invoice->tenant_name . ')'
                    . ($wasPaid !== ($status === 'Paid') ? ' — status ' . $status : ''),
                'subject_type' => Invoice::class,
                'subject_id'   => $invoice->id,
            ]);

            DB::commit();

            if (!$wasPaid && $status === 'Paid') {
                $this->sendPaymentApprovedEmail($invoice->fresh());
            }

            event(new InvoiceUpdated('updated', $invoice->id));

            return response()->json([
                'message' => 'Invoice updated.',
                'invoice' => $invoice->fresh(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'message' => 'Failed to update invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function verifyPayment(Request $request, Invoice $invoice)
    {
        if ($invoice->status !== 'Pending') {
            return response()->json([
                'message' => 'Invoice is not pending verification.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            [$status, $balance, $datePaid, $verified] = $this->deriveInvoiceStatus($invoice);

            $invoice->update([
                'status'      => $status,
                'balance'     => $balance,
                'date_paid'   => $datePaid,
                'verified_by' => $verified ? Auth::id() : null,
                'verified_at' => $verified ? now() : null,
            ]);

            $this->deductFromReservation($invoice);
            $this->syncLinkedReservation($invoice);

            DB::commit();

            $this->notifyTenant($invoice, 'verified');

            if ($status === 'Paid') {
                $this->sendPaymentApprovedEmail($invoice->fresh());
            } elseif ($status === 'Partial') {
                $this->sendPaymentApprovedEmail($invoice->fresh(), true);
            }

            // Broadcast billing change + possible reservation auto-approval
            event(new InvoiceUpdated('verified', $invoice->id));
            event(new ReservationUpdated('updated', null));

            $message = match ($status) {
                'Paid'    => 'Payment verified successfully. Invoice is now fully Paid.',
                'Partial' => 'Partial payment verified. Invoice status changed to Partial.',
                'Unpaid'  => 'Payment verified — nothing was paid on this invoice.',
                default   => 'Payment verified.',
            };

            return response()->json([
                'message' => $message,
                'status'  => $status,
                'invoice' => $invoice->fresh(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'message' => 'Verification failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function rejectPayment(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'max:1000'],
        ]);

        if ($invoice->status !== 'Pending') {
            return response()->json([
                'message' => 'Invoice is not pending verification.',
            ], 422);
        }

        $tenantUser  = User::find($invoice->tenant_id);
        if (! $tenantUser) {
            $tenantUser = User::find(Tenant::find($invoice->tenant_id)?->user_id);
        }
        $tenantEmail = $tenantUser?->email ?? $invoice->tenant_name;

        DB::beginTransaction();
        try {
            $reservation = $this->reservationForInvoice($invoice);

            if ($reservation && $reservation->status === 'Pending') {
                if (
                    $reservation->proof_of_payment
                    && Storage::disk('public')->exists($reservation->proof_of_payment)
                ) {
                    Storage::disk('public')->delete($reservation->proof_of_payment);
                }

                $reservation->update([
                    'status'           => 'Rejected',
                    'admin_notes'      => $validated['admin_notes'] ?: $reservation->admin_notes,
                    'processed_by'     => Auth::id(),
                    'processed_at'     => now(),
                    'proof_of_payment' => null,
                ]);

                $reservation->room?->syncStatus();

                $invoices = Invoice::query()
                    ->where('description', 'like', '%' . $reservation->reference_no . '%')
                    ->whereIn('status', ['Unpaid', 'Partial', 'Pending'])
                    ->get();

                foreach ($invoices as $inv) {
                    if ($inv->receipt_path && Storage::disk('public')->exists($inv->receipt_path)) {
                        Storage::disk('public')->delete($inv->receipt_path);
                    }
                    $inv->delete();
                }

                AuditLogger::log([
                    'action'       => 'REJECT',
                    'module'       => 'BILLING',
                    'description'  => 'Payment rejected for ' . $tenantEmail
                        . ' — ' . $invoice->transaction_no
                        . ' (₱' . number_format((float) $invoice->balance, 2) . ')'
                        . ' — ' . $validated['admin_notes'],
                    'status'       => 'warning',
                    'subject_type' => Invoice::class,
                    'subject_id'   => $invoice->id,
                ]);

                DB::commit();

                $this->notifyTenant($invoice, 'rejected', $validated['admin_notes']);
                $this->sendPaymentRejectedEmail($invoice, $validated['admin_notes']);

                event(new InvoiceUpdated('rejected', $invoice->id));
                event(new ReservationUpdated('rejected', $reservation->id));

                return response()->json([
                    'message' => 'Payment rejected. Reservation cancelled. Tenant has been notified.',
                    'status'  => 'Rejected',
                    'invoice' => null,
                ]);
            }

            if ($invoice->receipt_path && Storage::disk('public')->exists($invoice->receipt_path)) {
                Storage::disk('public')->delete($invoice->receipt_path);
            }

            $revertedAmount = max(0, (float) $invoice->amount_paid - (float) $invoice->balance);
            $revertStatus   = $revertedAmount > 0 ? 'Partial' : 'Unpaid';

            $invoice->update([
                'status'       => $revertStatus,
                'receipt_path' => null,
                'receipt_no'   => null,
                'verified_by'  => null,
                'verified_at'  => null,
                'amount_paid'  => $revertedAmount,
            ]);

            AuditLogger::log([
                'action'       => 'REJECT',
                'module'       => 'BILLING',
                'description'  => 'Payment rejected for ' . $tenantEmail
                    . ' — ' . $invoice->transaction_no
                    . ' (₱' . number_format((float) $invoice->balance, 2) . ')'
                    . ' — ' . $validated['admin_notes'],
                'status'       => 'warning',
                'subject_type' => Invoice::class,
                'subject_id'   => $invoice->id,
            ]);

            DB::commit();

            $this->notifyTenant($invoice, 'rejected', $validated['admin_notes']);
            $this->sendPaymentRejectedEmail($invoice->fresh(), $validated['admin_notes']);

            event(new InvoiceUpdated('rejected', $invoice->id));

            return response()->json([
                'message' => 'Payment rejected. Tenant has been notified.',
                'status'  => $revertStatus,
                'invoice' => $invoice->fresh(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json([
                'message' => 'Rejection failed: ' . $e->getMessage(),
            ], 500);
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

    private function deductFromReservation(Invoice $invoice): void
    {
        $reservation = $this->reservationForInvoice($invoice);

        if ($reservation) {
            $paidAmount   = (float) $invoice->amount_paid;
            $newRemaining = max(0, (float) $reservation->remaining - $paidAmount);

            $reservation->update(['remaining' => $newRemaining]);
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
            return $checkIn->copy()->addMonths(1);
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

    private function syncLinkedReservation(Invoice $invoice): void
    {
        $reservation = $this->reservationForInvoice($invoice);

        if (! $reservation) {
            return;
        }

        if ($reservation->status !== 'Pending') {
            $reservation->room?->syncStatus();
            return;
        }

        if (
            in_array($reservation->room?->room_type, ['Bedspace', 'Barkada Room'], true)
            && $reservation->room_bed_id
        ) {
            $in  = $reservation->check_in;
            $out = $reservation->check_out;

            $conflict = Reservation::where('room_id', $reservation->room_id)
                ->where('room_bed_id', $reservation->room_bed_id)
                ->where('id', '!=', $reservation->id)
                ->where('status', 'Approved')
                ->when($in && $out, function ($q) use ($in, $out) {
                    $q->whereDate('check_in', '<', $out->toDateString())
                        ->where(function ($q2) use ($in) {
                            $q2->whereDate('check_out', '>', $in->toDateString())
                                ->orWhereNull('check_out');
                        });
                })
                ->exists();

            if ($conflict) {
                return;
            }
        }

        $reservation->update([
            'status'       => 'Approved',
            'processed_by' => Auth::id(),
            'processed_at' => now(),
        ]);

        if ($reservation->user_id) {
            Tenant::updateOrCreate(
                ['user_id' => $reservation->user_id],
                [
                    'room_id'                 => $reservation->room_id,
                    'approved_reservation_id' => $reservation->id,
                    'rent_rate'               => $reservation->total_charges,
                    'status'                  => 'active',
                ]
            );
        }

        $reservation->room?->syncStatus();

        AuditLogger::log([
            'action'       => 'APPROVE',
            'module'       => 'RESERVATIONS',
            'description'  => 'Reservation ' . $reservation->reference_no
                . ' auto-approved via payment verification',
            'subject_type' => Reservation::class,
            'subject_id'   => $reservation->id,
        ]);
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
            $tenantUser = User::where('id', $invoice->tenant_id)->first();
            if (! $tenantUser) {
                $tenantUser = User::find(Tenant::find($invoice->tenant_id)?->user_id);
            }
            if (! $tenantUser) return;

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
        $user = User::find($invoice->tenant_id);
        if (!$user) {
            $tenant = Tenant::find($invoice->tenant_id);
            if ($tenant) {
                $user = User::find($tenant->user_id);
            }
        }
        return $user?->email;
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
                    ? now()->diffInDays($invoice->due_date)
                    : 0;

                Mail::to($email)->send(new OverduePaymentMail($invoice, (int) $daysOverdue));
            }
        } catch (\Throwable $e) {
            report($e);
            throw $e;
        }
    }
}
