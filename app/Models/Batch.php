<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Batch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_id',
        'batch_type',
        'best_for',
        'size_label',
        'is_featured',
        'min_size',
        'max_size',
        'price_npr',
        'schedule_notes',
        'start_date',
        'end_date',
        'class_status',
        'class_time',
        'class_link',
        'is_active',
        'teacher_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $appends = ['class_status'];

    protected $casts = [
        'course_id'         => 'integer',
        'min_size'          => 'integer',
        'max_size'          => 'integer',
        'price_npr'         => 'decimal:2',
        'start_date'        => 'date:Y-m-d',
        'end_date'          => 'date:Y-m-d',
        'is_active'         => 'boolean',
        'is_featured'       => 'boolean',
        'teacher_id'        => 'integer',
        'created_by'        => 'integer',
        'updated_by'        => 'integer',
        'deleted_by'        => 'integer',
    ];

    public function getClassStatusAttribute(): string
    {
        // Return admin-set override if present
        $stored = $this->attributes['class_status'] ?? null;
        if ($stored) return $stored;

        // Otherwise auto-compute from dates
        $today = now()->toDateString();
        $start = $this->getRawOriginal('start_date');
        $end   = $this->getRawOriginal('end_date');

        if (!$start || $today < $start) return 'upcoming';
        if ($end && $today > $end)      return 'completed';
        return 'in_progress';
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
