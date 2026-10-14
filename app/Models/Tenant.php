<?php
// app/Models/Tenant.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'user_id',
        'room_id',
        'move_in_date',
        'move_in_time',
        'move_out_date',
        'move_out_time',
        'rent_rate',
        'status',
    ];

    protected $casts = [
        'move_in_date'   => 'date',
        'move_out_date'  => 'date',
        'rent_rate'      => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Initial for the avatar circle
    public function getInitialAttribute(): string
    {
        return strtoupper(substr($this->user->name ?? '?', 0, 1));
    }
}