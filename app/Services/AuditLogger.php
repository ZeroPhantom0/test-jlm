<?php
// app/Services/AuditLogger.php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Record an audit log entry.
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
            'EXPORT'         => 'fa-solid fa-file-export', // Added EXPORT icon
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
            'REPORTS'        => 'sky', // Added REPORTS color (requires updating the blade file)
            default          => 'slate',
        };
    }
}