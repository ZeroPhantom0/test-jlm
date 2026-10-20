<?php
// app/Http/Controllers/Tenant/TenantDashboardController.php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Reservation;
use App\Models\RoomChangeRequest;
use App\Models\StayExtensionRequest;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TenantDashboardController extends Controller
{
    /**
     * Display the tenant's dashboard with live data.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // -------------------------------------------------------
        // Tenant profile + current room + reservation dates
        // -------------------------------------------------------
        $tenant = Tenant::with(['room', 'approvedReservation'])
            ->where('user_id', $user->id)
            ->first();

        $room = $tenant?->room;

        // -------------------------------------------------------
        // Outstanding balance — UNPAID INVOICES
        // -------------------------------------------------------
        $unpaidInvoices = Invoice::where('tenant_id', $tenant?->id)
            ->whereIn('status', ['Unpaid', 'Partial', 'Overdue'])
            ->orderBy('due_date')
            ->get();

        $invoiceBalance = (float) $unpaidInvoices->sum('balance');
        $invoiceCount   = $unpaidInvoices->count();

        // -------------------------------------------------------
        // Outstanding balance — APPROVED RESERVATION REMAINING
        //
        // When a reservation is approved, the admin may or may not
        // have billed the `remaining` amount as an Invoice yet.
        //
        // - If an invoice for it exists (description contains
        //   "remaining" / "reservation"), the invoice is the
        //   source of truth — do NOT double-count.
        // - Otherwise, add the reservation's remaining into the
        //   outstanding balance so the tenant sees what they owe.
        // -------------------------------------------------------
        $reservationRemaining = 0.0;

        if ($tenant?->approvedReservation) {
            $approved = $tenant->approvedReservation;

            if ($approved->status === 'Approved' && (float) $approved->remaining > 0) {
                $billedRemaining = (float) Invoice::where('tenant_id', $tenant->id)
                    ->whereIn('status', ['Unpaid', 'Partial', 'Overdue', 'Pending'])
                    ->where(function ($q) {
                        $q->where('description', 'like', '%remaining%')
                          ->orWhere('description', 'like', '%reservation%');
                    })
                    ->sum('balance');

                $reservationRemaining = max(0, (float) $approved->remaining - $billedRemaining);
            }
        }

        $outstandingBalance = $invoiceBalance + $reservationRemaining;
        $unpaidCount        = $invoiceCount + ($reservationRemaining > 0 ? 1 : 0);

        // -------------------------------------------------------
        // Latest announcements (pinned first)
        // -------------------------------------------------------
        $announcements = Announcement::published()
            ->orderByDesc('is_pinned')
            ->latest()
            ->limit(5)
            ->get();

        // -------------------------------------------------------
        // History preview (Reservations + 3 Services ONLY)
        // -------------------------------------------------------
        $historyItems = collect();

        if ($tenant) {
            $historyItems = collect()
                ->merge($this->historyReservations($user))
                ->merge($this->historyMaintenance($tenant))
                ->merge($this->historyRoomChanges($tenant))
                ->merge($this->historyStayExtensions($tenant))
                ->sortByDesc('timestamp')
                ->take(4)
                ->values();
        }

        // -------------------------------------------------------
        // Greeting name (first word of full name)
        // -------------------------------------------------------
        $greetingName = explode(' ', trim($user->name))[0] ?: 'Tenant';

        return view('tenants.dashboard', compact(
            'user',
            'tenant',
            'room',
            'outstandingBalance',
            'unpaidCount',
            'announcements',
            'historyItems',
            'greetingName'
        ));
    }

    // ============================================================
    // HISTORY SOURCES (only Reservations + 3 Services)
    // ============================================================

    private function historyReservations($user)
    {
        return Reservation::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('tenant_name', $user->name);
            })
            ->latest()
            ->get()
            ->map(function (Reservation $r) {
                $status = match ($r->status) {
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                    'Expired'  => 'expired',
                    default    => 'pending',
                };

                return [
                    'key'       => 'rsv-' . $r->id,
                    'icon'      => 'fa-regular fa-calendar-check',
                    'title'     => 'Reservation — ' . $r->desired_room,
                    'date'      => $r->created_at?->format('M d, Y'),
                    'timestamp' => $r->processed_at ?? $r->created_at,
                    'status'    => $status,
                ];
            });
    }

    private function historyMaintenance(Tenant $tenant)
    {
        return MaintenanceRequest::where('tenant_id', $tenant->id)
            ->latest()
            ->get()
            ->map(function (MaintenanceRequest $m) {
                $status = match ($m->status) {
                    'Completed' => 'completed',
                    'Cancelled' => 'cancelled',
                    default     => 'pending',
                };

                return [
                    'key'       => 'mt-' . $m->id,
                    'icon'      => 'fa-solid fa-wrench',
                    'title'     => $m->title,
                    'date'      => $m->created_at?->format('M d, Y'),
                    'timestamp' => $m->completed_at ?? $m->created_at,
                    'status'    => $status,
                ];
            });
    }

    private function historyRoomChanges(Tenant $tenant)
    {
        return RoomChangeRequest::where('tenant_id', $tenant->id)
            ->latest()
            ->get()
            ->map(function (RoomChangeRequest $r) {
                $status = match ($r->status) {
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                    default    => 'pending',
                };

                return [
                    'key'       => 'rc-' . $r->id,
                    'icon'      => 'fa-solid fa-house',
                    'title'     => 'Room Change — ' . $r->current_room . ' → ' . $r->requested_room,
                    'date'      => $r->created_at?->format('M d, Y'),
                    'timestamp' => $r->processed_at ?? $r->created_at,
                    'status'    => $status,
                ];
            });
    }

    private function historyStayExtensions(Tenant $tenant)
    {
        return StayExtensionRequest::where('tenant_id', $tenant->id)
            ->latest()
            ->get()
            ->map(function (StayExtensionRequest $s) {
                $status = match ($s->status) {
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                    default    => 'pending',
                };

                return [
                    'key'       => 'se-' . $s->id,
                    'icon'      => 'fa-regular fa-calendar-plus',
                    'title'     => 'Stay Extension — +' . $s->extension_days . ' day' . ($s->extension_days === 1 ? '' : 's'),
                    'date'      => $s->created_at?->format('M d, Y'),
                    'timestamp' => $s->processed_at ?? $s->created_at,
                    'status'    => $status,
                ];
            });
    }
}