<?php
// app/Data/HouseDetail.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Property;
use App\Models\Data\Furnishing;
use App\Models\Data\HouseType;
use App\Models\Data\ConstructionStatus;
use App\Models\Data\RoofType;
use App\Models\Data\PropertyFace;
use App\Models\User;
use App\Models\Data\Unit;
use App\Models\Data\ParkingType;



class HouseDetail extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'house_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'property_id',
        'furnishing_id',
        'house_type_id',
        'built_area',
        'built_area_unit_id',
        'total_floors',
        'floor_details',
        'year_built',
        'year_renovated',
        'construction_status',
        'construction_status_details',
        'roof_type_id',
        'reserved_tank',
        'tank_area',
        'parking_cars',
        'parking_bikes',
        'parking_type_id',
        'parking_area',
        'parking_area_unit_id',
        'amenities',
        'building_face_id',
        'created_by',
        'updated_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_floors' => 'float',
        'floor_details' => 'array',
        'construction_status_details' => 'array',
        'amenities' => 'array',
        'year_built' => 'integer',
        'year_renovated' => 'integer',
        'parking_cars' => 'integer',
        'parking_bikes' => 'integer',
        'parking_area' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at'
    ];

    // ========== Relationships ==========

    /**
     * Get the property that owns this house detail.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /**
     * Get the furnishing status.
     */
    public function furnishing(): BelongsTo
    {
        return $this->belongsTo(Furnishing::class, 'furnishing_id');
    }


    /**
     * Get the house type.
     */
    public function houseType(): BelongsTo
    {
        return $this->belongsTo(HouseType::class, 'house_type_id');
    }

    /**
     * Get the built area unit.
     */
    public function builtAreaUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'built_area_unit_id');
    }

    /**
     * Get the construction status.
     */
    public function constructionStatus(): BelongsTo
    {
        return $this->belongsTo(ConstructionStatus::class, 'construction_status');
    }

    /**
     * Get the roof type.
     */
    public function roofType(): BelongsTo
    {
        return $this->belongsTo(RoofType::class, 'roof_type_id');
    }

    /**
     * Get the parking area unit.
     */
    public function parkingAreaUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parking_area_unit_id');
    }

    /**
     * Get the building face.
     */
    public function buildingFace(): BelongsTo
    {
        return $this->belongsTo(PropertyFace::class, 'building_face_id');
    }

    public function parkingType()
    {
        return $this->belongsTo(ParkingType::class, 'parking_type_id');
    }

    /**
     * Get the user who created this record.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who updated this record.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ========== Scopes ==========

    /**
     * Scope a query to only include houses with parking.
     */
    public function scopeHasParking($query)
    {
        return $query->where(function($q) {
            $q->where('parking_cars', '>', 0)
              ->orWhere('parking_bikes', '>', 0);
        });
    }

    /**
     * Scope a query to only include houses with specific parking type.
     */
    public function scopeParkingType($query, $type)
    {
        return $query->where('parking_type_id', $type);
    }

    /**
     * Scope a query to only include houses built after a certain year.
     */
    public function scopeBuiltAfter($query, $year)
    {
        return $query->where('year_built', '>=', $year);
    }

    /**
     * Scope a query to only include houses built before a certain year.
     */
    public function scopeBuiltBefore($query, $year)
    {
        return $query->where('year_built', '<=', $year);
    }

    /**
     * Scope a query to only include houses renovated.
     */
    public function scopeRenovated($query)
    {
        return $query->whereNotNull('year_renovated');
    }

    /**
     * Scope a query to only include houses with specific furnishing.
     */
    public function scopeFurnishing($query, $furnishingId)
    {
        return $query->where('furnishing_id', $furnishingId);
    }

    /**
     * Scope a query to only include houses with specific house type.
     */
    public function scopeHouseType($query, $houseTypeId)
    {
        return $query->where('house_type_id', $houseTypeId);
    }

    /**
     * Scope a query to only include houses with minimum parking cars.
     */
    public function scopeMinParkingCars($query, $count)
    {
        return $query->where('parking_cars', '>=', $count);
    }

    /**
     * Scope a query to only include houses with minimum parking bikes.
     */
    public function scopeMinParkingBikes($query, $count)
    {
        return $query->where('parking_bikes', '>=', $count);
    }

    // ========== Accessors & Mutators ==========

    /**
     * Get the total parking spaces.
     */
    public function getTotalParkingAttribute(): int
    {
        return ($this->parking_cars ?? 0) + ($this->parking_bikes ?? 0);
    }

    /**
     * Get the formatted built area.
     */
    public function getFormattedBuiltAreaAttribute(): string
    {
        if (!$this->built_area) {
            return 'N/A';
        }
        
        $unit = $this->builtAreaUnit->symbol ?? 'sq ft';
        return $this->built_area . ' ' . $unit;
    }

    /**
     * Get the formatted parking area.
     */
    public function getFormattedParkingAreaAttribute(): string
    {
        if (!$this->parking_area) {
            return 'N/A';
        }
        
        $unit = $this->parkingAreaUnit->symbol ?? 'sq ft';
        return number_format($this->parking_area, 2) . ' ' . $unit;
    }

    /**
     * Get the construction status text.
     */
    public function getConstructionStatusTextAttribute(): string
    {
        if ($this->constructionStatus) {
            return $this->constructionStatus->name;
        }
        
        return $this->construction_status_details['status'] ?? 'Unknown';
    }

    /**
     * Check if the house has parking.
     */
    public function getHasParkingAttribute(): bool
    {
        return $this->total_parking > 0;
    }

    /**
     * Check if the house is newly built (less than 5 years old).
     */
    public function getIsNewlyBuiltAttribute(): bool
    {
        if (!$this->year_built) {
            return false;
        }
        
        return (date('Y') - $this->year_built) <= 5;
    }

    /**
     * Get the age of the house.
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->year_built) {
            return null;
        }
        
        return date('Y') - $this->year_built;
    }

    /**
     * Get the amenities as a comma-separated string.
     */
    public function getAmenitiesListAttribute(): string
    {
        if (!$this->amenities) {
            return '';
        }
        
        $amenities = is_array($this->amenities) ? $this->amenities : json_decode($this->amenities, true);
        
        return implode(', ', array_map('ucfirst', $amenities));
    }

    /**
     * Get the floor count description.
     */
    public function getFloorDescriptionAttribute(): string
    {
        if (!$this->total_floors) {
            return 'N/A';
        }
        
        $floor = $this->total_floors;
        
        if ($floor == 1) return 'Ground Floor Only';
        if ($floor == 2) return 'Ground + 1 Floor';
        if ($floor == 3) return 'Ground + 2 Floors';
        return $floor . ' Floors';
    }

    // ========== Custom Methods ==========

    /**
     * Check if a specific amenity is available.
     */
    public function hasAmenity(string $amenity): bool
    {
        if (!$this->amenities) {
            return false;
        }
        
        $amenities = is_array($this->amenities) ? $this->amenities : json_decode($this->amenities, true);
        
        return in_array($amenity, $amenities);
    }

    /**
     * Add an amenity.
     */
    public function addAmenity(string $amenity): void
    {
        $amenities = $this->amenities ?? [];
        
        if (is_string($amenities)) {
            $amenities = json_decode($amenities, true) ?? [];
        }
        
        if (!in_array($amenity, $amenities)) {
            $amenities[] = $amenity;
            $this->amenities = $amenities;
            $this->save();
        }
    }

    /**
     * Remove an amenity.
     */
    public function removeAmenity(string $amenity): void
    {
        $amenities = $this->amenities ?? [];
        
        if (is_string($amenities)) {
            $amenities = json_decode($amenities, true) ?? [];
        }
        
        $this->amenities = array_values(array_diff($amenities, [$amenity]));
        $this->save();
    }

    /**
     * Get floor details for a specific floor.
     */
    public function getFloorDetail(int $floorNumber): ?array
    {
        $floorDetails = $this->floor_details ?? [];
        
        if (is_string($floorDetails)) {
            $floorDetails = json_decode($floorDetails, true) ?? [];
        }
        
        foreach ($floorDetails as $floor) {
            if (($floor['floor'] ?? null) == $floorNumber) {
                return $floor;
            }
        }
        
        return null;
    }

    /**
     * Calculate total built area from floor details.
     */
    public function calculateTotalBuiltArea(): ?float
    {
        $floorDetails = $this->floor_details ?? [];
        
        if (is_string($floorDetails)) {
            $floorDetails = json_decode($floorDetails, true) ?? [];
        }
        
        $total = 0;
        foreach ($floorDetails as $floor) {
            $total += $floor['area'] ?? 0;
        }
        
        return $total > 0 ? $total : null;
    }
}