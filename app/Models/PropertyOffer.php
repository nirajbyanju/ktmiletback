<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyOffer extends Model {
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'property_offers';

    protected $fillable = [
        'property_id',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'is_active',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
