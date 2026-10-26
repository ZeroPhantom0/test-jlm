<?php
// app/Http/Controllers/TenantNotificationController.php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user   = Auth::user();
        $tenant = Tenant::with('approvedReservation')
            ->where('user_id', $user->id)
            ->first();

        $notifications = [];

        if (!$tenant) {
            return response()->json([
                'notifications' => [],
                'unread_count'  => 0,
            ]);
        }

        // 1) Unread messages from admin
        $unreadMessages = DB::table('messages as m')
            ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
            ->where('c.tenant_id', $tenant->id)
            ->where('m.sender_role', 'admin')
            ->whereNull('m.read_at')
            ->where('m.deleted_by_receiver', 0)
            ->where('c.deleted_by_tenant', 0)
            ->orderByDesc('m.created_at')
            ->limit(10)
            ->select(
                'm.id',
                'm.body',
                'm.created_at',
                'c.id as conversation_id',
                'c.subject'
            )
            ->get();

        foreach ($unreadMessages as $m) {
            $notifications[] = [
                'id'          => 'msg-' . $m->id,
                'type'        => 'message',
                'icon'        => 'fa-regular fa-comment-dots',
                'color'       => 'text-blue-600 bg-blue-50',
                'title'       => 'New message from Admin',
                'description' => $m->subject ?: Str::limit($m->body, 60),
                'time'        => $m->created_at,
                'unread'      => true,
                'link'        => '/tenants/messages?conversation=' . $m->conversation_id,
            ];
        }

        // 2) Outstanding / overdue invoices
        $invoices = DB::table('invoices')
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['Unpaid', 'Partial', 'Overdue'])
            ->orderByRaw("FIELD(status, 'Overdue', 'Unpaid', 'Partial')")
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        foreach ($invoices as $i) {
            $isOverdue = $i->status === 'Overdue'
                || ($i->due_date && $i->due_date < now()->toDateString());

            $notifications[] = [
                'id'          => 'inv-' . $i->id,
                'type'        => 'invoice',
                'icon'        => 'fa-solid fa-credit-card',
                'color'       => $isOverdue
                    ? 'text-rose-600 bg-rose-50'
                    : 'text-orange-600 bg-orange-50',
                'title'       => $isOverdue ? 'Overdue invoice' : 'Outstanding balance',
                'description' => $i->description . ' — ₱'
                    . number_format((float) $i->balance, 2),
                'time'        => $i->due_date
                    ? $i->due_date . ' 00:00:00'
                    : $i->created_at,
                'unread'      => true,
                'link'        => '/tenants/billing',
            ];
        }

        // 2a) Recently verified invoices (Paid / Partial after admin verify)
        $resolvedInvoices = DB::table('invoices')
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['Paid', 'Partial'])
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subDays(14))
            ->orderByDesc('verified_at')
            ->limit(10)
            ->get();

        foreach ($resolvedInvoices as $inv) {
            $isPaid = $inv->status === 'Paid';

            $notifications[] = [
                'id'          => 'invok-' . $inv->id . '-' . Str::slug($inv->status),
                'type'        => 'invoice',
                'icon'        => $isPaid
                    ? 'fa-solid fa-circle-check'
                    : 'fa-solid fa-circle-exclamation',
                'color'       => $isPaid
                    ? 'text-emerald-600 bg-emerald-50'
                    : 'text-amber-600 bg-amber-50',
                'title'       => $isPaid ? 'Payment verified' : 'Partial payment verified',
                'description' => $inv->transaction_no . ' · ₱'
                    . number_format((float) $inv->amount_paid, 2)
                    . ($isPaid ? ' fully paid' : ' — remaining ₱'
                        . number_format((float) $inv->balance, 2)),
                'time'        => $inv->verified_at,
                'unread'      => true,
                'link'        => '/tenants/billing',
            ];
        }

        // 2b) Recently rejected payments (from audit log)
        $rejectedLogs = DB::table('audit_logs')
            ->where('module', 'BILLING')
            ->where('action', 'REJECT')
            ->where('description', 'like', '%' . ($user->email ?? '__no_match__') . '%')
            ->where('created_at', '>=', now()->subDays(14))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        foreach ($rejectedLogs as $log) {
            $notifications[] = [
                'id'          => 'invrej-' . $log->id,
                'type'        => 'invoice',
                'icon'        => 'fa-solid fa-circle-xmark',
                'color'       => 'text-rose-600 bg-rose-50',
                'title'       => 'Payment rejected',
                'description' => $log->description,
                'time'        => $log->created_at,
                'unread'      => true,
                'link'        => '/tenants/billing',
            ];
        }

        // 3) Reservation status updates (admin-driven only)
        $reservations = DB::table('reservations')
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereIn('status', ['Approved', 'Expired'])
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'Rejected')
                            ->whereNotNull('processed_by'); // only admin rejections
                    });
            })
            ->orderByDesc(DB::raw('COALESCE(processed_at, updated_at, created_at)'))
            ->limit(10)
            ->get();

        foreach ($reservations as $r) {
            $color = match ($r->status) {
                'Approved' => 'text-emerald-600 bg-emerald-50',
                'Rejected' => 'text-rose-600 bg-rose-50',
                'Expired'  => 'text-slate-600 bg-slate-100',
                default    => 'text-slate-600 bg-slate-100',
            };
            $icon = match ($r->status) {
                'Approved' => 'fa-regular fa-circle-check',
                'Rejected' => 'fa-regular fa-circle-xmark',
                'Expired'  => 'fa-regular fa-clock',
                default    => 'fa-regular fa-circle',
            };

            $notifications[] = [
                'id'          => 'res-' . $r->id . '-' . Str::slug($r->status),
                'type'        => 'reservation',
                'icon'        => $icon,
                'color'       => $color,
                'title'       => 'Reservation ' . $r->status,
                'description' => $r->desired_room . ' · ' . $r->reference_no,
                'time'        => $r->processed_at ?? $r->updated_at ?? $r->created_at,
                'unread'      => true,
                'link'        => '/tenants/reservations',
            ];
        }

        // =========================================================
        // 4) MAINTENANCE REQUESTS
        //    Only show In Progress and Completed.
        //    Cancelled is deliberately excluded — if the tenant cancelled
        //    their own request, we shouldn't show it back to them.
        // =========================================================
        $maintenance = DB::table('maintenance_requests')
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['In Progress', 'Completed'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        foreach ($maintenance as $m) {
            $statusSlug = Str::slug($m->status);

            $color = match ($m->status) {
                'Completed'   => 'text-emerald-600 bg-emerald-50',
                'In Progress' => 'text-blue-600 bg-blue-50',
                default       => 'text-slate-600 bg-slate-100',
            };

            $icon = match ($m->status) {
                'Completed'   => 'fa-regular fa-circle-check',
                'In Progress' => 'fa-solid fa-spinner',
                default       => 'fa-regular fa-circle',
            };

            $notifications[] = [
                'id'          => 'mnt-' . $m->id . '-' . $statusSlug,
                'type'        => 'maintenance',
                'icon'        => $icon,
                'color'       => $color,
                'title'       => 'Maintenance ' . $m->status,
                'description' => $m->title
                    . ($m->assigned_staff ? ' · ' . $m->assigned_staff : ''),
                'time'        => $m->completed_at ?? $m->updated_at ?? $m->created_at,
                'unread'      => true,
                'link'        => '/tenants/services/maintenance',
            ];
        }

        // =========================================================
        // 5) ROOM CHANGE REQUESTS
        //    Only admin-driven decisions (processed_by NOT NULL).
        //    Tenant self-cancellations (no processed_by) are excluded.
        // =========================================================
        $roomChanges = DB::table('room_change_requests')
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['Approved', 'Rejected'])
            ->whereNotNull('processed_by')
            ->orderByDesc(DB::raw('COALESCE(processed_at, updated_at, created_at)'))
            ->limit(10)
            ->get();

        foreach ($roomChanges as $rc) {
            $statusSlug = Str::slug($rc->status);
            $isApproved = $rc->status === 'Approved';

            $notifications[] = [
                'id'          => 'rc-' . $rc->id . '-' . $statusSlug,
                'type'        => 'room-change',
                'icon'        => $isApproved
                    ? 'fa-regular fa-circle-check'
                    : 'fa-regular fa-circle-xmark',
                'color'       => $isApproved
                    ? 'text-emerald-600 bg-emerald-50'
                    : 'text-rose-600 bg-rose-50',
                'title'       => 'Room change ' . $rc->status,
                'description' => $rc->current_room . ' → ' . $rc->requested_room,
                'time'        => $rc->processed_at ?? $rc->updated_at ?? $rc->created_at,
                'unread'      => true,
                'link'        => '/tenants/services/room-change',
            ];
        }

        // =========================================================
        // 6) STAY EXTENSION REQUESTS
        //    Only admin-driven decisions (processed_by NOT NULL).
        //    Tenant self-cancellations (no processed_by) are excluded.
        // =========================================================
        $stayExtensions = DB::table('stay_extension_requests')
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['Approved', 'Rejected'])
            ->whereNotNull('processed_by')
            ->orderByDesc(DB::raw('COALESCE(processed_at, updated_at, created_at)'))
            ->limit(10)
            ->get();

        foreach ($stayExtensions as $se) {
            $statusSlug = Str::slug($se->status);
            $isApproved = $se->status === 'Approved';

            $notifications[] = [
                'id'          => 'se-' . $se->id . '-' . $statusSlug,
                'type'        => 'stay-extension',
                'icon'        => $isApproved
                    ? 'fa-regular fa-circle-check'
                    : 'fa-regular fa-circle-xmark',
                'color'       => $isApproved
                    ? 'text-emerald-600 bg-emerald-50'
                    : 'text-rose-600 bg-rose-50',
                'title'       => 'Stay extension ' . $se->status,
                'description' => '+' . $se->extension_days . ' day(s) · ' . $se->reference_no,
                'time'        => $se->processed_at ?? $se->updated_at ?? $se->created_at,
                'unread'      => true,
                'link'        => '/tenants/services/stay-extension',
            ];
        }

        // 7) Recently published announcements (last 7 days)
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
                'link'        => '/tenants/announcements',
            ];
        }

        // Sort newest first
        usort($notifications, fn($x, $y) => strtotime($y['time']) <=> strtotime($x['time']));

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

        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return response()->json(['success' => false], 403);
        }

        $belongs = DB::table('messages as m')
            ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
            ->where('m.id', $validated['message_id'])
            ->where('c.tenant_id', $tenant->id)
            ->exists();

        if (!$belongs) {
            return response()->json(['success' => false], 403);
        }

        DB::table('messages')
            ->where('id', $validated['message_id'])
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
