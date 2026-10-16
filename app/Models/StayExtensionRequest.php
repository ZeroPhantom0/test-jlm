<?php
// app/Models/StayExtensionRequest.php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StayExtensionRequest extends Model
{
    protected $table = 'stay_extension_requests';

    protected $fillable = [
        'reference_no',
        'tenant_id',
        'room_id',
        'current_lease_end',
        'requested_lease_end',
        'extension_days',
        'reason',
        'admin_notes',
        'status',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'current_lease_end'   => 'date',
        'requested_lease_end' => 'date',
        'extension_days'      => 'integer',
        'processed_at'        => 'datetime',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
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

    public function getExtensionLabelAttribute(): string
    {
        return '+' . $this->extension_days . ' ' . ($this->extension_days === 1 ? 'day' : 'days');
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
                      ->orWhereHas('tenant.user', function ($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('room', function ($q) use ($search) {
                          $q->where('room_number', 'like', "%{$search}%");
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
        return '#SE-' . ($lastId + 1);
    }

    public static function calculateExtensionDays(string $currentEnd, string $requestedEnd): int
    {
        try {
            return Carbon::parse($currentEnd)->diffInDays(Carbon::parse($requestedEnd));
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function toReactArray(): array
    {
        return [
            'id'             => $this->id,
            'reference_no'   => ($this->room?->room_number ? 'Room ' . $this->room->room_number . ' · ' : '') . $this->reference_no,
            'tenant'         => $this->tenant->user->name ?? 'Unknown',
            'status'         => $this->status,
            'current_end'    => $this->current_lease_end?->format('M d, Y'),
            'requested_end'  => $this->requested_lease_end?->format('M d, Y'),
            'extension_days' => $this->extension_label . ' extension',
            'reason'         => $this->reason,
            'admin_notes'    => $this->admin_notes ?? '',
            'created_at'     => $this->created_at?->format('M d, Y'),
        ];
    }
}