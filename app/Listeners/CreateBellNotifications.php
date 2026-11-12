<?php
// app/Listeners/CreateBellNotifications.php

namespace App\Listeners;

use App\Events\AnnouncementUpdated;
use App\Events\InvoiceUpdated;
use App\Events\MaintenanceUpdated;
use App\Events\ReservationUpdated;
use App\Events\ServiceRequestUpdated;
use App\Models\Announcement;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Reservation;
use App\Models\RoomChangeRequest;
use App\Models\StayExtensionRequest;
use App\Models\Tenant;
use App\Services\Notifier;
use Illuminate\Support\Str;

/**
 * Turns the app's existing domain events into bell notifications.
 *
 *   Tenant did it          → every admin is notified
 *   Admin / system did it  → the affected tenant is notified
 *   Announcement published → every tenant is notified
 *
 * The actor is always excluded (see Notifier). Messages are not handled
 * here — both bells read unread messages straight from the messages table.
 */
class CreateBellNotifications
{
    public function handle(object $event): void
    {
        try {
            match (true) {
                $event instanceof ReservationUpdated    => $this->reservation($event),
                $event instanceof InvoiceUpdated        => $this->invoice($event),
                $event instanceof MaintenanceUpdated    => $this->maintenance($event),
                $event instanceof ServiceRequestUpdated => $this->serviceRequest($event),
                $event instanceof AnnouncementUpdated   => $this->announcement($event),
                default                                 => null,
            };
        } catch (\Throwable $e) {
            // A notification must never break the action that triggered it
            report($e);
        }
    }

    // ============================================================
    // RESERVATIONS
    // ============================================================
    private function reservation(ReservationUpdated $e): void
    {
        if (! $e->reservationId) return;
        $r = Reservation::find($e->reservationId);
        if (! $r) return;

        $room = 'Room ' . $r->desired_room;

        if (Notifier::actorIsTenant()) {
            match ($e->action) {
                'created' => Notifier::admins([
                    'type' => 'reservation', 'key' => "res-created-{$r->id}",
                    'title' => 'New reservation request',
                    'description' => "{$r->tenant_name} requested {$room} · check-in " . ($r->check_in?->format('M j, Y') ?? '—'),
                    'icon' => 'fa-regular fa-calendar-check', 'color' => 'text-emerald-600 bg-emerald-50',
                    'link' => '/admin/reservations',
                ]),
                'cancelled' => Notifier::admins([
                    'type' => 'reservation', 'key' => "res-cancelled-{$r->id}",
                    'title' => 'Reservation cancelled by tenant',
                    'description' => "{$r->tenant_name} cancelled {$r->reference_no} ({$room})",
                    'icon' => 'fa-regular fa-circle-xmark', 'color' => 'text-amber-600 bg-amber-50',
                    'link' => '/admin/reservations',
                ]),
                default => null,
            };
            return;
        }

        // Admin or system (scheduler) → tell the tenant
        if (! $r->user_id) return;

        match ($e->action) {
            'approved' => Notifier::notify($r->user_id, [
                'type' => 'reservation', 'key' => "res-approved-{$r->id}",
                'title' => 'Reservation approved',
                'description' => "Your reservation {$r->reference_no} for {$room} was approved.",
                'icon' => 'fa-regular fa-circle-check', 'color' => 'text-emerald-600 bg-emerald-50',
                'link' => '/tenants',
            ]),
            'rejected' => Notifier::notify($r->user_id, [
                'type' => 'reservation', 'key' => "res-rejected-{$r->id}",
                'title' => 'Reservation declined',
                'description' => "Your reservation {$r->reference_no} for {$room} was declined"
                    . ($r->admin_notes ? ': ' . Str::limit($r->admin_notes, 120) : '.'),
                'icon' => 'fa-regular fa-circle-xmark', 'color' => 'text-rose-600 bg-rose-50',
                'link' => '/tenants/history',
            ]),
            'cancelled' => Notifier::notify($r->user_id, [
                'type' => 'reservation', 'key' => "res-cancelled-{$r->id}",
                'title' => 'Reservation cancelled',
                'description' => "{$r->reference_no} ({$room}) was cancelled because the remaining balance was not settled before check-in.",
                'icon' => 'fa-solid fa-ban', 'color' => 'text-rose-600 bg-rose-50',
                'link' => '/tenants/history',
            ]),
            default => null,
        };
    }

    // ============================================================
    // BILLING
    // ============================================================
    private function invoice(InvoiceUpdated $e): void
    {
        if (! $e->invoiceId) return;
        $inv = Invoice::withTrashed()->find($e->invoiceId);
        if (! $inv) return;

        $isServiceFee = (bool) preg_match('/#(RC|SE)-\d+/', (string) $inv->description);
        $amount = '₱' . number_format((float) $inv->amount_paid, 2);

        if (Notifier::actorIsTenant()) {
            // Room change / stay extension payments arrive with the request
            // itself, which already notifies — don't send two.
            if ($isServiceFee) return;

            match ($e->action) {
                'submitted' => Notifier::admins([
                    'type' => 'payment', 'key' => "inv-submitted-{$inv->id}-" . ($inv->updated_at?->timestamp ?? time()),
                    'title' => "Payment submitted — {$amount}",
                    'description' => "{$inv->tenant_name} · {$inv->transaction_no} · " . Str::limit((string) $inv->description, 60),
                    'icon' => 'fa-solid fa-money-bill-transfer', 'color' => 'text-emerald-600 bg-emerald-50',
                    'link' => '/admin/billing',
                ]),
                'cancelled' => Notifier::admins([
                    'type' => 'payment', 'key' => "inv-withdrawn-{$inv->id}-" . time(),
                    'title' => 'Payment submission withdrawn',
                    'description' => "{$inv->tenant_name} withdrew the payment for {$inv->transaction_no}",
                    'icon' => 'fa-regular fa-circle-xmark', 'color' => 'text-amber-600 bg-amber-50',
                    'link' => '/admin/billing',
                ]),
                default => null,
            };
            return;
        }

        $userId = $this->tenantUserIdForInvoice($inv);
        if (! $userId) return;

        match ($e->action) {
            'verified' => Notifier::notify($userId, [
                'type' => 'payment', 'key' => "inv-verified-{$inv->id}-{$inv->status}",
                'title' => $inv->status === 'Paid' ? 'Payment verified' : 'Partial payment verified',
                'description' => "{$amount} for {$inv->transaction_no} was verified"
                    . ($inv->status === 'Partial' ? ' · balance ₱' . number_format((float) $inv->balance, 2) : '.'),
                'icon' => 'fa-solid fa-circle-check', 'color' => 'text-emerald-600 bg-emerald-50',
                'link' => '/tenants/history',
            ]),
            'rejected' => Notifier::notify($userId, [
                'type' => 'payment', 'key' => "inv-rejected-{$inv->id}-" . time(),
                'title' => 'Payment rejected',
                'description' => "Your payment for {$inv->transaction_no} was rejected. Please check your email for the reason.",
                'icon' => 'fa-solid fa-circle-xmark', 'color' => 'text-rose-600 bg-rose-50',
                'link' => '/tenants',
            ]),
            default => null,
        };
    }

    private function tenantUserIdForInvoice(Invoice $inv): ?int
    {
        if ($inv->tenant_id) {
            $uid = Tenant::whereKey($inv->tenant_id)->value('user_id');
            if ($uid) return (int) $uid;
        }
        // Booking-time invoices have no tenant_id — use the reservation
        if (preg_match('/#RSV-\d+/', (string) $inv->description, $m)) {
            $uid = Reservation::where('reference_no', $m[0])->value('user_id');
            if ($uid) return (int) $uid;
        }
        return null;
    }

    // ============================================================
    // MAINTENANCE
    // ============================================================
    private function maintenance(MaintenanceUpdated $e): void
    {
        if (! $e->requestId) return;
        $m = MaintenanceRequest::find($e->requestId);
        if (! $m) return;

        if (Notifier::actorIsTenant()) {
            match ($e->action) {
                'created' => Notifier::admins([
                    'type' => 'maintenance', 'key' => "mnt-created-{$m->id}",
                    'title' => "New maintenance request ({$m->priority})",
                    'description' => $m->title . ($m->category ? " · {$m->category}" : ''),
                    'icon' => 'fa-solid fa-wrench', 'color' => 'text-rose-600 bg-rose-50',
                    'link' => '/admin/services/maintenance',
                ]),
                'cancelled' => Notifier::admins([
                    'type' => 'maintenance', 'key' => "mnt-cancelled-{$m->id}",
                    'title' => 'Maintenance request cancelled by tenant',
                    'description' => $m->title,
                    'icon' => 'fa-regular fa-circle-xmark', 'color' => 'text-slate-600 bg-slate-100',
                    'link' => '/admin/services/maintenance',
                ]),
                default => null,
            };
            return;
        }

        $userId = $m->tenant_id ? Tenant::whereKey($m->tenant_id)->value('user_id') : null;
        if (! $userId || ! in_array($e->action, ['created', 'updated'], true)) return;

        // One notification per status the request reaches
        Notifier::notify((int) $userId, [
            'type' => 'maintenance', 'key' => "mnt-{$m->id}-" . Str::slug($m->status),
            'title' => $e->action === 'created' ? 'Maintenance request filed for you' : "Maintenance {$m->status}",
            'description' => $m->title . ' — ' . match ($m->status) {
                'In Progress' => 'our staff is working on it.',
                'Completed'   => 'the issue has been resolved.',
                'Cancelled'   => 'the request was cancelled by the admin.',
                default       => 'status: ' . $m->status . '.',
            },
            'icon' => $m->status === 'Completed' ? 'fa-solid fa-circle-check' : 'fa-solid fa-wrench',
            'color' => match ($m->status) {
                'Completed'   => 'text-emerald-600 bg-emerald-50',
                'In Progress' => 'text-blue-600 bg-blue-50',
                default       => 'text-slate-600 bg-slate-100',
            },
            'link' => '/tenants/services/maintenance',
        ]);
    }

    // ============================================================
    // ROOM CHANGE / STAY EXTENSION
    // ============================================================
    private function serviceRequest(ServiceRequestUpdated $e): void
    {
        if (! $e->requestId) return;

        $isRoomChange = $e->type === 'room_change';
        $req = $isRoomChange
            ? RoomChangeRequest::with('tenant')->find($e->requestId)
            : StayExtensionRequest::with('tenant')->find($e->requestId);
        if (! $req) return;

        $label  = $isRoomChange ? 'room change' : 'stay extension';
        $type   = $isRoomChange ? 'room-change' : 'stay-extension';
        $detail = $isRoomChange
            ? "{$req->current_room} → {$req->requested_room}"
            : '+' . $req->extension_days . ' day' . ((int) $req->extension_days === 1 ? '' : 's') . ' · ' . $req->reference_no;
        $prefix = $isRoomChange ? 'rc' : 'se';

        if (Notifier::actorIsTenant()) {
            $tenantName = $req->tenant?->user?->name ?? 'A tenant';
            match ($e->action) {
                'created' => Notifier::admins([
                    'type' => $type, 'key' => "{$prefix}-created-{$req->id}",
                    'title' => 'New ' . $label . ' request',
                    'description' => "{$tenantName} · {$detail} · payment awaiting verification",
                    'icon' => $isRoomChange ? 'fa-solid fa-house' : 'fa-regular fa-calendar-plus',
                    'color' => $isRoomChange ? 'text-indigo-600 bg-indigo-50' : 'text-purple-600 bg-purple-50',
                    'link' => $isRoomChange ? '/admin/services/room-change' : '/admin/services/stay-extension',
                ]),
                'cancelled' => Notifier::admins([
                    'type' => $type, 'key' => "{$prefix}-cancelled-{$req->id}",
                    'title' => ucfirst($label) . ' cancelled by tenant',
                    'description' => "{$tenantName} · {$detail}",
                    'icon' => 'fa-regular fa-circle-xmark', 'color' => 'text-amber-600 bg-amber-50',
                    'link' => $isRoomChange ? '/admin/services/room-change' : '/admin/services/stay-extension',
                ]),
                default => null,
            };
            return;
        }

        $userId = $req->tenant?->user_id;
        if (! $userId || ! in_array($e->action, ['approved', 'rejected'], true)) return;

        $approved = $e->action === 'approved';
        Notifier::notify((int) $userId, [
            'type' => $type, 'key' => "{$prefix}-{$e->action}-{$req->id}",
            'title' => ucfirst($label) . ($approved ? ' approved' : ' declined'),
            'description' => $detail . ($approved
                ? ' — your request was approved.'
                : ($req->admin_notes ? ' — ' . Str::limit($req->admin_notes, 120) : ' — your request was declined.')),
            'icon' => $approved ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark',
            'color' => $approved ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50',
            'link' => $isRoomChange ? '/tenants/services/room-change' : '/tenants/services/stay-extension',
        ]);
    }

    // ============================================================
    // ANNOUNCEMENTS (admin → all tenants)
    // ============================================================
    private function announcement(AnnouncementUpdated $e): void
    {
        if (! in_array($e->action, ['created', 'updated'], true)) return;

        $a = Announcement::find($e->announcementId);
        if (! $a || ! $a->is_published) return;

        // Keyed per announcement: an edit doesn't re-notify, but an
        // announcement that gets published later still notifies once.
        Notifier::allTenants([
            'type' => 'announcement', 'key' => "ann-{$a->id}",
            'title' => 'New announcement: ' . Str::limit($a->title, 80),
            'description' => Str::limit(strip_tags((string) $a->content), 100),
            'icon' => 'fa-solid fa-bullhorn', 'color' => 'text-purple-600 bg-purple-50',
            'link' => '/tenants/announcements',
        ]);
    }
}
