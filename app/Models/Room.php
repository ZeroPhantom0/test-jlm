<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    // Helpers used in views
    public function getOccupancyLabelAttribute(): string
    {
        return ($this->total_beds - $this->available_slots) . '/' . $this->total_beds;
    }

    public function getAvailableSlotsAttribute(): int
    {
        // You can compute from bookings later; for now derive from status
        return $this->status === 'Occupied' ? 0 : $this->total_beds;
    }

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
}