<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyImages extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'property_images';

    protected $fillable = [
        'property_id',
        'image_url',
        'image_type',
        'is_featured',
        'sort_order',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    // Replace image_url output
    public function getImageUrlAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }
}