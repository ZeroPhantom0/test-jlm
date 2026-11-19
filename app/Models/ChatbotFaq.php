<?php
// app/Models/ChatbotFaq.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotFaq extends Model
{
    protected $fillable = [
        'chatbot_category_id', 'question', 'answer', 'keywords',
        'sort_order', 'is_active', 'hits',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
        'hits'       => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ChatbotCategory::class, 'chatbot_category_id');
    }

    /** Keywords as a clean, lower-cased array. */
    public function keywordList(): array
    {
        return array_values(array_filter(array_map(
            fn ($k) => mb_strtolower(trim($k)),
            explode(',', (string) $this->keywords)
        )));
    }
}
