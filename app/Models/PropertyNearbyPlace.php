<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyNearbyPlace extends Model
{
    protected $fillable = [
        'property_id',
        'name',
        'type',
        'distance',
        'distance_unit',
        'description'
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}