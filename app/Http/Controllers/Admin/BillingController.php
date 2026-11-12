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

    public function verifyPayment(Request $request, Invoice $invoice)
    {
        if ($invoice->status !== 'Pending') {
            return response()->json(['message' => 'Invoice is not pending verification.'], 422);
        }

        DB::beginTransaction();
        try {
            [$status, $balance, $datePaid, $verified] = $this->deriveInvoiceStatus($invoice);
            $reservation = $this->reservationForInvoice($invoice);

            $invoice->update([
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
        $validated = $request->validate(['admin_notes' => ['required', 'string', 'max:1000']]);

        if ($invoice->status !== 'Pending') {
            return response()->json(['message' => 'Invoice is not pending verification.'], 422);
        }

        $tenantEmail = $this->getTenantEmail($invoice) ?? $invoice->tenant_name;

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
                $revertedAmount = max(0, (float) $invoice->amount_paid - (float) $invoice->balance);
                $revertStatus   = $revertedAmount > 0 ? 'Partial' : 'Unpaid';
                $invoice->update([
                    'status' => $revertStatus,
                    'receipt_path' => null,
                    'receipt_no' => null,
                    'verified_by' => null,
                    'verified_at' => null,
                    'amount_paid' => $revertedAmount,
                ]);
            }

            AuditLogger::log([
                'action'       => 'REJECT',
                'module' => 'BILLING',
                'description'  => 'Payment rejected for ' . $tenantEmail . ' — ' . $invoice->transaction_no . ' — ' . $validated['admin_notes'],
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