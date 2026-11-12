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

    // Keep the public landing page's room list live (status, price, details)
    protected static function booted(): void
    {
        static::saved(fn () => \App\Events\PublicRoomsUpdated::dispatchOnce());
        static::deleted(fn () => \App\Events\PublicRoomsUpdated::dispatchOnce());
    }

    protected $fillable = [
        'room_number', 'floor', 'room_type', 'description', 'total_beds',
        'upper_deck', 'lower_deck', 'daily_rent', 'status', 'room_bed_id', 'tenant_name',
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

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    // ============================================================
    // HELPERS
    // ============================================================

    private function isValidBedId(?string $bedId): bool
    {
        return $bedId && preg_match('/^B\d+-[UL]$/', $bedId);
    }

    private function isReservedEligibleType(): bool
    {
        return in_array($this->room_type, ['Solo Room', 'Barkada Room'], true);
    }

    public function hasPartialPaymentReservation(): bool
    {
        if (! $this->isReservedEligibleType()) {
            return false;
        }

        return Reservation::where('room_id', $this->id)
            ->where('status', 'Approved')
            ->where('remaining', '>', 0.005)
            ->where(function ($q) {
                $q->whereNull('check_out')
                    ->orWhereDate('check_out', '>=', now()->toDateString());
            })
            ->exists();
    }

    // ============================================================
    // OCCUPANCY & BED SLOTS
    // ============================================================

    // ------------------------------------------------------------
    // "Who is in the room right now" — a booking only occupies the
    // room once its check-in moment has arrived. An approved booking
    // for a future date (e.g. Dec 25 while today is Nov 13) does NOT
    // make the room Occupied; it makes it Reserved (see syncStatus).
    // ------------------------------------------------------------

    /** Approved reservations of this room that haven't ended yet. */
    private function liveApprovedReservations()
    {
        return Reservation::where('room_id', $this->id)
            ->where('status', 'Approved')
            ->where(function ($q) {
                $q->whereNull('check_out')
                    ->orWhereDate('check_out', '>=', now()->toDateString());
            })
            ->get()
            ->reject(fn (Reservation $r) => $r->isStayOver())
            ->values();
    }

    /** Approved stays in progress right now (checked in, not checked out). */
    public function currentStays()
    {
        return $this->liveApprovedReservations()
            ->filter(fn (Reservation $r) => $r->hasStarted())
            ->values();
    }

    /** Approved bookings whose check-in is still ahead. */
    public function upcomingStays()
    {
        return $this->liveApprovedReservations()
            ->reject(fn (Reservation $r) => $r->hasStarted())
            ->sortBy(fn (Reservation $r) => $r->checkInAt())
            ->values();
    }

    /**
     * Active tenants physically in the room now: their approved stay has
     * started (tenants with no linked reservation — manual assignments —
     * count as present).
     */
    private function currentTenants()
    {
        return Tenant::where('room_id', $this->id)
            ->where('status', 'active')
            ->with('user', 'approvedReservation')
            ->get()
            ->filter(function (Tenant $t) {
                $stay = $t->approvedReservation;
                return ! $stay || ($stay->hasStarted() && ! $stay->isStayOver());
            })
            ->values();
    }

    /**
     * When the room (Solo / Barkada) or the first bed (Bedspace) is
     * expected to free up: the earliest check-out among the stays in
     * progress. Null when nobody is in the room or check-out is open-ended.
     */
    public function nextAvailableAt(): ?\Carbon\Carbon
    {
        return $this->currentStays()
            ->map(fn (Reservation $r) => $r->checkOutAt())
            ->filter()
            ->sort()
            ->first();
    }

    /**
     * What to tell visitors about availability:
     *  - Occupied → estimated availability = nearest check-out of the
     *               people in the room now (first to leave).
     *  - Reserved → when the upcoming booking starts.
     *
     * @return array{est_date: ?string, est_approx: ?string, next_booking: ?string}
     */
    public function availabilityInfo(): array
    {
        $info = ['est_date' => null, 'est_approx' => null, 'next_booking' => null];

        if ($this->status === 'Occupied') {
            $at = $this->nextAvailableAt();
            if ($at) {
                $info['est_date']   = $at->format('M j, Y · g:i A');
                $info['est_approx'] = $at->isToday()
                    ? 'Later today'
                    : 'In approx. ' . max(1, (int) ceil(now()->diffInHours($at) / 24)) . ' day'
                        . ((int) ceil(now()->diffInHours($at) / 24) > 1 ? 's' : '');
            } else {
                $info['est_approx'] = 'Open-ended stay';
            }
        }

        $next = $this->upcomingStays()->first();
        if ($next) {
            $in  = $next->checkInAt();
            $out = $next->checkOutAt();
            $info['next_booking'] = $in?->format('M j, Y')
                . ($out ? ' – ' . $out->format('M j, Y') : '');
        }

        return $info;
    }

    public function getBedSlotsAttribute(): array
    {
        if ($this->room_type === 'Solo Room') {
            $isOccupied = $this->currentTenants()->isNotEmpty() || $this->currentStays()->isNotEmpty();
            return [[
                'id' => 'SOLO',
                'label' => 'Whole Room',
                'status' => $isOccupied ? 'Occupied' : 'Available'
            ]];
        }

        $totalBeds = (int) $this->total_beds;
        if ($totalBeds <= 0) return [];

        $heldByTenants = [];
        foreach ($this->currentTenants() as $tenant) {
            $bedId = $tenant->approvedReservation?->room_bed_id;
            if ($this->isValidBedId($bedId)) {
                $heldByTenants[$bedId] = true;
            }
        }

        $heldByReservations = $this->currentStays()
            ->pluck('room_bed_id')
            ->filter(fn($id) => $this->isValidBedId($id))
            ->all();
        $heldByReservations = array_flip($heldByReservations);

        $slots = [];
        $slotIndex = 0;
        for ($i = 1; $i <= ceil($totalBeds / 2); $i++) {
            foreach (['U' => 'Upper', 'L' => 'Lower'] as $code => $label) {
                if ($slotIndex >= $totalBeds) break 2;

                $id = "B{$i}-{$code}";
                $isOccupied = isset($heldByTenants[$id]) || isset($heldByReservations[$id]);

                $slots[] = [
                    'id'     => $id,
                    'label'  => "Bed {$i} - {$label}",
                    'status' => $isOccupied ? 'Occupied' : 'Available',
                ];
                $slotIndex++;
            }
        }

        return $slots;
    }

    public function getOccupiedCountAttribute(): int
    {
        // Only stays that have actually started fill the room. Pending
        // requests and future bookings don't.
        if ($this->room_type === 'Solo Room') {
            return ($this->currentTenants()->isNotEmpty() || $this->currentStays()->isNotEmpty()) ? 1 : 0;
        }

        if ($this->room_type === 'Barkada Room') {
            $inUse = $this->currentTenants()->isNotEmpty() || $this->currentStays()->isNotEmpty();

            return $inUse ? (int) $this->total_beds : 0;
        }

        // Bedspace — count via the occupant list to avoid duplicate counting
        return $this->getValidOccupants()->count();
    }

    public function getAvailableSlotsAttribute(): int
    {
        if ($this->room_type === 'Solo Room') {
            return 1 - $this->occupied_count;
        }
        return max(0, (int) $this->total_beds - $this->occupied_count);
    }

    // ============================================================
    // OCCUPANCY LABEL (used by rooms.blade.php + modal)
    // ============================================================

    public function getOccupancyLabelAttribute(): string
    {
        $total = $this->room_type === 'Solo Room' ? 1 : (int) $this->total_beds;
        $occupied = (int) $this->occupied_count;

        return $occupied . ' / ' . $total;
    }

    public function getOccupancyPercentAttribute(): int
    {
        $total = $this->room_type === 'Solo Room' ? 1 : (int) $this->total_beds;
        if ($total <= 0) {
            return 0;
        }
        return (int) round(($this->occupied_count / $total) * 100);
    }

    // ============================================================
    // AUTO STATUS
    // ============================================================

        public function syncStatus(): void
    {
        $occupied = $this->occupied_count;
        $total = $this->room_type === 'Solo Room' ? 1 : $this->total_beds;

        $isFull = ($total > 0 && $occupied >= $total);

        // 1. Occupied only when the stays in progress RIGHT NOW fill it.
        $newStatus = $isFull ? 'Occupied' : 'Available';

        // 2. Reserved (Solo / Barkada): not occupied yet, but an approved
        //    booking is coming up — e.g. booked for Dec 25 while today is
        //    Nov 13. Other dates can still be booked (overlap is checked
        //    per date at booking time).
        if (! $isFull && $this->isReservedEligibleType() && $this->upcomingStays()->isNotEmpty()) {
            $newStatus = 'Reserved';
        }

        // 3. Update Tenant Name
        $names = $this->active_tenant_names;
        $newTenantName = empty($names) ? null : implode(', ', $names);

        if ($this->status !== $newStatus || $this->tenant_name !== $newTenantName) {
            $this->update([
                'status'      => $newStatus,
                'tenant_name' => $newTenantName,
            ]);
        }
    }

    // ============================================================
    // TENANT NAMES & OCCUPANTS
    // ============================================================

    private function getValidOccupants()
    {
        // If it's a Barkada room, we don't want individual bed assignments.
        if ($this->room_type === 'Barkada Room') {
            return collect();
        }

        $tenantOccupants = $this->currentTenants()
            ->filter(fn($t) => $this->isValidBedId($t->approvedReservation?->room_bed_id))
            ->map(fn($t) => [
                'name'   => $t->user->name ?? 'Unknown',
                'bed_id' => $t->approvedReservation->room_bed_id,
            ]);

        $reservationOccupants = $this->currentStays()
            ->filter(fn($r) => $this->isValidBedId($r->room_bed_id))
            ->map(fn($r) => [
                'name'   => $r->tenant_name,
                'bed_id' => $r->room_bed_id,
            ]);

        return $tenantOccupants->concat($reservationOccupants)
            ->unique('bed_id')
            ->values();
    }

    public function getActiveTenantNamesAttribute(): array
    {
        return $this->getValidOccupants()
            ->pluck('name')
            ->filter()
            ->values()
            ->toArray();
    }

    public function getBedConfigurationsAttribute(): array
    {
        if ($this->room_type === 'Solo Room') {
            $tenant = $this->tenants()->where('status', 'active')->with('user')->first();
            if ($tenant) {
                $name = $tenant->user?->name ?? 'Unknown';
                return [[
                    'name'    => $name,
                    'initial' => strtoupper(substr($name, 0, 1)),
                    'slot'    => 'SOLO',
                    'label'   => 'Whole Room',
                ]];
            }
            return [];
        }

        return $this->getValidOccupants()
            ->map(function ($occupant) {
                $slot = $occupant['bed_id'];
                $label = 'Unassigned';

                if (preg_match('/^B(\d+)-([UL])$/', $slot, $matches)) {
                    $bedNum = $matches[1];
                    $deck = $matches[2] === 'U' ? 'Upper Deck' : 'Lower Deck';
                    $label = "Bed {$bedNum} ({$deck})";
                }

                return [
                    'name'    => $occupant['name'],
                    'initial' => strtoupper(substr($occupant['name'] ?? '?', 0, 1)),
                    'slot'    => $slot,
                    'label'   => $label,
                ];
            })
            ->toArray();
    }

    public function getBarkadaOccupantsAttribute(): array
    {
        if ($this->room_type !== 'Barkada Room') {
            return [];
        }

        $reservation = \App\Models\Reservation::where('room_id', $this->id)
            ->whereIn('status', ['Approved', 'Pending'])
            ->where(function ($q) {
                $q->whereNull('check_out')
                    ->orWhereDate('check_out', '>=', now()->toDateString());
            })
            ->latest()
            ->first();

        if (!$reservation) {
            return [];
        }

        $occupants = [
            [
                'name'    => $reservation->tenant_name,
                'initial' => strtoupper(substr($reservation->tenant_name, 0, 1)),
                'label'   => 'Lead Guest',
            ]
        ];

        if ($reservation->request_details && preg_match('/Companions:\s*(.*)/i', $reservation->request_details, $matches)) {
            $names = array_filter(array_map('trim', explode(',', $matches[1])));
            foreach ($names as $name) {
                if ($name) {
                    $occupants[] = [
                        'name'    => $name,
                        'initial' => strtoupper(substr($name, 0, 1)),
                        'label'   => 'Companion',
                    ];
                }
            }
        }

        return $occupants;
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
            'id'                => $this->id,
            'room_number'       => $this->room_number,
            'floor'             => $this->floor,
            'room_type'         => $this->room_type,
            'description'       => $this->description,
            'total_beds'        => $this->total_beds,
            'upper_deck'        => $this->upper_deck,
            'lower_deck'        => $this->lower_deck,
            'daily_rent'        => (float) $this->daily_rent,
            'status'            => $this->status,
            'room_bed_id'       => $this->room_bed_id,
            'tenant_name'       => $this->tenant_name,
            'occupied_count'    => $this->occupied_count,
            'available_slots'   => $this->available_slots,
            'occupancy_label'   => $this->occupancy_label,
            'occupancy_percent' => $this->occupancy_percent,
            'bed_configurations'=> $this->bed_configurations,
            'barkada_occupants' => $this->barkada_occupants,
        ];
    }
}