<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'batch_type',
        'min_size',
        'max_size',
        'price_npr',
        'offer_label',
        'discount_type',
        'discount_value',
        'offer_starts_at',
        'offer_ends_at',
        'is_price_variable',
        'schedule_notes',
        'start_date',
        'end_date',
        'class_time',
        'class_link',
        'is_active',
    ];

    protected $casts = [
        'course_id' => 'integer',
        'min_size' => 'integer',
        'max_size' => 'integer',
        'price_npr' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'is_price_variable' => 'boolean',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'offer_starts_at' => 'date:Y-m-d',
        'offer_ends_at' => 'date:Y-m-d',
        'is_active' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
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
