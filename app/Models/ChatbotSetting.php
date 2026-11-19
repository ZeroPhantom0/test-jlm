<?php
// app/Models/ChatbotSetting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /** All settings as [key => value]. */
    public static function map(): array
    {
        return static::pluck('value', 'key')->all();
    }
}
