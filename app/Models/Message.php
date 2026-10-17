<?php
// app/Models/Message.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $table = 'messages';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_role',
        'body',
        'read_at',
        'deleted_by_sender',
        'deleted_by_receiver',
        'deleted_at',
        'deleted_for_everyone_at',
    ];

    protected $casts = [
        'read_at'                 => 'datetime',
        'deleted_at'              => 'datetime',
        'deleted_for_everyone_at' => 'datetime',
        'deleted_by_sender'       => 'boolean',
        'deleted_by_receiver'     => 'boolean',
        'created_at'              => 'datetime',
        'updated_at'              => 'datetime',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    // ============================================================
    // HELPERS
    // ============================================================

    public function markRead(): void
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Whether the given role has deleted this message from their view.
     */
    public function isDeletedFor(string $role): bool
    {
        return $role === 'admin'
            ? (bool) $this->deleted_by_receiver
            : (bool) $this->deleted_by_sender;
    }

    /**
     * Whether the message should be hidden from the given role.
     */
    public function isHiddenFrom(string $role): bool
    {
        if ($this->deleted_at !== null) {
            return true;
        }

        return $this->isDeletedFor($role);
    }

    /**
     * True when BOTH sides removed it → show "This message was deleted".
     */
    public function isDeletedForEveryone(): bool
    {
        return $this->deleted_for_everyone_at !== null;
    }

    /**
     * Safe body to render — placeholder when deleted for everyone.
     */
    public function renderBody(): string
    {
        return $this->isDeletedForEveryone()
            ? 'This message was deleted.'
            : $this->body;
    }

    /**
     * Group reactions by emoji: ['❤️' => 3, '😂' => 1]
     */
    public function reactionSummary(): array
    {
        return $this->reactions
            ->groupBy('reaction')
            ->map(fn ($group) => $group->count())
            ->toArray();
    }
}