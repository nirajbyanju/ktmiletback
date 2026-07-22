<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $fillable = [
        'key', 'name', 'category', 'group_label', 'trigger_label', 'automation',
        'is_enabled', 'subject', 'body', 'cta_text', 'cta_path', 'when_to_use',
        'placeholders', 'default_subject', 'default_body', 'default_cta_text',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'placeholders' => 'array',
    ];

    public static function byKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }
}
