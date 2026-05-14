<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionalService extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_name',
        'description',
        'is_add_on',
        'price_npr',
    ];

    protected $casts = [
        'is_add_on' => 'boolean',
        'price_npr' => 'decimal:2',
    ];
}
