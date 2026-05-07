<?php
// app/Data/Property.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Data\PropertyType;
use App\Models\Data\PropertyStatus;
use App\Models\Data\ListingType;
use App\Models\Data\RoadType;
use App\Models\Data\RoadCondition;
use App\Models\Data\WaterSource;
use App\Models\Data\SewageType;
use App\Models\Data\Unit;
use App\Models\Data\PropertyFace;
use App\Models\User;
use App\Auditable;
use App\Models\Data\PropertyCategory;
use App\Models\Data\Amenities;


class Property extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'properties';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'property_code',
        'title',
        'slug',
        'tags',
        'description',
        'land_area',
        'land_unit_id',
        'property_face_id',
        'property_type_id',
        'listing_type_id',
        'video_url',
        'property_category_id',
        'length',
        'height',
        'measure_unit_id',
        'is_road_accessible',
        'road_type_id',
        'road_condition_id',
        'road_width',
        'base_price',
        'advertise_price',
        'currency',
        'is_featured',
        'is_negotiable',
        'banking_available',
        'has_electricity',
        'water_source_id',
        'sewage_type_id',
        'views_count',
        'likes_count',
        'seo_title',
        'seo_description',
        'property_status_id',
        'nearby_places',
        'created_by',
        'updated_by',
        'verified_by',
        'verified_at',
        'status',
        'is_status'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'land_area' => 'string',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_road_accessible' => 'boolean',
        'base_price' => 'decimal:2',
        'advertise_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_negotiable' => 'boolean',
        'banking_available' => 'boolean',
        'has_electricity' => 'boolean',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'currency' => 'NPR',
        'views_count' => 0,
        'likes_count' => 0,
        'is_featured' => false,
        'is_negotiable' => false,
        'banking_available' => false,
        'has_electricity' => false,
        'is_road_accessible' => true,
        'property_status_id' => 1,
        'status' => 'draft'
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Generate unique property code.
     */
    public function nearbyPlaces()
    {
        return $this->hasMany(PropertyNearbyPlace::class, 'property_id');
    }
    public static function generatePropertyCode(): string
    {
        $year = date('Y');
        $month = date('m');
        $lastProperty = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastProperty) {
            $lastNumber = intval(substr($lastProperty->property_code, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "PROP-{$year}{$month}-{$newNumber}";
    }




    // ========== Relationships ==========

    /**
     * Get the land unit.
     */
    public function landUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'land_unit_id');
    }

    /**
     * Get the property face.
     */
    public function propertyFace(): BelongsTo
    {
        return $this->belongsTo(PropertyFace::class, 'property_face_id');
    }

    /**
     * Get the property type.
     */
    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    /**
     * Get the listing type.
     */
    public function listingType(): BelongsTo
    {
        return $this->belongsTo(ListingType::class, 'listing_type_id');
    }

    /**
     * Get the measure unit.
     */
    public function measureUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'measure_unit_id');
    }

    /**
     * Get the road type.
     */
    public function roadType(): BelongsTo
    {
        return $this->belongsTo(RoadType::class, 'road_type_id');
    }

    /**
     * Get the road condition.
     */
    public function roadCondition(): BelongsTo
    {
        return $this->belongsTo(RoadCondition::class, 'road_condition_id');
    }

    /**
     * Get the water source.
     */
    public function waterSource(): BelongsTo
    {
        return $this->belongsTo(WaterSource::class, 'water_source_id');
    }

    /**
     * Get the sewage type.
     */
    public function sewageType(): BelongsTo
    {
        return $this->belongsTo(SewageType::class, 'sewage_type_id');
    }

    /**
     * Get the property status.
     */
    public function propertyStatus(): BelongsTo
    {
        return $this->belongsTo(PropertyStatus::class, 'property_status_id');
    }

    /**
     * Get the house details.
     */
    public function houseDetails(): HasOne
    {
        return $this->hasOne(HouseDetail::class, 'property_id');
    }

    public function images()
    {
        return $this->hasMany(PropertyImages::class, 'property_id');
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(
            Amenities::class,
            'property_amenities',
            'property_id',
            'amenity_id'
        );
    }

    public function amenities(): BelongsToMany
    {
        return $this->features();
    }

    public function address()
    {
        return $this->hasOne(PropertyAddress::class);
    }

    /**
     * Get the user who created this property.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who updated this property.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who verified this property.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ========== Scopes ==========

    /**
     * Scope a query to only include featured properties.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include negotiable properties.
     */
    public function scopeNegotiable($query)
    {
        return $query->where('is_negotiable', true);
    }

    /**
     * Scope a query to only include properties with banking available.
     */
    public function scopeBankingAvailable($query)
    {
        return $query->where('banking_available', true);
    }

    /**
     * Scope a query to only include verified properties.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * Scope a query to only include unverified properties.
     */
    public function scopeUnverified($query)
    {
        return $query->whereNull('verified_at');
    }

    /**
     * Scope a query to only include properties with specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include published properties.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to only include properties within price range.
     */
    public function scopePriceRange($query, $min, $max)
    {
        return $query->whereBetween('advertise_price', [$min, $max]);
    }

    /**
     * Scope a query to only include properties within area range.
     */
    public function scopeAreaRange($query, $min, $max)
    {
        return $query->whereBetween('land_area', [$min, $max]);
    }

    /**
     * Scope a query to find nearby properties by distance.
     */
    public function scopeNearby($query, $latitude, $longitude, $distance = 10)
    {
        $haversine = "(6371 * acos(cos(radians($latitude)) 
                      * cos(radians(latitude)) 
                      * cos(radians(longitude) - radians($longitude)) 
                      + sin(radians($latitude)) 
                      * sin(radians(latitude))))";

        return $query->select('*')
            ->selectRaw("{$haversine} AS distance")
            ->whereRaw("{$haversine} < ?", [$distance])
            ->orderBy('distance');
    }

    // ========== Accessors & Mutators ==========

    /**
     * Get the formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->advertise_price, 2);
    }

    /**
     * Get the formatted land area.
     */
    public function getFormattedLandAreaAttribute(): string
    {
        if (!$this->land_area) {
            return 'N/A';
        }

        $unit = $this->landUnit->symbol ?? 'sq ft';
        return number_format($this->land_area, 2) . ' ' . $unit;
    }

    /**
     * Get the full address (if you add address fields later).
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [];
        if ($this->city) $parts[] = $this->city;
        if ($this->state) $parts[] = $this->state;
        if ($this->country) $parts[] = $this->country;

        return implode(', ', $parts);
    }

    /**
     * Get the verification status.
     */
    public function getIsVerifiedAttribute(): bool
    {
        return !is_null($this->verified_at);
    }

    /**
     * Get the property age.
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->houseDetails || !$this->houseDetails->year_built) {
            return null;
        }

        return date('Y') - $this->houseDetails->year_built;
    }

    public function propertyCategory()
    {
        return $this->belongsTo(PropertyCategory::class, 'property_category_id');
    }
}
