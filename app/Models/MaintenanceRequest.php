<?php
// app/Models/MaintenanceRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRequest extends Model
{
    protected $table = 'maintenance_requests';

    protected $fillable = [
        'tenant_id',
        'room_id',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'assigned_staff',
        'scheduled_date',
        'preferred_date',
        'preferred_time',
        'completed_at',
    ];

    protected $casts = [
        'preferred_date'  => 'date',
        'scheduled_date'  => 'date',
        'completed_at'    => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getPriorityClassAttribute(): string
    {
        return match ($this->priority) {
            'Critical' => 'bg-rose-100 text-rose-700',
            'High'     => 'bg-rose-100 text-rose-600',
            'Medium'   => 'bg-yellow-100 text-amber-700',
            'Low'      => 'bg-blue-100 text-blue-600',
            default    => 'bg-slate-100 text-slate-600',
        };
    }

    public function getStatusClassAttribute(): string
    {
        return match ($this->status) {
            'Pending'     => 'bg-yellow-100 text-amber-700',
            'In Progress' => 'bg-blue-100 text-blue-600',
            'Completed'   => 'bg-emerald-100 text-emerald-600',
            'Cancelled'   => 'bg-slate-100 text-slate-500',
            default       => 'bg-slate-100 text-slate-600',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'Pending'     => 'fa-regular fa-clock',
            'In Progress' => 'fa-solid fa-spinner',
            'Completed'   => 'fa-regular fa-circle-check',
            'Cancelled'   => 'fa-regular fa-circle-xmark',
            default       => 'fa-regular fa-circle',
        };
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'In Progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%")
                      ->orWhereHas('room', function ($q) use ($search) {
                          $q->where('room_number', 'like', "%{$search}%");
                      })
                      ->orWhereHas('tenant.user', function ($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($filters['priority'] ?? null, function ($q, $priority) {
                $q->where('priority', $priority);
            });
    }
}