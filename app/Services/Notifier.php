<?php
// app/Services/Notifier.php

namespace App\Services;

use App\Http\Controllers\AdminNotificationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Writes bell notifications (user_notifications).
 *
 * The person who performed the action never gets notified about it:
 * the actor (the signed-in user, unless given) is always removed from
 * the recipient list.
 */
class Notifier
{
    /**
     * @param int[]|int $userIds recipients
     * @param array{type:string,title:string,description?:?string,icon?:?string,color?:?string,link?:?string,key?:?string} $n
     */
    public static function notify(array|int $userIds, array $n, ?int $actorId = null): int
    {
        $actorId ??= Auth::id();
        $userIds = collect((array) $userIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $actorId !== null && $id === (int) $actorId)
            ->values();

        if ($userIds->isEmpty()) {
            return 0;
        }

        $now  = now();
        $rows = $userIds->map(fn ($uid) => [
            'user_id'     => $uid,
            'actor_id'    => $actorId,
            'type'        => $n['type'],
            'title'       => mb_substr($n['title'], 0, 255),
            'description' => isset($n['description']) ? mb_substr((string) $n['description'], 0, 500) : null,
            'icon'        => $n['icon'] ?? null,
            'color'       => $n['color'] ?? null,
            'link'        => $n['link'] ?? null,
            'dedupe_key'  => $n['key'] ?? null,
            'read_at'     => null,
            'created_at'  => $now,
            'updated_at'  => $now,
        ])->all();

        try {
            // Same dedupe_key for the same recipient is ignored (no doubles)
            $inserted = DB::table('user_notifications')->insertOrIgnore($rows);
        } catch (\Throwable $e) {
            report($e);
            return 0;
        }

        AdminNotificationController::flushCache();

        return $inserted;
    }

    /** Every admin (minus the actor). */
    public static function admins(array $n, ?int $actorId = null): int
    {
        $ids = DB::table('users')->where('role', 'admin')->pluck('id')->all();
        return self::notify($ids, $n, $actorId);
    }

    /** Every tenant account (minus the actor). */
    public static function allTenants(array $n, ?int $actorId = null): int
    {
        $ids = DB::table('users')->where('role', 'tenant')->pluck('id')->all();
        return self::notify($ids, $n, $actorId);
    }

    /** Is the signed-in user a tenant? (null user = system / scheduler) */
    public static function actorIsTenant(): bool
    {
        $user = Auth::user();
        return $user !== null && method_exists($user, 'isTenant') && $user->isTenant();
    }

    /** Feed rows for one user, in the shape both bells already render. */
    public static function feedFor(int $userId, int $limit = 30): array
    {
        return DB::table('user_notifications')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(60))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($n) => [
                'id'          => 'n-' . $n->id,
                'type'        => $n->type,
                'icon'        => $n->icon ?: 'fa-regular fa-bell',
                'color'       => $n->color ?: 'text-slate-600 bg-slate-100',
                'title'       => $n->title,
                'description' => $n->description,
                'time'        => (string) $n->created_at,
                'unread'      => $n->read_at === null,
                'link'        => $n->link,
            ])
            ->all();
    }

    public static function markRead(int $userId, ?int $notificationId = null): void
    {
        DB::table('user_notifications')
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->when($notificationId, fn ($q) => $q->where('id', $notificationId))
            ->update(['read_at' => now(), 'updated_at' => now()]);

        AdminNotificationController::flushCache();
    }
}
