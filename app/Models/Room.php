<?php
// app/Models/Room.php

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

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    // ============================================================
    // OCCUPANCY
    // ============================================================

    public function getOccupiedCountAttribute(): int
    {
        if (array_key_exists('tenants_count', $this->attributes)) {
            return (int) $this->attributes['tenants_count'];
        }

        return (int) $this->tenants()->where('status', 'active')->count();
    }

    public function getAvailableSlotsAttribute(): int
    {
        return max(0, (int) $this->total_beds - $this->occupied_count);
    }

    public function getOccupancyLabelAttribute(): string
    {
        return $this->occupied_count . '/' . $this->total_beds;
    }

    public function getOccupancyPercentAttribute(): int
    {
        if ($this->total_beds <= 0) {
            return 0;
        }

        return (int) round(($this->occupied_count / $this->total_beds) * 100);
    }

    // ============================================================
    // TENANT NAME FORMATTING
    // ============================================================

    public function getActiveTenantNamesAttribute(): array
    {
        return $this->tenants()
            ->where('status', 'active')
            ->with('user')
            ->get()
            ->pluck('user.name')
            ->filter()
            ->values()
            ->toArray();
    }

    public function getTenantDisplayAttribute(): ?string
    {
        $names = $this->active_tenant_names;
        
        if (empty($names)) {
            return null;
        }

        if (count($names) === 1) {
            return $names[0];
        }

        return $names[0] . ', ' . substr($names[1], 0, 7) . '...';
    }

    public function getTenantTooltipAttribute(): string
    {
        return implode(', ', $this->active_tenant_names);
    }

    /**
     * Parse bed slots for active tenants.
     * Maps "B1-U" to "Bed 1 - Upper Deck"
     */
    public function getBedConfigurationsAttribute(): array
    {
        if ($this->room_type === 'Solo Room') {
            return [];
        }

        return $this->tenants()
            ->where('status', 'active')
            ->with('user', 'approvedReservation')
            ->get()
            ->map(function ($tenant) {
                $slot = $tenant->approvedReservation?->room_bed_id ?? 'Unassigned';
                $label = 'Unassigned';

                if ($slot !== 'Unassigned' && preg_match('/^B(\d+)-([UL])$/', $slot, $matches)) {
                    $bedNum = $matches[1];
                    $deck = $matches[2] === 'U' ? 'Upper Deck' : 'Lower Deck';
                    $label = "Bed {$bedNum} ({$deck})";
                } elseif ($slot !== 'Unassigned') {
                    $label = $slot; // Fallback for custom IDs
                }

                return [
                    'name'   => $tenant->user->name ?? 'Unknown',
                    'initial'=> strtoupper(substr($tenant->user->name ?? '?', 0, 1)),
                    'slot'   => $slot,
                    'label'  => $label,
                ];
            })
            ->toArray();
    }

    // ============================================================
    // AUTO STATUS (STRICT)
    // ============================================================

    public function syncStatus(): void
    {
        $names    = $this->active_tenant_names;
        $occupied = count($names);

        // If the room is completely full (or over capacity), it is Occupied.
        // Otherwise, it is Available.
        $isFull = ($this->total_beds > 0 && $occupied >= $this->total_beds);
        
        $newStatus = $isFull ? 'Occupied' : 'Available';

        $newTenantName = empty($names) ? null : implode(', ', $names);

        if ($this->status !== $newStatus || $this->tenant_name !== $newTenantName) {
            $this->update([
                'status'      => $newStatus,
                'tenant_name' => $newTenantName,
            ]);
        }
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
            'Occupied' => 'bg-rose-100 text-rose-600',
            'Reserved' => 'bg-indigo-100 text-indigo-700',
            default    => 'bg-emerald-100 text-emerald-600',
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
    // SERIALIZATION
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
            'occupied_count'   => $this->occupied_count,
            'available_slots'  => $this->available_slots,
            'occupancy_label'  => $this->occupancy_label,
            'occupancy_percent'=> $this->occupancy_percent,
            'bed_configurations' => $this->bed_configurations, // <-- Added
        ];
    }
}