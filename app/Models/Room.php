<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_number',
        'floor',
        'room_type',
        'description',
        'total_beds',
        'upper_deck',
        'lower_deck',
        'daily_rent',
        'status',
        'room_bed_id',
        'tenant_name',
    ];

    protected $casts = [
        'daily_rent' => 'decimal:2',
        'floor'      => 'integer',
        'total_beds' => 'integer',
        'upper_deck' => 'integer',
        'lower_deck' => 'integer',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function virtualTour(): HasOne
    {
        return $this->hasOne(VirtualTour::class);
    }

    /**
     * All tenants (users) currently assigned to this room.
     * This is the single source of truth for occupancy.
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    // ============================================================
    // OCCUPANCY (real — based on tenants.count)
    // ============================================================

    /**
     * Number of tenants currently assigned to this room.
     * Uses the eager-loaded `tenants_count` when available,
     * otherwise hits the DB.
     */
    public function getOccupiedCountAttribute(): int
    {
        if (array_key_exists('tenants_count', $this->attributes)) {
            return (int) $this->attributes['tenants_count'];
        }

        return (int) $this->tenants()->count();
    }

    /**
     * Remaining slots = total_beds - occupied.
     */
    public function getAvailableSlotsAttribute(): int
    {
        return max(0, (int) $this->total_beds - $this->occupied_count);
    }

    /**
     * "2/3" style label — occupied / total.
     */
    public function getOccupancyLabelAttribute(): string
    {
        return $this->occupied_count . '/' . $this->total_beds;
    }

    /**
     * 0–100 — percentage full.
     */
    public function getOccupancyPercentAttribute(): int
    {
        if ($this->total_beds <= 0) {
            return 0;
        }

        return (int) round(($this->occupied_count / $this->total_beds) * 100);
    }

    // ============================================================
    // STATUS HELPERS
    // ============================================================

    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'Occupied'  => 'fa-check',
            'Reserved'  => 'fa-clock',
            default     => 'fa-house',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'Occupied' => 'bg-green-100 text-green-600',
            'Reserved' => 'bg-indigo-100 text-indigo-700',
            default    => 'bg-slate-100 text-slate-600 border border-slate-200',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match (true) {
            str_contains($this->room_type, 'Solo')     => 'bg-blue-100 text-blue-700',
            str_contains($this->room_type, 'Bedspace') => 'bg-green-100 text-green-700',
            str_contains($this->room_type, 'Barkada')  => 'bg-purple-100 text-purple-700',
            default                                    => 'bg-slate-100 text-slate-700',
        };
    }

    // ============================================================
    // SERIALIZATION FOR THE REACT MODALS
    // ============================================================

    public function toRowArray(): array
    {
        return [
            'id'               => $this->id,
            'room_number'      => $this->room_number,
            'floor'            => $this->floor,
            'room_type'        => $this->room_type,
            'description'      => $this->description,
            'total_beds'       => $this->total_beds,
            'upper_deck'       => $this->upper_deck,
            'lower_deck'       => $this->lower_deck,
            'daily_rent'       => (float) $this->daily_rent,
            'status'           => $this->status,
            'room_bed_id'      => $this->room_bed_id,
            'tenant_name'      => $this->tenant_name,

            // Real occupancy values
            'occupied_count'   => $this->occupied_count,
            'available_slots'  => $this->available_slots,
            'occupancy_label'  => $this->occupancy_label,
            'occupancy_percent'=> $this->occupancy_percent,
        ];
    }
}