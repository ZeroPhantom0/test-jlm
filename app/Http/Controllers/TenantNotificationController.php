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

        // Event-based notifications from the admin / system: reservation,
        // billing, maintenance, room change, stay extension decisions and
        // announcements (see CreateBellNotifications). Never the tenant's
        // own actions. Works even before a tenant row exists (e.g. a first
        // reservation that was declined).
        $feed = \App\Services\Notifier::feedFor((int) $user->id);

        if (!$tenant) {
            return response()->json([
                'notifications' => $feed,
                'unread_count'  => collect($feed)->where('unread', true)->count(),
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

        foreach ($feed as $n) {
            $notifications[] = $n;
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

    /** Mark one bell notification ("n-123") as read. */
    public function markRead(Request $request)
    {
        $validated = $request->validate(['id' => ['required', 'string', 'max:40']]);

        if (preg_match('/^n-(\d+)$/', $validated['id'], $m)) {
            \App\Services\Notifier::markRead((int) Auth::id(), (int) $m[1]);
        }

        return response()->json(['success' => true]);
    }

    /** Mark everything as read: bell notifications + unread admin messages. */
    public function markAllRead(Request $request)
    {
        $user = Auth::user();
        \App\Services\Notifier::markRead((int) $user->id);

        $tenant = Tenant::where('user_id', $user->id)->first();
        if ($tenant) {
            DB::table('messages as m')
                ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
                ->where('c.tenant_id', $tenant->id)
                ->where('m.sender_role', 'admin')
                ->whereNull('m.read_at')
                ->update(['m.read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }
}
