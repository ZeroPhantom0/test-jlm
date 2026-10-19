<?php
// app/Models/Tenant.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tenant extends Model
{
    protected $fillable = [
        'user_id',
        'room_id',
        'approved_reservation_id',
        'rent_rate',
        'status',
    ];

    protected $casts = [
        'rent_rate' => 'decimal:2',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * The reservation that made this user a tenant.
     * ALL lease dates live here — tenants never duplicate them.
     */
    public function approvedReservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'approved_reservation_id');
    }

    // ============================================================
    // ACCESSORS — read the dates FROM THE RESERVATION
    // ============================================================

    public function getInitialAttribute(): string
    {
        return strtoupper(substr($this->user->name ?? '?', 0, 1));
    }

    /** Date the tenant moved in (= reservation.check_in). */
    public function getMoveInAttribute()
    {
        return $this->approvedReservation?->check_in;
    }

    /** Time the tenant moved in (= reservation.check_in_time). */
    public function getMoveInTimeAttribute(): ?string
    {
        return $this->approvedReservation?->check_in_time;
    }

    /** Date the tenant is set to move out (= reservation.check_out). May be null. */
    public function getMoveOutAttribute()
    {
        return $this->approvedReservation?->check_out;
    }

    /** Time the tenant is set to move out (= reservation.check_out_time). */
    public function getMoveOutTimeAttribute(): ?string
    {
        return $this->approvedReservation?->check_out_time;
    }

    // ============================================================
    // PAYLOAD FOR REACT MODALS
    // ============================================================

    public function toReactArray(): array
    {
        $user       = $this->user;
        $personal   = $user?->personalInformation;
        $identity   = $user?->identityVerification;
        $reservation = $this->approvedReservation;

        return [
            'id'      => $this->id,
            'name'    => $user->name ?? 'Unknown',
            'initial' => $this->initial,
            'email'   => $user->email ?? '—',
            'phone'   => $user->phone ?? '—',
            'status'  => $this->status,

            // Lease dates — sourced from the reservation
            'room'          => $this->room?->room_number
                                    ? 'Room ' . $this->room->room_number
                                    : '—',
            'move_in'       => $reservation?->check_in?->format('M d, Y')       ?? '—',
            'move_in_time'  => $reservation?->check_in_time                     ?? '',
            'move_out'      => $reservation?->check_out?->format('M d, Y')      ?? 'Open-ended',
            'move_out_time' => $reservation?->check_out_time                    ?? '',
            'rent_rate'     => (float) $this->rent_rate,

            // Personal info
            'address'          => $personal?->current_address ?? '—',
            'emergency_name'   => $personal?->emergency_name ?? '—',
            'emergency_number' => $personal?->emergency_number ?? '—',

            // Identity verification (Valid ID)
            'id_type'        => $identity?->id_type ?? null,
            'id_status'      => $identity?->status ?? null,
            'id_uploaded_at' => $identity?->created_at?->format('M d, Y') ?? null,
            'id_front_url'   => $identity?->id_front
                                    ? asset('storage/' . $identity->id_front)
                                    : null,
            'id_back_url'    => $identity?->id_back
                                    ? asset('storage/' . $identity->id_back)
                                    : null,
        ];
    }
}