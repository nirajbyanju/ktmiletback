<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Admin-editable FAQ entries shown on the public /faq page. */
class Faq extends Model
{
    protected $fillable = ['group_title', 'question', 'answer', 'sort_order', 'is_active'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}
