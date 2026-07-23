<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DemoRequest extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'course_name',
        'course_id',
        'batch_id',
        'country',
        'preferred_at',
        'status',
        'zoom_url',
        'scheduled_at',
        'teacher',
        'admin_notes',
        'read_at',
        'outcome_email_sent_at',
    ];

    const STATUSES = ['pending', 'approved', 'rejected'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'read_at' => 'datetime',
        'outcome_email_sent_at' => 'datetime',
        'batch_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
