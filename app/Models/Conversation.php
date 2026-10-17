<?php
// app/Models/Conversation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $table = 'conversations';

    protected $fillable = [
        'tenant_id',
        'admin_id',
        'subject',
        'last_message_at',
        'deleted_by_tenant',
        'deleted_by_admin',
        'deleted_at',
    ];

    protected $casts = [
        'last_message_at'    => 'datetime',
        'deleted_at'         => 'datetime',
        'deleted_by_tenant'  => 'boolean',
        'deleted_by_admin'   => 'boolean',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * The most recent message in the conversation.
     * MUST be `HasOne` — `latestOfMany()` returns a single row.
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getUnreadForRoleAttribute(): int
    {
        return $this->messages()
            ->whereNull('read_at')
            ->when($this->tenant_id, fn ($q) => $q->where('sender_role', 'admin'))
            ->count();
    }

    // ============================================================
    // HELPERS
    // ============================================================

    /**
     * Whether the conversation is hidden from the given role.
     */
    public function isHiddenFrom(string $role): bool
    {
        if ($this->deleted_at !== null) {
            return true;
        }

        return $role === 'admin'
            ? (bool) $this->deleted_by_admin
            : (bool) $this->deleted_by_tenant;
    }

    public static function findOrCreateForTenant(Tenant $tenant, ?int $adminId, string $subject): self
    {
        $existing = static::where('tenant_id', $tenant->id)
            ->where('subject', $subject)
            ->whereNull('admin_id')
            ->latest()
            ->first();

        if ($existing) {
            if ($adminId && ! $existing->admin_id) {
                $existing->update(['admin_id' => $adminId]);
            }
            return $existing;
        }

        return static::create([
            'tenant_id'       => $tenant->id,
            'admin_id'        => $adminId,
            'subject'         => $subject,
            'last_message_at' => now(),
        ]);
    }
}