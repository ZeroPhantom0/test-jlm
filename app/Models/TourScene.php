<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TourScene extends Model
{
    protected $table = 'tour_scenes';

    /**
     * The table only has `created_at`, not `updated_at`.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'tour_id',
        'title',
        'description',
        'image_path',
        'is_starting_scene',
        'sort_order',
        'default_yaw',
        'default_pitch',
        'default_fov',
        'auto_audio_text',
    ];

    protected $casts = [
        'is_starting_scene' => 'boolean',
        'sort_order'        => 'integer',
        'default_yaw'       => 'decimal:6',
        'default_pitch'     => 'decimal:6',
        'default_fov'       => 'decimal:6',
        'created_at'        => 'datetime',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function tour(): BelongsTo
    {
        return $this->belongsTo(VirtualTour::class, 'tour_id');
    }

    public function hotspots(): HasMany
    {
        return $this->hasMany(TourHotspot::class, 'scene_id');
    }

    public function targetScene(): BelongsTo
    {
        return $this->belongsTo(TourScene::class, 'target_scene_id');
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getImageUrlAttribute(): string
    {
        if (empty($this->image_path)) {
            return 'https://via.placeholder.com/800x400?text=360+Scene';
        }

        return asset('storage/scenes/' . $this->image_path);
    }
}