<?php
// app/Models/PersonalInformation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalInformation extends Model
{
    /** Live: Admin → Tenants table + that tenant's dashboard refresh */
    protected static function booted(): void
    {
        static::saved(fn (self $m) => \App\Events\TenantsUpdated::dispatchOnce($m->user_id));
        static::deleted(fn (self $m) => \App\Events\TenantsUpdated::dispatchOnce($m->user_id));
    }

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