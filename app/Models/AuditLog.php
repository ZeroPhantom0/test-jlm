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
        'created_at' => 'datetime',
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

    /**
     * Sort by created_at.
     *
     * @param  string|null  $order  'newest' (default) or 'oldest'
     */
    public function scopeOrderByDate($query, ?string $order)
    {
        $direction = strtolower((string) $order) === 'oldest' ? 'asc' : 'desc';

        return $query->orderBy('created_at', $direction)->orderBy('id', $direction);
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
            'tenant'   => 'teal',
            'resident' => 'teal',
            default    => 'gray',
        };
    }

    /**
     * Unix timestamp in milliseconds — used by the client-side
     * newest/oldest sorter. Falls back to 0 if created_at is null.
     */
    public function getTimestampAttribute(): int
    {
        return $this->created_at
            ? (int) $this->created_at->getTimestampMs()
            : 0;
    }
}