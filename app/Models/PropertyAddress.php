<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyAddress extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'property_addresses';

    protected $fillable = [
        'property_id',
        'province_id',
        'district_id',
        'municipality_id',
        'ward_id',
        'area',
        'postal_code',
        'full_address',
        'latitude',
        'longitude',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
