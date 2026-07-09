<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoRequest extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'course_name',
        'course_id',
        'country',
        'preferred_at',
        'status',
        'zoom_url',
        'scheduled_at',
        'admin_notes',
        'teacher_id',
        'read_at',
    ];

    const STATUSES = ['pending', 'approved', 'rejected'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'read_at'      => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
