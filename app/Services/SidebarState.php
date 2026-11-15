<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Live sidebar data (badges + nav visibility). Rendered into the layouts on
 * page load, and re-fetched by the sidebars whenever a Pusher event fires,
 * so counts stay current without a reload.
 */
class SidebarState
{
    /** Things waiting for an admin, keyed by sidebar nav key. */
    public static function admin(): array
    {
        $maintenance = DB::table('maintenance_requests')->where('status', 'Pending')->count();
        $roomChange  = DB::table('room_change_requests')->where('status', 'Pending')->count();
        $stayExt     = DB::table('stay_extension_requests')->where('status', 'Pending')->count();

        return [
            'badges' => [
                'reservations'            => DB::table('reservations')->where('status', 'Pending')->count(),
                'billing'                 => DB::table('invoices')->whereNull('deleted_at')->where('status', 'Pending')->count(),
                'services'                => $maintenance + $roomChange + $stayExt,
                'services.maintenance'    => $maintenance,
                'services.room-change'    => $roomChange,
                'services.stay-extension' => $stayExt,
                'messages'                => DB::table('messages')
                    ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
                    ->where('conversations.deleted_by_admin', false)
                    ->whereNull('conversations.deleted_at')
                    ->where('messages.sender_role', 'tenant')
                    ->whereNull('messages.read_at')
                    ->whereNull('messages.deleted_at')
                    ->count(),
            ],
        ];
    }

    /** Nav state + badges for one tenant. */
    public static function tenant(User $user): array
    {
        $tenant     = Tenant::where('user_id', $user->id)->first();
        $isApproved = Tenant::isApproved($user->id);

        return [
            'isTenant'       => $isApproved,
            // Services nav is hidden while there's a remaining balance in Billing
            'servicesLocked' => $isApproved && Tenant::hasUnsettledBalance($user->id),
            'badges'         => [
                'messages'     => $tenant ? DB::table('messages')
                    ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
                    ->where('conversations.tenant_id', $tenant->id)
                    ->where('conversations.deleted_by_tenant', false)
                    ->whereNull('conversations.deleted_at')
                    ->where('messages.sender_role', 'admin')
                    ->whereNull('messages.read_at')
                    ->whereNull('messages.deleted_at')
                    ->count() : 0,
                'reservations' => DB::table('reservations')->where('user_id', $user->id)->where('status', 'Pending')->count(),
            ],
        ];
    }
}
