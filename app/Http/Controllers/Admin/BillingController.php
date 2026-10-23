<?php
// app/Http/Controllers/Admin/BillingController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BillingController extends Controller
{
    /**
     * Statuses that are "passive bookkeeping" — hidden by default in the
     * admin table because there's no action for the admin to take on them.
     */
    private const HIDDEN_STATUSES = ['Paid', 'Unpaid'];

    /**
     * Statuses that require admin action / attention.
     */
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

        // Decorate for Blade
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
        $balance = max(0, $total - $validated['amount_paid']);

        $parseDate = function ($value, $format = null) {
            if (! $value || $value === '-') return null;
            try {
                return $format
                    ? \Carbon\Carbon::createFromFormat($format, $value)
                    : \Carbon\Carbon::parse($value);
            } catch (\Throwable $e) {
                return null;
            }
        };

        $invoice->update([
            'tenant_name'    => $validated['tenant_name'],
            'room_number'    => $validated['room_number'],
            'description'    => $validated['description'],
            'check_in'       => $parseDate($validated['check_in'], 'M j, Y') ?? $invoice->check_in,
            'check_out'      => $parseDate($validated['check_out'], 'M j, Y') ?? $invoice->check_out,
            'rate_per_day'   => $validated['rate_per_day'],
            'days'           => $validated['days'],
            'total_amount'   => $total,
            'amount_paid'    => $validated['amount_paid'],
            'balance'        => $balance,
            'due_date'       => $parseDate($validated['due_date'], 'M j, Y') ?? $invoice->due_date,
            'payment_method' => $validated['payment_method'],
            'status'         => $validated['status'],
        ]);

        return response()->json(['message' => 'Invoice updated.', 'invoice' => $invoice->fresh()]);
    }

    /**
     * Admin approves a Pending payment.
     *
     * NEW: the invoice's final status is derived from what the tenant
     * actually paid (`amount_paid` vs `total_amount`):
     *
     *   amount_paid >= total_amount        → Paid    (balance = 0)
     *   0 < amount_paid < total_amount     → Partial (balance = total - paid)
     *   amount_paid == 0                   → Unpaid  (shouldn't happen here)
     *
     * "Verify" confirms the submitted amount — it does NOT top the
     * invoice up to fully-paid. A down-payment invoice stays Partial
     * after verification.
     *
     * Also: if the invoice is tied to a still-Pending reservation, the
     * reservation is finalized (approved) as a side-effect — that part
     * is unchanged from the previous fix.
     */
    public function verifyPayment(Request $request, Invoice $invoice)
    {
        if ($invoice->status !== 'Pending') {
            return response()->json([
                'message' => 'Invoice is not pending verification.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Derive the correct target status from the actual paid amount.
            [$status, $balance, $datePaid, $verified] =
                $this->deriveInvoiceStatus($invoice);

            $invoice->update([
                'status'      => $status,
                'balance'     => $balance,
                'date_paid'   => $datePaid,
                'verified_by' => $verified ? Auth::id() : null,
                'verified_at' => $verified ? now() : null,
            ]);

            // If this invoice belongs to a still-Pending reservation,
            // finalize the reservation side too.
            $this->syncLinkedReservation($invoice);

            DB::commit();

            $this->notifyTenant($invoice, 'verified');

            $message = match ($status) {
                'Paid'    => 'Payment verified successfully.',
                'Partial' => 'Partial payment verified. Remaining balance is still outstanding.',
                'Unpaid'  => 'Payment verified — nothing was paid on this invoice.',
                default   => 'Payment verified.',
            };

            return response()->json([
                'message' => $message,
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

        DB::beginTransaction();
        try {
            if ($invoice->receipt_path && Storage::disk('public')->exists($invoice->receipt_path)) {
                Storage::disk('public')->delete($invoice->receipt_path);
            }

            $revertStatus = $invoice->amount_paid > 0 ? 'Partial' : 'Unpaid';

            $invoice->update([
                'status'       => $revertStatus,
                'receipt_path' => null,
                'receipt_no'   => null,
                'verified_by'  => null,
                'verified_at'  => null,
            ]);

            DB::commit();

            $this->notifyTenant($invoice, 'rejected', $validated['admin_notes']);

            return response()->json([
                'message' => 'Payment rejected. Tenant has been notified.',
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
        // TODO: send actual notification
        return response()->json([
            'message' => "Reminder sent to {$invoice->tenant_name}.",
        ]);
    }

    // ============================================================
    // NEW: DERIVE INVOICE STATUS
    // ============================================================

    /**
     * Derive the "correct" status for an invoice based on what has
     * actually been paid.
     *
     * Returns [status, balance, datePaid, verified].
     *
     *  - paid >= total      → ['Paid',    0,        today, true]
     *  - 0 < paid < total   → ['Partial', total-paid, null, true]
     *  - paid == 0          → ['Unpaid',  total,    null,  false]
     *
     * The `+ 0.005` is a small float tolerance so ₱4999.9999 vs ₱5000
     * still counts as fully paid.
     */
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

    // ============================================================
    // RESERVATION SYNC (unchanged)
    // ============================================================

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
}