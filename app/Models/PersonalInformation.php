<?php
// app/Models/PersonalInformation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalInformation extends Model
{
    protected $table = 'personal_information';

    protected $fillable = [
        'user_id',
        'full_name',
        'email',
        'contact_number',
        'current_address',
        'emergency_name',
        'emergency_number',
        'profile_picture',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}