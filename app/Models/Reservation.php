<?php
// app/Models/Reservation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $table = 'reservations';

    protected $fillable = [
        'reference_no',
        'reservation_number',
        'user_id',
        'room_id',
        'tenant_name',
        'tenant_initial',
        'avatar_bg',
        'avatar_color',
        'email',
        'phone',
        'desired_room',
        'room_bed_id',
        'check_in',
        'check_in_time',
        'check_out',
        'check_out_time',
        'has_companion',
        'total_charges',
        'reservation_fee',
        'payment_option',
        'payment_method',
        'remaining',
        'request_details',
        'admin_notes',
        'proof_of_payment',
        'status',
        'processed_by',
        'processed_at',
        'expires_at',
    ];

    protected $casts = [
        'check_in'        => 'date',
        'check_out'       => 'date',
        'processed_at'    => 'datetime',
        'expires_at'      => 'datetime',
        'total_charges'   => 'decimal:2',
        'reservation_fee' => 'decimal:2',
        'remaining'       => 'decimal:2',
        'has_companion'   => 'boolean',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    // ---- Relationships ----

    public function user(): BelongsTo       { return $this->belongsTo(User::class); }
    public function room(): BelongsTo       { return $this->belongsTo(Room::class); }
    public function processedBy(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }

    // ---- Accessors ----

    public function getStatusClassAttribute(): string
    {
        return match ($this->status) {
            'Pending'  => 'bg-amber-100 text-amber-700',
            'Approved' => 'bg-green-100 text-green-600',
            'Rejected' => 'bg-rose-100 text-rose-600',
            'Expired'  => 'bg-slate-100 text-slate-500',
            default    => 'bg-slate-100 text-slate-500',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'Pending'  => 'fa-regular fa-clock',
            'Approved' => 'fa-regular fa-circle-check',
            'Rejected' => 'fa-regular fa-circle-xmark',
            'Expired'  => 'fa-regular fa-clock',
            default    => 'fa-regular fa-circle',
        };
    }

    public function getFormattedTotalChargesAttribute(): string
    {
        return '₱' . number_format((float) $this->total_charges, 2);
    }

    public function getFormattedRemainingAttribute(): string
    {
        return '₱' . number_format((float) $this->remaining, 2);
    }

    public function getFormattedFeeAttribute(): string
    {
        return '₱' . number_format((float) $this->reservation_fee, 0);
    }

    public function getStayPeriodAttribute(): string
    {
        $in  = $this->check_in?->format('M j, Y')  ?? '—';
        $out = $this->check_out?->format('M j, Y') ?? 'Open-ended';
        return $in . ' – ' . $out;
    }

    public function getStayDurationAttribute(): ?int
    {
        if (! $this->check_in || ! $this->check_out) return null;
        return max(1, $this->check_in->diffInDays($this->check_out) + 1);
    }

    public function getFormattedCheckInAttribute(): string
    {
        $date = $this->check_in?->format('M j, Y') ?? '—';
        return $this->check_in_time ? $date . ' · ' . $this->check_in_time : $date;
    }

    public function getFormattedCheckOutAttribute(): string
    {
        if (! $this->check_out) return 'Open-ended';
        $date = $this->check_out->format('M j, Y');
        return $this->check_out_time ? $date . ' · ' . $this->check_out_time : $date;
    }

    public function getProofUrlAttribute(): ?string
    {
        return $this->proof_of_payment
            ? asset('storage/' . $this->proof_of_payment)
            : null;
    }

    // ---- Scopes ----

    public function scopePending($query)  { return $query->where('status', 'Pending'); }
    public function scopeApproved($query) { return $query->where('status', 'Approved'); }
    public function scopeRejected($query) { return $query->where('status', 'Rejected'); }
    public function scopeExpired($query)  { return $query->where('status', 'Expired'); }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('tenant_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('desired_room', 'like', "%{$search}%")
                        ->orWhere('reference_no', 'like', "%{$search}%")
                        ->orWhere('reservation_number', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            });
    }

    // ---- Helpers ----

    public static function generateReferenceNo(): string
    {
        $lastId = static::max('id') ?? 0;
        return '#RSV-' . ($lastId + 1);
    }

    public static function generateReservationNumber(): string
    {
        $lastId = static::max('id') ?? 0;
        $year = now()->format('Y');
        return 'RSV-' . $year . '-' . str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
    }

    public static function makeAvatar(string $name): array
    {
        $initial = strtoupper(substr($name, 0, 1));

        $palette = [
            'bg-teal-100'   => 'text-teal-600',
            'bg-sky-100'    => 'text-sky-600',
            'bg-green-100'  => 'text-green-600',
            'bg-purple-100' => 'text-purple-600',
            'bg-amber-100'  => 'text-amber-600',
        ];

        $keys  = array_keys($palette);
        $index = ord($initial) % count($keys);

        return [
            'initial' => $initial,
            'bg'      => $keys[$index],
            'color'   => $palette[$keys[$index]],
        ];
    }

    /**
     * Whether the reservation's remaining balance has been fully settled.
     */
    public function isRemainingSettled(): bool
    {
        return (float) $this->remaining <= 0.005;
    }

    /**
     * Whether the reservation has an outstanding remaining balance
     * that has NOT yet been invoiced.
     */
    public function hasUnbilledRemaining(): bool
    {
        if ((float) $this->remaining <= 0.005) {
            return false;
        }

        $billed = (float) \App\Models\Invoice::where('tenant_id', $this->user?->tenant?->id)
            ->where(function ($q) {
                $q->where('description', 'like', '%remaining%')
                  ->orWhere('description', 'like', '%' . $this->reference_no . '%');
            })
            ->sum('balance');

        return ($this->remaining - $billed) > 0.005;
    }

    public function toReactArray(): array
    {
        return [
            // NEW — needed by ReservationsModals.jsx so
            // /admin/reservations/{id}/approve hits the correct record.
            'id'              => $this->id,
            'key'             => $this->reference_no,
            'name'            => $this->tenant_name,
            'initial'         => $this->tenant_initial,
            'bg'              => $this->avatar_bg,
            'color'           => $this->avatar_color,
            'subCode'         => $this->reference_no,
            'resNumber'       => $this->reservation_number,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'desiredRoom'     => 'Desired Room: ' . $this->desired_room,
            'bedId'           => $this->room_bed_id ? 'Room/Bed ID: ' . $this->room_bed_id : 'Room/Bed ID: N/A',
            'room'            => $this->desired_room,
            'checkin'         => $this->check_in?->format('M j, Y') ?? '-',
            'checkout'        => $this->check_out?->format('M j, Y') ?? 'Open-ended',
            'stayPeriod'      => $this->stay_period,
            'stayDuration'    => $this->stay_duration,
            'formattedCheckIn'=> $this->formatted_check_in,
            'formattedCheckOut' => $this->formatted_check_out,
            'submitted'       => 'Submitted: ' . $this->created_at?->format('F j, Y'),
            'requestDetails'  => $this->request_details ?? '',
            'totalCharges'    => $this->formatted_total_charges,
            'paymentOption'   => $this->payment_option,
            'remaining'       => $this->formatted_remaining,
            'paymentMethod'   => $this->payment_method,
            'fee'             => $this->formatted_fee,
            'hasCompanion'    => $this->has_companion,
            'statusText'      => $this->status,
            'statusClass'     => $this->status_class,
            'statusIcon'      => $this->status_icon,
            'adminNotes'      => $this->admin_notes ?? '',
            'proofUrl'        => $this->proof_url,
        ];
    }
}