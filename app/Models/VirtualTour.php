<?php

namespace App\Models;

use App\Models\TourScene;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VirtualTour extends Model
{
    protected $table = 'virtual_tours';

    protected $fillable = [
        'room_id',
        'title',
        'description',
        'status',
        'thumbnail',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function scenes(): HasMany
    {
        return $this->hasMany(TourScene::class, 'tour_id');
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getThumbnailUrlAttribute(): string
    {
        if (empty($this->thumbnail)) {
            return 'https://via.placeholder.com/220x128?text=360+Tour';
        }

        if (Str::startsWith($this->thumbnail, ['http://', 'https://'])) {
            return $this->thumbnail;
        }

        return asset('storage/tours/' . $this->thumbnail);
    }

    public function getHotspotsCountAttribute(): int
    {
        if (isset($this->attributes['hotspots_count'])) {
            return (int) $this->attributes['hotspots_count'];
        }

        return $this->scenes()->withCount('hotspots')->get()->sum('hotspots_count');
    }

    // ============================================================
    // HELPER METHODS FOR FRONTEND
    // ============================================================

    public function toEditArray(): array
    {
        return [
            'id' => $this->id,
            'room_id' => $this->room_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'thumbnail' => $this->thumbnail,
            'thumbnail_url' => $this->thumbnail_url,
            'room_number' => $this->room->room_number ?? '',
        ];
    }

    public function toViewerArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'thumbnail_url' => $this->thumbnail_url,
            'room_number' => $this->room->room_number ?? 'N/A',
            'scenes_count' => $this->scenes->count(),
            'created_at_formatted' => $this->created_at->format('M d, Y'),
        ];
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopePublished($query)
    {
        return $query->where('status', 'Published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'Draft');
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('title', 'like', "%{$search}%");
            })
            ->when($filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            });
    }
}