<?php
// app/Models/RoomChangeRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomChangeRequest extends Model
{
    protected $table = 'room_change_requests';

    protected $fillable = [
        'reference_no',
        'tenant_id',
        'current_room_id',
        'requested_room_id',
        'current_room',
        'requested_room',
        'availability',
        'reason',
        'admin_notes',
        'status',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function currentRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'current_room_id');
    }

    public function requestedRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'requested_room_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getStatusClassAttribute(): string
    {
        return match ($this->status) {
            'Pending'  => 'bg-yellow-100 text-amber-700',
            'Approved' => 'bg-emerald-100 text-emerald-600',
            'Rejected' => 'bg-rose-100 text-rose-600',
            default    => 'bg-slate-100 text-slate-500',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'Pending'  => 'fa-regular fa-clock',
            'Approved' => 'fa-regular fa-circle-check',
            'Rejected' => 'fa-regular fa-circle-xmark',
            default    => 'fa-regular fa-circle',
        };
    }

    public function getAvailabilityClassAttribute(): string
    {
        return match ($this->availability) {
            'AVAILABLE' => 'bg-emerald-100 text-emerald-600',
            'OCCUPIED'  => 'bg-rose-100 text-rose-600',
            'RESERVED'  => 'bg-indigo-100 text-indigo-700',
            default     => 'bg-slate-100 text-slate-500',
        };
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'Approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'Rejected');
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('reference_no', 'like', "%{$search}%")
                      ->orWhere('current_room', 'like', "%{$search}%")
                      ->orWhere('requested_room', 'like', "%{$search}%")
                      ->orWhereHas('tenant.user', function ($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            });
    }

    // ============================================================
    // HELPERS
    // ============================================================

    public static function generateReferenceNo(): string
    {
        $lastId = static::max('id') ?? 0;
        return '#RC-' . ($lastId + 1);
    }

    public function toReactArray(): array
    {
        return [
            'id'             => $this->id,
            'reference_no'   => $this->reference_no,
            'tenant'         => $this->tenant->user->name ?? 'Unknown',
            'status'         => $this->status,
            'current_room'   => $this->current_room,
            'requested_room' => $this->requested_room,
            'availability'   => $this->availability,
            'reason'         => $this->reason,
            'admin_notes'    => $this->admin_notes ?? '',
            'created_at'     => $this->created_at?->format('M d, Y'),
        ];
    }
}