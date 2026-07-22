<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    protected $fillable = [
        'user_id', 'recipient', 'template_key', 'subject', 'body_html',
        'related_type', 'related_id', 'status', 'error',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
