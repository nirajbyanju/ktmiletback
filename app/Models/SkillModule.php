<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillModule extends Model
{
    use HasFactory;

    protected $table = 'skills_modules';

    protected $fillable = [
        'skill_name',
        'topics_covered',
        'feedback_included',
    ];

    protected $casts = [
        'feedback_included' => 'boolean',
    ];
}
