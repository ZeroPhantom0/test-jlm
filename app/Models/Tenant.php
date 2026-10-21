<?php
// app/Models/Tenant.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

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

    public function approvedReservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'approved_reservation_id');
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getInitialAttribute(): string
    {
        return strtoupper(substr($this->user->name ?? '?', 0, 1));
    }

    // ============================================================
    // STATUS HELPERS
    // ============================================================

    public static function isApproved(?int $userId = null): bool
    {
        $userId ??= Auth::id();

        if (! $userId) {
            return false;
        }

        return static::where('user_id', $userId)
            ->whereNotNull('approved_reservation_id')
            ->exists();
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

            'room'          => $this->room?->room_number
                ? 'Room ' . $this->room->room_number
                : '—',
            // Check-in / Check-out are the ONLY dates for this tenant.
            'check_in'      => $reservation?->check_in?->format('M d, Y')       ?? '—',
            'check_in_time' => $reservation?->check_in_time                     ?? '',
            'check_out'     => $reservation?->check_out?->format('M d, Y')      ?? 'Open-ended',
            'check_out_time' => $reservation?->check_out_time                    ?? '',
            'rent_rate'     => (float) $this->rent_rate,

            'address'          => $personal?->current_address ?? '—',
            'emergency_name'   => $personal?->emergency_name ?? '—',
            'emergency_number' => $personal?->emergency_number ?? '—',

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

    // ============================================================
    // TIME ACCESSORS (12-hour display)
    // ============================================================

    public function getCheckInTime12Attribute(): ?string
    {
        return $this->formatTime12($this->approvedReservation?->check_in_time);
    }

    public function getCheckOutTime12Attribute(): ?string
    {
        return $this->formatTime12($this->approvedReservation?->check_out_time);
    }

    private function formatTime12(?string $time): ?string
    {
        if (!$time) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($time)->format('g:i A');
        } catch (\Throwable $e) {
            return $time; // fall back to raw value if unparseable
        }
    }
}
