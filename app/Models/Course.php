<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'duration_weeks',
        'total_hours',
        'delivery_mode',
        'instruction_lang',
        'skills',
    ];

    protected $casts = [
        'duration_weeks' => 'integer',
        'total_hours' => 'integer',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }
}
