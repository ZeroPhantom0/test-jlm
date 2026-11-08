<?php
// app/Models/Message.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    protected $table = 'messages';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_role',
        'body',
        'attachments',
        'read_at',
        'deleted_by_sender',
        'deleted_by_receiver',
        'deleted_at',
        'deleted_for_everyone_at',
    ];

    protected $casts = [
        'attachments'             => 'array',
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

    public function isDeletedFor(string $role): bool
    {
        return $role === 'admin'
            ? (bool) $this->deleted_by_receiver
            : (bool) $this->deleted_by_sender;
    }

    public function isHiddenFrom(string $role): bool
    {
        if ($this->deleted_at !== null) {
            return true;
        }

        return $this->isDeletedFor($role);
    }

    public function isDeletedForEveryone(): bool
    {
        return $this->deleted_for_everyone_at !== null;
    }

    public function renderBody(): string
    {
        return $this->isDeletedForEveryone()
            ? 'This message was deleted.'
            : $this->body;
    }

    public function reactionSummary(): array
    {
        return $this->reactions
            ->groupBy('reaction')
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    /**
     * Serialize attachments with public URLs for the frontend.
     * The storage path stays internal — the client only sees
     * the public URL, name, mime and size.
     */
    public function attachmentsForClient(): array
    {
        $list = $this->attachments ?? [];

        if (! is_array($list) || empty($list)) {
            return [];
        }

        return array_map(function ($a) {
            $path = $a['path'] ?? null;
            $mime = $a['mime'] ?? 'application/octet-stream';

            return [
                'name'     => $a['name'] ?? 'file',
                'mime'     => $mime,
                'size'     => (int) ($a['size'] ?? 0),
                'url'      => $path ? '/storage/' . ltrim($path, '/') : null,
                'is_image' => str_starts_with($mime, 'image/'),
            ];
        }, $list);
    }
}
