<?php
// app/Models/Announcement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $table = 'announcements';

    protected $fillable = [
        'title',
        'content',
        'priority',
        'is_pinned',
        'is_published',
        'created_by',
        'published_at',
    ];

    protected $casts = [
        'is_pinned'    => 'boolean',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getPriorityClassAttribute(): string
    {
        return match ($this->priority) {
            'Urgent'    => 'bg-rose-100 text-rose-600',
            'Important' => 'bg-orange-100 text-orange-600',
            'Normal'    => 'bg-slate-100 text-slate-600',
            default     => 'bg-slate-100 text-slate-600',
        };
    }

    public function getPriorityIconAttribute(): string
    {
        return match ($this->priority) {
            'Urgent'    => 'fa-solid fa-exclamation',
            'Important' => 'fa-solid fa-exclamation',
            'Normal'    => 'fa-solid fa-circle-info',
            default     => 'fa-solid fa-circle-info',
        };
    }

    public function getBoxClassAttribute(): string
    {
        // Border color of the announcement card
        if ($this->is_pinned) {
            return 'border-[1.5px] border-amber-300';
        }
        return 'border border-slate-200';
    }

    public function getIconBgAttribute(): string
    {
        return match ($this->priority) {
            'Urgent'    => 'bg-rose-100 text-rose-600',
            'Important' => 'bg-yellow-100 text-amber-600',
            'Normal'    => 'bg-slate-100 text-slate-600',
            default     => 'bg-slate-100 text-slate-600',
        };
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOfPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->when($filters['priority'] ?? null, function ($q, $priority) {
                $q->where('priority', $priority);
            });
    }

    // ============================================================
    // HELPERS
    // ============================================================

    public function toReactArray(): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'content'     => $this->content,
            'priority'    => $this->priority,
            'is_pinned'   => $this->is_pinned,
            'is_published'=> $this->is_published,
            'author'      => $this->author->name ?? 'Unknown',
            'created_at'  => $this->created_at?->format('M d, Y'),
        ];
    }
}