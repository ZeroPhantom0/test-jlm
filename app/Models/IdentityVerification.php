<?php
// app/Models/IdentityVerification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdentityVerification extends Model
{
    protected $fillable = [
        'user_id',
        'id_type',
        'id_front',
        'id_back',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}