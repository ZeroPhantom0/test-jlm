<?php
// app/Http/Controllers/AdminNotificationController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminNotificationController extends Controller
{
    /**
     * Cache TTL in seconds. The admin header polls every 5s by default,
     * so 10s means each poll has a 50% chance of hitting cache, and
     * a 30s fallback poll essentially always hits cache. This cuts the
     * 12-query cost down to ~1 execution per 10s per admin.
     */
    private const CACHE_TTL = 10;

    private function cacheKey(int $adminId): string
    {
        return 'admin.notifications.' . $adminId;
    }

    /**
     * Invalidate the notification cache for every admin.
     * Call this from broadcast-worthy events:
     *   - MessageSent
     *   - ReservationUpdated
     *   - InvoiceUpdated
     *   - MaintenanceUpdated
     *   - ServiceRequestUpdated (room change / stay extension)
     *   - AnnouncementUpdated
     *
     * Example: in your event's broadcastWith() or a listener, call
     *   AdminNotificationController::flushCache();
     */
    public static function flushCache(): void
    {
        // If using Redis/Memcached with tags, replace with:
        //   Cache::tags(['admin-notifications'])->flush();
        //
        // Without tags, we need to know the admin IDs. Look them up once.
        try {
            $adminIds = DB::table('users')
                ->where('role', 'admin')
                ->pluck('id');

            foreach ($adminIds as $id) {
                Cache::forget('admin.notifications.' . $id);
            }
        } catch (\Throwable $e) {
            // Never let cache invalidation break a write
            report($e);
        }
    }

    public function index(Request $request)
    {
        $cacheKey = 'admin.notifications.' . Auth::id();

        $payload = Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            fn() => $this->buildPayload((int) Auth::id())
        );

        return response()->json($payload);
    }

    public function markMessageRead(Request $request)
    {
        $validated = $request->validate([
            'message_id' => ['required', 'integer', 'exists:messages,id'],
        ]);

        DB::table('messages')
            ->where('id', $validated['message_id'])
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Reading a message changes the unread count → drop cache for this admin
        Cache::forget($this->cacheKey((int) Auth::id()));

        return response()->json(['success' => true]);
    }

    // ============================================================
    // PAYLOAD BUILDER (unchanged logic, just extracted)
    // ============================================================
    private function buildPayload(int $currentUserId): array
    {
        $notifications = [];

        // 1) Unread messages from tenants
        $unreadMessages = DB::table('messages as m')
            ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
            ->join('tenants as t', 't.id', '=', 'c.tenant_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->where('m.sender_role', 'tenant')
            ->whereNull('m.read_at')
            ->where('m.deleted_by_receiver', 0)
            ->where('c.deleted_by_admin', 0)
            ->orderByDesc('m.created_at')
            ->limit(10)
            ->select(
                'm.id',
                'm.body',
                'm.created_at',
                'c.id as conversation_id',
                'c.subject',
                'u.name as tenant_name'
            )
            ->get();

        foreach ($unreadMessages as $m) {
            $notifications[] = [
                'id'          => 'msg-' . $m->id,
                'type'        => 'message',
                'icon'        => 'fa-regular fa-comment-dots',
                'color'       => 'text-blue-600 bg-blue-50',
                'title'       => 'New message from ' . $m->tenant_name,
                'description' => $m->subject ?: Str::limit($m->body, 60),
                'time'        => $m->created_at,
                'unread'      => true,
                'link'        => '/admin/messages?conversation=' . $m->conversation_id,
            ];
        }

        // 2) Pending reservations
        $pendingReservations = DB::table('reservations')
            ->where('status', 'Pending')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        foreach ($pendingReservations as $r) {
            $notifications[] = [
                'id'          => 'res-' . $r->id,
                'type'        => 'reservation',
                'icon'        => 'fa-regular fa-calendar-check',
                'color'       => 'text-emerald-600 bg-emerald-50',
                'title'       => 'New reservation pending',
                'description' => $r->tenant_name . ' requested ' . $r->desired_room,
                'time'        => $r->created_at,
                'unread'      => true,
                'link'        => '/admin/reservations',
            ];
        }

        // 2a) Tenant-cancelled reservations
        $cancelledReservations = DB::table('reservations')
            ->where('status', 'Rejected')
            ->whereNull('processed_by')
            ->orderByDesc(DB::raw('COALESCE(processed_at, updated_at, created_at)'))
            ->limit(10)
            ->get();

        foreach ($cancelledReservations as $r) {
            $notifications[] = [
                'id'          => 'res-can-' . $r->id,
                'type'        => 'reservation',
                'icon'        => 'fa-regular fa-circle-xmark',
                'color'       => 'text-amber-600 bg-amber-50',
                'title'       => 'Reservation cancelled by tenant',
                'description' => $r->tenant_name . ' — ' . $r->desired_room,
                'time'        => $r->processed_at ?? $r->updated_at ?? $r->created_at,
                'unread'      => true,
                'link'        => '/admin/reservations',
            ];
        }

        // 2b) Payments submitted by tenants (awaiting verification)
        $pendingPayments = DB::table('invoices')
            ->where('status', 'Pending')
            ->whereNotNull('receipt_path')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        foreach ($pendingPayments as $inv) {
            $notifications[] = [
                'id'          => 'pay-' . $inv->id,
                'type'        => 'payment',
                'icon'        => 'fa-solid fa-money-bill-transfer',
                'color'       => 'text-emerald-600 bg-emerald-50',
                'title'       => 'Payment submitted — ₱' . number_format((float) $inv->balance, 2),
                'description' => $inv->tenant_name . ' · ' . $inv->transaction_no
                    . ' · ' . ($inv->description ?? 'Invoice'),
                'time'        => $inv->updated_at ?? $inv->created_at,
                'unread'      => true,
                'link'        => '/admin/billing',
            ];
        }

        // 3) Overdue / unpaid invoices
        $overdueInvoices = DB::table('invoices')
            ->whereIn('status', ['Overdue', 'Unpaid'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->orderByDesc('due_date')
            ->limit(10)
            ->get();

        foreach ($overdueInvoices as $i) {
            $notifications[] = [
                'id'          => 'inv-' . $i->id,
                'type'        => 'invoice',
                'icon'        => 'fa-solid fa-credit-card',
                'color'       => 'text-orange-600 bg-orange-50',
                'title'       => 'Overdue invoice',
                'description' => $i->transaction_no . ' — ' . $i->tenant_name
                    . ' (₱' . number_format($i->balance, 2) . ')',
                'time'        => $i->due_date . ' 00:00:00',
                'unread'      => true,
                'link'        => '/admin/billing',
            ];
        }

        // 4) Pending maintenance requests
        $pendingMaintenance = DB::table('maintenance_requests')
            ->where('status', 'Pending')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        foreach ($pendingMaintenance as $mr) {
            $notifications[] = [
                'id'          => 'mnt-' . $mr->id,
                'type'        => 'maintenance',
                'icon'        => 'fa-solid fa-wrench',
                'color'       => 'text-rose-600 bg-rose-50',
                'title'       => 'Maintenance request (' . $mr->priority . ')',
                'description' => $mr->title,
                'time'        => $mr->created_at,
                'unread'      => true,
                'link'        => '/admin/services/maintenance',
            ];
        }

        // 4a) Tenant-cancelled maintenance requests
        $tenantCancelledMaintenance = DB::table('maintenance_requests')
            ->where('status', 'Cancelled')
            ->whereNotNull('tenant_id')
            ->where('updated_at', '>=', now()->subDays(30))
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        foreach ($tenantCancelledMaintenance as $mr) {
            $notifications[] = [
                'id'          => 'mnt-cancel-' . $mr->id,
                'type'        => 'maintenance',
                'icon'        => 'fa-regular fa-circle-xmark',
                'color'       => 'text-slate-600 bg-slate-100',
                'title'       => 'Maintenance request cancelled',
                'description' => $mr->title,
                'time'        => $mr->updated_at ?? $mr->created_at,
                'unread'      => true,
                'link'        => '/admin/services/maintenance',
            ];
        }

        // 4b) Pending room change requests
        $pendingRoomChanges = DB::table('room_change_requests')
            ->where('status', 'Pending')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        foreach ($pendingRoomChanges as $rc) {
            $notifications[] = [
                'id'          => 'rc-' . $rc->id,
                'type'        => 'room-change',
                'icon'        => 'fa-solid fa-house',
                'color'       => 'text-indigo-600 bg-indigo-50',
                'title'       => 'Room change request',
                'description' => $rc->current_room . ' → ' . $rc->requested_room,
                'time'        => $rc->created_at,
                'unread'      => true,
                'link'        => '/admin/services/room-change',
            ];
        }

        // 4c) Tenant-cancelled room change requests
        $tenantCancelledRoomChanges = DB::table('room_change_requests')
            ->where('status', 'Rejected')
            ->whereNull('processed_by')
            ->orderByDesc(DB::raw('COALESCE(processed_at, updated_at, created_at)'))
            ->limit(10)
            ->get();

        foreach ($tenantCancelledRoomChanges as $rc) {
            $notifications[] = [
                'id'          => 'rc-cancel-' . $rc->id,
                'type'        => 'room-change',
                'icon'        => 'fa-regular fa-circle-xmark',
                'color'       => 'text-amber-600 bg-amber-50',
                'title'       => 'Room change cancelled by tenant',
                'description' => $rc->current_room . ' → ' . $rc->requested_room,
                'time'        => $rc->processed_at ?? $rc->updated_at ?? $rc->created_at,
                'unread'      => true,
                'link'        => '/admin/services/room-change',
            ];
        }

        // 4d) Pending stay extension requests
        $pendingStayExtensions = DB::table('stay_extension_requests')
            ->where('status', 'Pending')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        foreach ($pendingStayExtensions as $se) {
            $notifications[] = [
                'id'          => 'se-' . $se->id,
                'type'        => 'stay-extension',
                'icon'        => 'fa-regular fa-calendar-plus',
                'color'       => 'text-purple-600 bg-purple-50',
                'title'       => 'Stay extension request',
                'description' => '+' . $se->extension_days . ' day(s) · ' . $se->reference_no,
                'time'        => $se->created_at,
                'unread'      => true,
                'link'        => '/admin/services/stay-extension',
            ];
        }

        // 4e) Tenant-cancelled stay extension requests
        $tenantCancelledStayExtensions = DB::table('stay_extension_requests')
            ->where('status', 'Rejected')
            ->whereNull('processed_by')
            ->orderByDesc(DB::raw('COALESCE(processed_at, updated_at, created_at)'))
            ->limit(10)
            ->get();

        foreach ($tenantCancelledStayExtensions as $se) {
            $notifications[] = [
                'id'          => 'se-cancel-' . $se->id,
                'type'        => 'stay-extension',
                'icon'        => 'fa-regular fa-circle-xmark',
                'color'       => 'text-amber-600 bg-amber-50',
                'title'       => 'Stay extension cancelled by tenant',
                'description' => '+' . $se->extension_days . ' day(s) · ' . $se->reference_no,
                'time'        => $se->processed_at ?? $se->updated_at ?? $se->created_at,
                'unread'      => true,
                'link'        => '/admin/services/stay-extension',
            ];
        }

        // 5) Recently published announcements (last 7 days)
        //    Excludes announcements authored by the current admin
        $announcements = DB::table('announcements')
            ->where('is_published', 1)
            ->where('created_at', '>=', now()->subDays(7))
            ->where(function ($q) use ($currentUserId) {
                $q->whereNull('created_by')
                    ->orWhere('created_by', '!=', $currentUserId);
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        foreach ($announcements as $a) {
            $notifications[] = [
                'id'          => 'ann-' . $a->id,
                'type'        => 'announcement',
                'icon'        => 'fa-solid fa-bullhorn',
                'color'       => 'text-purple-600 bg-purple-50',
                'title'       => 'Announcement: ' . $a->title,
                'description' => Str::limit(strip_tags($a->content), 60),
                'time'        => $a->created_at,
                'unread'      => false,
                'link'        => '/admin/announcements',
            ];
        }

        // 🛡️ Defensive filter — never leak report/export items to the UI
        $notifications = array_values(array_filter($notifications, function ($n) {
            $type = $n['type'] ?? '';
            $id   = $n['id']   ?? '';
            $link = $n['link'] ?? '';

            if (in_array($type, ['export', 'report', 'reports', 'report-export'], true)) {
                return false;
            }
            if (str_starts_with($id, 'exp-')) {
                return false;
            }
            if (is_string($link) && (
                str_contains($link, '/admin/reports') ||
                str_contains($link, '/reports/export')
            )) {
                return false;
            }
            return true;
        }));

        // Sort newest first
        usort($notifications, fn($x, $y) => strtotime($y['time']) <=> strtotime($x['time']));

        $unreadCount = collect($notifications)->where('unread', true)->count();

        return [
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ];
    }
}
