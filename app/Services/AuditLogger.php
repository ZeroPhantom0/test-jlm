<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Record an audit log entry.
     *
     * @param  array{
     *     action: string,
     *     module: string,
     *     description: string,
     *     status?: string,
     *     action_icon?: string,
     *     module_color?: string,
     *     subject_type?: string|null,
     *     subject_id?: int|null,
     *     properties?: array|null
     * }  $data
     */
    public static function log(array $data): AuditLog
    {
        $user = Auth::user();

        return AuditLog::create([
            'user_id'       => $user?->id,
            'user_name'     => $user?->name ?? ($data['user_name'] ?? 'Unknown'),
            'user_role'     => $user?->role ?? ($data['user_role'] ?? 'Unknown'),
            'action'        => $data['action'],
            'action_icon'   => $data['action_icon']   ?? self::iconFor($data['action']),
            'module'        => $data['module'],
            'module_color'  => $data['module_color']  ?? self::colorFor($data['module']),
            'description'   => $data['description'],
            'status'        => $data['status']        ?? 'success',
            'ip_address'    => request()->ip(),
            'user_agent'    => request()->userAgent(),
            'subject_type'  => $data['subject_type']  ?? null,
            'subject_id'    => $data['subject_id']    ?? null,
            'properties'    => $data['properties']    ?? null,
        ]);
    }

    public static function iconFor(string $action): string
    {
        return match (strtoupper($action)) {
            'LOGIN'          => 'fa-solid fa-right-to-bracket',
            'LOGIN FAILED'   => 'fa-solid fa-xmark',
            'LOGOUT'         => 'fa-solid fa-right-from-bracket',
            'CREATE'         => 'fa-solid fa-plus',
            'UPDATE'         => 'fa-regular fa-pen-to-square',
            'DELETE'         => 'fa-regular fa-trash-can',
            'APPROVE'        => 'fa-solid fa-check',
            'REJECT'         => 'fa-solid fa-xmark',
            'VERIFY'         => 'fa-solid fa-check-double',
            'SUBMIT'         => 'fa-solid fa-plus',
            'PUBLISH'        => 'fa-solid fa-bullhorn',
            default          => 'fa-solid fa-circle-info',
        };
    }

    public static function colorFor(string $module): string
    {
        return match (strtoupper($module)) {
            'AUTHENTICATION' => 'slate',
            'BILLING'        => 'green',
            'RESIDENTS'      => 'blue',
            'RESERVATIONS'   => 'teal',
            'ROOMS'          => 'indigo',
            'TENANTS'        => 'indigo',
            'VIRTUAL TOUR'   => 'pink',
            'ANNOUNCEMENTS'  => 'purple',
            'SERVICES'       => 'amber',
            default          => 'slate',
        };
    }
}