<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentStatus extends Model
{
    protected $fillable = ['key', 'label', 'color', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    // Convenience: keyed array  ['action_required' => 'Action Required', ...]
    public static function labelMap(): array
    {
        return static::orderBy('sort_order')->pluck('label', 'key')->all();
    }

    public static function keys(): array
    {
        return static::orderBy('sort_order')->pluck('key')->all();
    }
}
