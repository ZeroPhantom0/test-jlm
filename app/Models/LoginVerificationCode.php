<?php
// app/Models/LoginVerificationCode.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginVerificationCode extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'token',
        'expires_at',
        'verified_at',
        'ip_address',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return $this->verified_at === null && $this->expires_at->isFuture();
    }

    public static function generateFor(User $user): self
    {
        // Invalidate previous codes
        static::where('user_id', $user->id)
            ->whereNull('verified_at')
            ->update(['expires_at' => now()]);

        return static::create([
            'user_id'    => $user->id,
            'code'       => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'token'      => bin2hex(random_bytes(32)),
            'expires_at' => now()->addMinutes(10),
            'ip_address' => request()->ip(),
        ]);
    }
}