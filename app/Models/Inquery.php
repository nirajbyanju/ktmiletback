<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Data\PropertyType;

class Inquery extends Model
{
    use HasFactory;
    protected $table = 'inquiries';
    protected $fillable = [
        'property_id',
        'from',
        'inquiry_type_id',
        'property_type_id',
        'name',
        'email',
        'phone',
        'location',
        'budget',
        'message',
        'description',
        'response_type_id',
        'reason',
      
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }


    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class);
    }

    public function inquiryFollowup(): HasMany
    {
        return $this->hasMany(InqueryFollowup::class, 'inquiry_id');
    }

    public function latestFollowup(): HasOne
    {
        return $this->hasOne(InqueryFollowup::class, 'inquiry_id')->latestOfMany();
    }
}
