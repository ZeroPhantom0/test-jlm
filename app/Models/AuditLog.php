<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'action',
        'action_icon',
        'module',
        'module_color',
        'description',
        'status',
        'ip_address',
        'user_agent',
        'subject_type',
        'subject_id',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    /* ---------- Scopes for filtering ---------- */

    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('user_name', 'like', "%{$term}%")
              ->orWhere('action', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('module', 'like', "%{$term}%");
        });
    }

    public function scopeBetweenDates($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        return $query;
    }

    public function scopeStatus($query, ?string $status)
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeModule($query, ?string $module)
    {
        return $module ? $query->where('module', $module) : $query;
    }

    /* ---------- Helpers for the view ---------- */

    public function getInitialAttribute(): string
    {
        return strtoupper(mb_substr($this->user_name ?: 'U', 0, 1));
    }

    public function getAvatarColorAttribute(): string
    {
        return match (strtolower($this->user_role)) {
            'admin'    => 'purple',
            'tenant',  => 'tenant',
            'resident' => 'tenant',
            default    => 'gray',
        };
    }
}