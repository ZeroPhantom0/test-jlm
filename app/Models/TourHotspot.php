<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourHotspot extends Model
{
    protected $table = 'tour_hotspots';

    /**
     * The table only has `created_at`, not `updated_at`.
     * Tell Eloquent not to touch a column that doesn't exist.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'scene_id',
        'type',
        'tooltip',
        'pitch',
        'yaw',
        'target_scene_id',
        'content_text',
        'media_url',
        'nav_audio',
        'description',
        'icon',
        'voice_assistant',
    ];

    protected $casts = [
        'pitch' => 'decimal:6',
        'yaw'   => 'decimal:6',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function scene(): BelongsTo
    {
        return $this->belongsTo(TourScene::class, 'scene_id');
    }

    public function targetScene(): BelongsTo
    {
        return $this->belongsTo(TourScene::class, 'target_scene_id');
    }
}