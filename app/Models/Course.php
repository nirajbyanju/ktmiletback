<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_name',
        'description',
        'best_for',
        'duration',
        'duration_type',
        'delivery_mode',
        'delivery',
        'support',
        'instruction',
        'schedule',
        'features',
        'is_status',
        'published_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'duration'     => 'integer',
        'support'      => 'array',
        'instruction'  => 'array',
        'schedule'     => 'array',
        'features'     => 'array',
        'is_status'    => 'integer',
        'published_at' => 'datetime',
        'created_by'   => 'integer',
        'updated_by'   => 'integer',
        'deleted_by'   => 'integer',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class);
    }
}
