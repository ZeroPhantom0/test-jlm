<?php
// app/Http/Controllers/Admin/BillingController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use App\Mail\PaymentVerifiedMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class BillingController extends Controller
{
    private const ACTIONABLE_STATUSES = ['Pending', 'Partial', 'Overdue'];

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
            ->map(function ($inv) {
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
            });

        $availableStatuses = self::ACTIONABLE_STATUSES;

        return view('admin.billing', compact(
            'invoices',
            'metrics',
            'filters',
            'invoicesForReact',
            'availableStatuses'
        ));
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
                    // try next format
                }
            }
            try {
                return \Carbon\Carbon::parse($value);
            } catch (\Throwable $e) {
                return null;
            }
        };

        // ── Recompute the due date from the (possibly edited) check-in/out ──
        $parsedCheckIn  = $parseDate($validated['check_in'])  ?? $invoice->check_in;
        $parsedCheckOut = $parseDate($validated['check_out']) ?? $invoice->check_out;

        $computedDueDate = $parseDate($validated['due_date'])
            ?? $this->computeDueDate($parsedCheckIn, $parsedCheckOut);

        DB::beginTransaction();
        try {
            $wasPaid = $invoice->status === 'Paid';
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

            // If the admin manually changed the status to Paid (and it wasn't before),
            // we need to deduct the balance from the reservation.
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

            // NEW: Deduct the payment from the reservation's remaining balance
            $this->deductFromReservation($invoice);

            $this->syncLinkedReservation($invoice);

            DB::commit();

            $this->notifyTenant($invoice, 'verified');

            if ($status === 'Paid') {
                $this->sendPaymentVerifiedEmail($invoice);
            }

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

        // Look up the tenant email once so both branches can use it in the audit log
        $tenantUser  = User::find($invoice->tenant_id);
        if (! $tenantUser) {
            $tenantUser = User::find(Tenant::find($invoice->tenant_id)?->user_id);
        }
        $tenantEmail = $tenantUser?->email ?? $invoice->tenant_name;

        DB::beginTransaction();
        try {
            // ── 1. Is this invoice tied to a still-Pending reservation? ──
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

                return response()->json([
                    'message' => 'Payment rejected. Reservation cancelled. Tenant has been notified.',
                    'status'  => 'Rejected',
                    'invoice' => null,
                ]);
            }

            // ── 2. Not reservation-linked ──
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
        return response()->json([
            'message' => "Reminder sent to {$invoice->tenant_name}.",
        ]);
    }

    // ============================================================
    // HELPERS
    // ============================================================

    /**
     * Deducts the verified payment amount from the linked Reservation's remaining balance.
     * This prevents the "Pay Now" loop from happening again.
     */
    private function deductFromReservation(Invoice $invoice): void
    {
        $reservation = $this->reservationForInvoice($invoice);

        if ($reservation) {
            // Use the amount_paid on the invoice. Since we verified it, this is the amount the tenant paid.
            $paidAmount = (float) $invoice->amount_paid;
            
            $newRemaining = max(0, (float) $reservation->remaining - $paidAmount);
            
            $reservation->update(['remaining' => $newRemaining]);
        }
    }

    /**
     * Compute the due date for an invoice based on the stay length.
     *
     *   ≤ 1 day         → check_out (same day)
     *   2 – 6 days      → check_in + 3 days
     *   7 – 31 days     → check_in + 7 days
     *   32 – 100 days   → check_in + 1 month
     *   101 – 364 days  → check_in + 2 months
     *   ≥ 365 days      → check_in + 3 months
     */
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
            } elseif ($type === 'rejected' && class_exists('App\Notifications\\PaymentRejectedNotification')) {
                $tenantUser->notify(new \App\Notifications\PaymentRejectedNotification($invoice, $notes));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function sendPaymentVerifiedEmail(Invoice $invoice): void
    {
        try {
            $user = User::find($invoice->tenant_id);

            if (!$user) {
                $tenant = Tenant::find($invoice->tenant_id);
                if ($tenant) {
                    $user = User::find($tenant->user_id);
                }
            }

            if ($user && $user->email) {
                Mail::to($user->email)->send(new PaymentVerifiedMail($invoice));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}