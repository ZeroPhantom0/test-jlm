<?php
// app/Models/ChatbotCategory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotCategory extends Model
{
    protected $fillable = [
        'slug', 'name', 'icon', 'welcome_message', 'button_label',
        'button_url', 'keywords', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function faqs(): HasMany
    {
        return $this->hasMany(ChatbotFaq::class)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
