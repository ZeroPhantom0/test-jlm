<?php
// app/Http/Controllers/AdminNotificationController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
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

        // 4a) Pending room change requests
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

        // 4b) Pending stay extension requests
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

        // 5) Recently published announcements (last 7 days)
        $announcements = DB::table('announcements')
            ->where('is_published', 1)
            ->where('created_at', '>=', now()->subDays(7))
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

        // 6) Recent report exports
        $exports = DB::table('audit_logs')
            ->where('module', 'REPORTS')
            ->where('action', 'EXPORT')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        foreach ($exports as $log) {
            $notifications[] = [
                'id'          => 'exp-' . $log->id,
                'type'        => 'export',
                'icon'        => 'fa-solid fa-file-export',
                'color'       => 'text-sky-600 bg-sky-50',
                'title'       => 'Report exported',
                'description' => $log->description,
                'time'        => $log->created_at,
                'unread'      => false,
                'link'        => '/admin/reports',
            ];
        }

        // Sort newest first
        usort($notifications, fn ($x, $y) => strtotime($y['time']) <=> strtotime($x['time']));

        $unreadCount = collect($notifications)->where('unread', true)->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
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

        return response()->json(['success' => true]);
    }
}