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

    /** Mark one bell notification ("n-123") as read for this admin. */
    public function markRead(Request $request)
    {
        $validated = $request->validate(['id' => ['required', 'string', 'max:40']]);

        if (preg_match('/^n-(\d+)$/', $validated['id'], $m)) {
            \App\Services\Notifier::markRead((int) Auth::id(), (int) $m[1]);
        }

        return response()->json(['success' => true]);
    }

    /** Mark everything as read: bell notifications + unread tenant messages. */
    public function markAllRead(Request $request)
    {
        \App\Services\Notifier::markRead((int) Auth::id());

        DB::table('messages')
            ->where('sender_role', 'tenant')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        Cache::forget($this->cacheKey((int) Auth::id()));

        return response()->json(['success' => true]);
    }

    // ============================================================
    // PAYLOAD BUILDER
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

        // 2) Everything else is event-based: reservations, payments,
        //    maintenance, room changes and stay extensions done by TENANTS
        //    (see CreateBellNotifications). An admin never sees their own
        //    actions, and read state is stored per admin.
        foreach (\App\Services\Notifier::feedFor($currentUserId) as $n) {
            $notifications[] = $n;
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
