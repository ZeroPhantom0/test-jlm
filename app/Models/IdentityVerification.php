<?php
// app/Models/IdentityVerification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdentityVerification extends Model
{
    /** Live: Admin → Tenants table + that tenant's dashboard refresh */
    protected static function booted(): void
    {
        static::saved(fn (self $m) => \App\Events\TenantsUpdated::dispatchOnce($m->user_id));
        static::deleted(fn (self $m) => \App\Events\TenantsUpdated::dispatchOnce($m->user_id));
    }

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