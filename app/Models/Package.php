<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Catalog level 2: Course Type → Package → Batches.
 * Price lives here (per course type); batches under a package are time slots.
 */
class Package extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_id',
        'name',
        'description',
        'size_label',
        'schedule_notes',
        'duration_weeks',
        'price_npr',
        'is_flexible',
        'is_group',
        'is_featured',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'course_id' => 'integer',
        'duration_weeks' => 'integer',
        'price_npr' => 'decimal:2',
        'is_flexible' => 'boolean',
        'is_group' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function setSortOrderAttribute($value)
    {
        $this->attributes['sort_order'] = is_null($value) ? 0 : $value;
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }
}
