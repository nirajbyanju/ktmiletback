<?php
// app/Services/Property/PropertyStoreService.php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyAddress;
use App\Models\PropertyImages;
use App\Models\HouseDetail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class PropertyStoreService
{
    public function __construct(
        private readonly AdminNotificationService $adminNotificationService,
    ) {
    }

    /**
     * Store a new property with all relationships
     *
     * @param array $data
     * @return Property
     */
    public function store(array $data): Property
    {
        return DB::transaction(function () use ($data) {
            $currentUser = Auth::user();
            $actorId = $currentUser?->id;

            if ($actorId && empty($data['created_by'])) {
                $data['created_by'] = $actorId;
            }

            if ($actorId) {
                $data['updated_by'] = $actorId;
            }

            // Create the property
            $property = $this->createProperty($data);

            // Create house details if property type is house
            if ($this->isHouseProperty($property)) {
                $this->createHouseDetails($property, $data);
            }

            // Create address if provided
            if ($this->hasAddressData($data)) {
                $this->createAddress($property, $data);
            }

            // Upload and create images if provided
            if (isset($data['images']) && is_array($data['images'])) {
                $this->createImages($property, $data['images'], $data);
            }

            // Handle additional features if provided
            if (isset($data['features']) && is_array($data['features'])) {
                $property->features()->sync($data['features']);
            }

            // Handle nearby places if provided
            if (isset($data['nearby_places']) && is_array($data['nearby_places'])) {
                $this->attachNearbyPlaces($property, $data['nearby_places']);
            }

            $property = $property->load([
                 'address', 
                 'images', 
                 'nearbyPlaces',
                'houseDetails',
                'houseDetails.furnishing',
                'houseDetails.houseType',
                'houseDetails.constructionStatus',
                'houseDetails.roofType',
                'houseDetails.buildingFace'
            ]);

            $propertyId = $property->id;

            DB::afterCommit(function () use ($propertyId, $currentUser): void {
                $freshProperty = Property::with([
                    'createdBy',
                    'propertyType',
                    'listingType',
                    'address',
                ])->find($propertyId);

                if ($freshProperty) {
                    $this->adminNotificationService->notifyPropertyCreated(
                        $freshProperty,
                        $currentUser instanceof User ? $currentUser : null
                    );
                }
            });

            return $property;
        });
    }

    /**
     * Update an existing property
     *
     * @param Property $property
     * @param array $data
     * @return Property
     */
    public function update(Property $property, array $data): Property
    {
        return DB::transaction(function () use ($property, $data) {
            if (Auth::id()) {
                $data['updated_by'] = Auth::id();
            }

            // Update the property
            $property->update($this->extractPropertyData($data));

            // Update or create house details if property is house
            if ($this->isHouseProperty($property)) {
                $this->updateOrCreateHouseDetails($property, $data);
            }

            // Update or create address
            if ($this->hasAddressData($data)) {
                $this->updateOrCreateAddress($property, $data);
            }

            // Handle images if provided
            if (isset($data['images']) && is_array($data['images'])) {
                $this->handleImageUpdates($property, $data['images'], $data);
            }

            // Handle image deletions if specified
            if (isset($data['delete_images']) && is_array($data['delete_images'])) {
                $this->deleteImages($property, $data['delete_images']);
            }

            // Sync features if provided
            if (isset($data['features']) && is_array($data['features'])) {
                $property->features()->sync($data['features']);
            }

            // Sync nearby places if provided
            if (isset($data['nearby_places']) && is_array($data['nearby_places'])) {
                $this->syncNearbyPlaces($property, $data['nearby_places']);
            }

            return $property->fresh([
                'address',
                'images',
                'features',
                'nearbyPlaces',
                'houseDetails',
                'houseDetails.furnishing',
                'houseDetails.houseType',
                'houseDetails.constructionStatus',
                'houseDetails.roofType',
                'houseDetails.buildingFace'
            ]);
        });
    }

    /**
     * Create the main property
     */
    protected function createProperty(array $data): Property
    {
        $propertyData = $this->extractPropertyData($data);

        // Generate property code if not provided
        if (empty($propertyData['property_code'])) {
            $propertyData['property_code'] = $this->generatePropertyCode();
        }

        // Generate slug if not provided
        if (empty($propertyData['slug']) && !empty($propertyData['title'])) {
            $propertyData['slug'] = Str::slug($propertyData['title'] . '-' . $propertyData['property_code']);
        }

        // Handle tags as JSON if it's an array
        if (isset($propertyData['tags']) && is_array($propertyData['tags'])) {
            $propertyData['tags'] = json_encode($propertyData['tags']);
        }

        return Property::create($propertyData);
    }

    /**
     * Check if property is a house type
     */
    protected function isHouseProperty(Property $property): bool
    {
        return $property->propertyType && $property->propertyType->slug == 'house';
    }

    /**
     * Create house details for the property
     */
    protected function createHouseDetails(Property $property, array $data): HouseDetail
    {
        $houseData = $this->extractHouseDetailsData($data);
        $houseData['property_id'] = $property->id;

        // Process floor details if provided
        if (isset($houseData['floor_details']) && is_array($houseData['floor_details'])) {
            $houseData['floor_details'] = $this->processFloorDetails($houseData['floor_details']);
        }

        // Process construction status details if provided
        if (isset($houseData['construction_status']) && is_array($houseData['construction_status'])) {
            $houseData['construction_status'] = json_encode($houseData['construction_status']);
        }

        // Process amenities if provided
        if (isset($houseData['amenities']) && is_array($houseData['amenities'])) {
            $houseData['amenities'] = json_encode($houseData['amenities']);
        }

        return HouseDetail::create($houseData);
    }

    /**
     * Update or create house details
     */
    protected function updateOrCreateHouseDetails(Property $property, array $data): HouseDetail
    {
        $houseData = $this->extractHouseDetailsData($data);

        // Process floor details if provided
        if (isset($houseData['floor_details']) && is_array($houseData['floor_details'])) {
            $houseData['floor_details'] = $this->processFloorDetails($houseData['floor_details']);
        }

        // Process construction status details if provided
        if (isset($houseData['construction_status_details']) && is_array($houseData['construction_status_details'])) {
            $houseData['construction_status_details'] = json_encode($houseData['construction_status_details']);
        }

        // Process amenities if provided
        if (isset($houseData['amenities']) && is_array($houseData['amenities'])) {
            $houseData['amenities'] = json_encode($houseData['amenities']);
        }

        return $property->houseDetails()->updateOrCreate(
            ['property_id' => $property->id],
            $houseData
        );
    }

    /**
     * Process floor details to ensure proper format
     */
    protected function processFloorDetails(array $floorDetails): string
    {
        $processed = [];

        foreach ($floorDetails as $floor) {
            $processed[] = [
                'floor' => $floor['floor'] ?? 1,
                'rooms' => $floor['rooms'] ?? [],
                'area' => $floor['area'] ?? 0,
                'description' => $floor['description'] ?? null,
                'room_details' => $this->processRoomDetails($floor['rooms'] ?? [])
            ];
        }

        return json_encode($processed);
    }

    /**
     * Process room details to add additional information
     */
    protected function processRoomDetails(array $rooms): array
    {
        $processed = [];

        foreach ($rooms as $room) {
            if (is_string($room)) {
                // Simple room name
                $processed[] = [
                    'name' => $room,
                    'count' => 1,
                    'size' => null,
                    'features' => []
                ];
            } elseif (is_array($room)) {
                // Detailed room information
                $processed[] = [
                    'name' => $room['name'] ?? 'Room',
                    'count' => $room['count'] ?? 1,
                    'size' => $room['size'] ?? null,
                    'size_unit' => $room['size_unit'] ?? 'sq ft',
                    'features' => $room['features'] ?? []
                ];
            }
        }

        return $processed;
    }

    /**
     * Create property address
     */
    protected function createAddress(Property $property, array $data): PropertyAddress
    {
        $addressData = $this->extractAddressData($data);
        $addressData['property_id'] = $property->id;

        // Generate full address if not provided
        if (empty($addressData['full_address'])) {
            $addressData['full_address'] = $this->generateFullAddress($addressData);
        }

        return PropertyAddress::create($addressData);
    }

    /**
     * Update or create property address
     */
    protected function updateOrCreateAddress(Property $property, array $data): PropertyAddress
    {
        $addressData = $this->extractAddressData($data);

        // Generate full address if not provided
        if (empty($addressData['full_address'])) {
            $addressData['full_address'] = $this->generateFullAddress($addressData);
        }

        return $property->address()->updateOrCreate(
            ['property_id' => $property->id],
            $addressData
        );
    }

    /**
     * Create property images
     */
    protected function createImages(Property $property, array $images, array $data): void
    {
        foreach ($images as $index => $image) {
            if ($image instanceof UploadedFile) {
                $path = $this->uploadImage($image);

                PropertyImages::create([
                    'property_id' => $property->id,
                    'image_url' => $path,
                    'image_type' => $data['image_types'][$index] ?? 'gallery',
                    'is_featured' => ($data['featured_image_index'] ?? 0) == $index,
                    'sort_order' => $index,
                ]);
            }
        }
    }

    /**
     * Handle image updates
     */
    protected function handleImageUpdates(Property $property, array $images, array $data): void
    {
        foreach ($images as $index => $image) {
            if ($image instanceof UploadedFile) {
                // Upload new image
                $path = $this->uploadImage($image);

                PropertyImages::create([
                    'property_id' => $property->id,
                    'image_url' => $path,
                    'image_type' => $data['image_types'][$index] ?? 'gallery',
                    'is_featured' => ($data['featured_image_index'] ?? 0) == $index,
                    'sort_order' => $property->images()->count(),
                ]);
            } elseif (is_string($image) && str_starts_with($image, 'http')) {
                // Handle existing image URL (kept as is)
                $existingImage = $property->images()->where('image_url', $image)->first();
                if ($existingImage) {
                    $existingImage->update([
                        'is_featured' => ($data['featured_image_index'] ?? 0) == $index,
                        'sort_order' => $index,
                    ]);
                }
            }
        }
    }

    /**
     * Delete specified images
     */
    protected function deleteImages(Property $property, array $imageIds): void
    {
        $images = $property->images()->whereIn('id', $imageIds)->get();

        foreach ($images as $image) {
            // Delete file from storage
            Storage::disk('public')->delete($image->image_url);
            // Delete record
            $image->delete();
        }
    }

    /**
     * Upload image to storage
     */
    protected function uploadImage(UploadedFile $image): string
    {
        $path = $image->store('properties', 'public');

        // Optional: Resize or optimize image here
        // Image::make($image)->resize(1200, 800)->save();

        return $path;
    }

    /**
     * Attach nearby places
     */
    protected function attachNearbyPlaces(Property $property, array $nearbyPlaces): void
    {
        foreach ($nearbyPlaces as $place) {
            $property->nearbyPlaces()->create([
                'name' => $place['name'],
                'type' => $place['type'],
                'distance' => $place['distance'],
                'distance_unit' => $place['distance_unit'] ?? 'km',
                'description' => $place['description'] ?? null,
            ]);
        }
    }

    /**
     * Sync nearby places
     */
    protected function syncNearbyPlaces(Property $property, array $nearbyPlaces): void
    {
        // Delete existing
        $property->nearbyPlaces()->delete();

        // Create new
        $this->attachNearbyPlaces($property, $nearbyPlaces);
    }

    /**
     * Extract house details data from request
     */
    protected function extractHouseDetailsData(array $data): array
    {
        $houseFields = [
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
            'building_face_id'
        ];

        return array_filter(
            array_intersect_key($data, array_flip($houseFields)),
            fn($value) => !is_null($value) && $value !== ''
        );
    }

    /**
     * Extract property data from request
     */
    protected function extractPropertyData(array $data): array
    {
        $propertyFields = [
            'property_code',
            'title',
            'slug',
            'tags',
            'description',
            'land_area',
            'land_unit_id',
            'property_face_id',
            'property_type_id',
            'property_category_id',
            'listing_type_id',
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
            'status',
            'video_url',
        ];

        return array_filter(
            array_intersect_key($data, array_flip($propertyFields)),
            fn($value) => !is_null($value)
        );
    }

    /**
     * Extract address data from request
     */
    protected function extractAddressData(array $data): array
    {
        $addressFields = [
            'province_id',
            'district_id',
            'municipality_id',
            'ward_id',
            'area',
            'postal_code',
            'full_address',
            'latitude',
            'longitude'
        ];

        return array_filter(
            array_intersect_key($data, array_flip($addressFields)),
            fn($value) => !is_null($value) && $value !== ''
        );
    }

    /**
     * Check if address data exists
     */
    protected function hasAddressData(array $data): bool
    {
        $addressFields = ['province_id', 'district_id', 'municipality_id', 'ward_id', 'area', 'postal_code'];

        foreach ($addressFields as $field) {
            if (!empty($data[$field])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if house details data exists
     */
    protected function hasHouseDetailsData(array $data): bool
    {
        $houseFields = [
            'furnishing_id',
            'house_type_id',
            'built_area',
            'total_floors',
            'year_built',
            'construction_status',
            'parking_cars',
            'parking_bikes'
        ];

        foreach ($houseFields as $field) {
            if (!empty($data[$field])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate full address from components
     */
    protected function generateFullAddress(array $addressData): string
    {
        $parts = [];

        if (!empty($addressData['area'])) $parts[] = $addressData['area'];
        if (!empty($addressData['ward_id'])) $parts[] = 'Ward ' . $addressData['ward_id'];
        if (!empty($addressData['municipality_id'])) $parts[] = $addressData['municipality_id'];
        if (!empty($addressData['district_id'])) $parts[] = $addressData['district_id'];
        if (!empty($addressData['province_id'])) $parts[] = $addressData['province_id'];

        return implode(', ', $parts);
    }

    /**
     * Generate unique property code
     */
    protected function generatePropertyCode(): string
    {
        $year = date('Y');

        $lastProperty = Property::where('property_code', 'like', "SRE{$year}-%")
            ->orderBy('property_code', 'desc')
            ->first();

        if ($lastProperty) {
            $lastNumber = (int) substr($lastProperty->property_code, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "SRE{$year}-{$newNumber}";
    }

    public function listActiveProperty($request)
    {
        $orderBy = in_array(strtoupper($request->get('order_by')), ['ASC', 'DESC'])
            ? strtoupper($request->get('order_by'))
            : 'DESC';

        $allowedSortColumns = [
            'created_at',
            'updated_at',
            'title',
            'base_price',
            'advertise_price',
            'views_count',
            'likes_count',
            'status',
            'is_status',
        ];

        $requestedOrderColumn = Str::snake($request->get('order_column') ?? 'created_at');
        $orderColumn = in_array($requestedOrderColumn, $allowedSortColumns, true)
            ? $requestedOrderColumn
            : 'created_at';

        $limit = $request->get('limit');
        if (empty($limit) || $limit == 0) {
            $limit = $request->header('X-Limit-No') ?? 10;
        }

        $limit = is_numeric($limit) ? (int) $limit : 10;
        $page  = is_numeric($request->get('page')) ? (int) $request->get('page') : 1;

        $allowedFilters = [
            'property_category_id',
            'property_type_id',
            'listing_type_id',
            'property_status_id',
            'is_status',
            'status',
            'title',
        ];

        $filters = collect($request->all())
            ->mapWithKeys(function ($value, $key) {
                return [Str::snake($key) => $value];
            })
            ->only($allowedFilters)
            ->filter();

        $query = Property::with(['houseDetails','address','nearbyPlaces','images', 'landUnit', 'propertyFace', 'propertyType', 'listingType', 'measureUnit', 'roadType', 'roadCondition', 'waterSource', 'sewageType', 'propertyStatus', 'createdBy', 'updatedBy', 'verifiedBy']);

        foreach ($filters as $field => $value) {
            if ($field === 'title') {
                $query->where($field, 'like', "%{$value}%");
            } else {
                $query->where($field, $value);
            }
        }

        $query->orderBy($orderColumn, $orderBy);

        return $query->paginate($limit, ['*'], 'page', $page);
    }
}
