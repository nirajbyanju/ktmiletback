<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseController;
use Illuminate\Http\Request;
use App\Models\Property;
use App\Http\Resources\PropertyFrontResources;
use App\Http\Resources\PropertyDetailResource;

class FrontController extends BaseController
{
    /**
     * Display a listing of properties with pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function propertySummary(Request $request)
    {
        try {
            // Determine order
            $orderBy = in_array(strtoupper($request->get('order_by')), ['ASC', 'DESC'])
                ? strtoupper($request->get('order_by'))
                : 'DESC';

            // Pagination parameters
            $limit = is_numeric($request->get('limit')) ? (int) $request->get('limit') : 10;
            $page = is_numeric($request->get('page')) ? (int) $request->get('page') : 1;

            // Fetch properties with relations and pagination
            $paginatedResults = Property::with([
                'propertyFace', 
                'listingType', 
                'propertyStatus', 
                'images',
                'address'
            ])
            // ->where('is_featured', 1) // Uncomment if needed
            ->orderBy('updated_at', $orderBy)
            ->paginate($limit, ['*'], 'page', $page);

            // Return JSON response with collection resource
            return response()->json([
                'status' => true,
                'message' => 'List of properties',
                'data' => PropertyFrontResources::collection($paginatedResults->getCollection()),
                'pagination' => [
                    'total' => $paginatedResults->total(),
                    'per_page' => $paginatedResults->perPage(),
                    'current_page' => $paginatedResults->currentPage(),
                    'last_page' => $paginatedResults->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching properties',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

     public function propertyDetail($slug){
        try {
            $property = Property::with([
                'propertyFace', 
                'listingType', 
                'propertyStatus', 
                'images',
                'address',
                'houseDetails',
                'houseDetails.furnishing',
                'houseDetails.houseType',
                'houseDetails.builtAreaUnit',
                'houseDetails.constructionStatus',
                'houseDetails.roofType',
                'houseDetails.parkingType',
                'houseDetails.parkingAreaUnit',
                'houseDetails.buildingFace',
                'propertyType',
                'roadType',
                'roadCondition',
                'waterSource',
                'sewageType',
                'landUnit',
                'measureUnit',
                'propertyCategory'
            ])
                ->where('slug', $slug)
                ->first();

                if(!$property){
                    return response()->json([
                        'status' => false,
                        'message' => 'Property not found',
                        'error' => 'Property not found',
                    ], 404);
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Property found',
                    'data' => $property,
                ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching property',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

public function propertyList(Request $request)
{
    try {
        // Support a simple frontend sort contract and keep legacy order params working.
        $allowedOrderFields = ['updated_at', 'created_at', 'advertise_price', 'land_area', 'title'];
        $sort = strtolower((string) $request->get('sort', ''));
        $orderByField = $request->get('order_by_field', 'updated_at');
        $orderBy = in_array(strtoupper($request->get('order_by')), ['ASC', 'DESC'])
            ? strtoupper($request->get('order_by'))
            : 'DESC';

        switch ($sort) {
            case 'latest':
                $orderByField = 'updated_at';
                $orderBy = 'DESC';
                break;
            case 'oldest':
                $orderByField = 'created_at';
                $orderBy = 'ASC';
                break;
            case 'price_low_to_high':
            case 'price_asc':
                $orderByField = 'advertise_price';
                $orderBy = 'ASC';
                break;
            case 'price_high_to_low':
            case 'price_desc':
                $orderByField = 'advertise_price';
                $orderBy = 'DESC';
                break;
            case 'title_asc':
                $orderByField = 'title';
                $orderBy = 'ASC';
                break;
            case 'title_desc':
                $orderByField = 'title';
                $orderBy = 'DESC';
                break;
            default:
                $orderByField = in_array($orderByField, $allowedOrderFields, true)
                    ? $orderByField
                    : 'updated_at';
                break;
        }

        // Pagination parameters
        $limit = is_numeric($request->get('limit')) ? (int) $request->get('limit') : 10;
        $page = is_numeric($request->get('page')) ? (int) $request->get('page') : 1;

        // Start query with relations
        $query = Property::with([
            'propertyFace', 
            'listingType', 
            'propertyStatus', 
            'images',
            'address',
            'propertyType',
            'roadType',
            'roadCondition',
            'waterSource',
            'sewageType',
            'landUnit',
            'measureUnit',
            'houseDetails'
        ]);

        // ========== TEXT SEARCH FILTERS ==========
        
        // Filter by title (partial match)
        if ($request->has('title') && !empty($request->title)) {
            $query->where('title', 'LIKE', '%' . $request->title . '%');
        }
        
        // Filter by tags (can be comma-separated)
        if ($request->has('tags') && !empty($request->tags)) {
            $tags = explode(',', $request->tags);
            $query->where(function($q) use ($tags) {
                foreach ($tags as $tag) {
                    $tag = trim($tag);
                    if (!empty($tag)) {
                        $q->orWhere('tags', 'LIKE', '%' . $tag . '%');
                    }
                }
            });
        }
        
        // Filter by property code
        if ($request->has('property_code') && !empty($request->property_code)) {
            $query->where('property_code', 'LIKE', '%' . $request->property_code . '%');
        }
        
        // Filter by description
        if ($request->has('description') && !empty($request->description)) {
            $query->where('description', 'LIKE', '%' . $request->description . '%');
        }

        // ========== EXACT MATCH FILTERS ==========
        
        // Filter by status (draft, published, etc.)
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }
        
        // Filter by currency
        if ($request->has('currency') && !empty($request->currency)) {
            $query->where('currency', $request->currency);
        }

        // ========== FOREIGN KEY FILTERS ==========
        
        $foreignKeyFilters = [
            'property_face_id' => 'propertyFace',
            'property_type_id' => 'propertyType',
            'listing_type_id' => 'listingType',
            'property_status_id' => 'propertyStatus',
            'road_type_id' => 'roadType',
            'road_condition_id' => 'roadCondition',
            'water_source_id' => 'waterSource',
            'sewage_type_id' => 'sewageType',
            'land_unit_id' => 'landUnit',
            'measure_unit_id' => 'measureUnit',
            'created_by' => 'createdBy'
        ];
        
        foreach ($foreignKeyFilters as $field => $relation) {
            if ($request->has($field) && !empty($request->$field)) {
                if (is_array($request->$field)) {
                    $query->whereIn($field, $request->$field);
                } else {
                    $query->where($field, $request->$field);
                }
            }
        }

        $slugRelationFilters = [
            'listing_type_slug' => 'listingType',
            'property_type_slug' => 'propertyType',
            'property_status_slug' => 'propertyStatus',
            'property_face_slug' => 'propertyFace',
            'property_category_slug' => 'propertyCategory',
        ];

        foreach ($slugRelationFilters as $requestKey => $relation) {
            if ($request->filled($requestKey)) {
                $query->whereHas($relation, function ($relationQuery) use ($request, $requestKey) {
                    $relationQuery->where('slug', $request->get($requestKey));
                });
            }
        }

        // ========== BOOLEAN FILTERS ==========
        
        $booleanFilters = [
            'is_featured',
            'is_negotiable',
            'banking_available',
            'has_electricity',
            'is_road_accessible'
        ];
        
        foreach ($booleanFilters as $filter) {
            if ($request->has($filter) && $request->$filter !== '') {
                $query->where($filter, filter_var($request->$filter, FILTER_VALIDATE_BOOLEAN));
            }
        }

        // ========== RANGE FILTERS ==========
        
        // Price range filter
        if ($request->has('min_price') && is_numeric($request->min_price)) {
            $query->where('advertise_price', '>=', $request->min_price);
        }
        if ($request->has('max_price') && is_numeric($request->max_price)) {
            $query->where('advertise_price', '<=', $request->max_price);
        }
        
        // Land area range filter
        if ($request->has('min_land_area') && is_numeric($request->min_land_area)) {
            $query->where('land_area', '>=', $request->min_land_area);
        }
        if ($request->has('max_land_area') && is_numeric($request->max_land_area)) {
            $query->where('land_area', '<=', $request->max_land_area);
        }
        
        // Road width range filter
        if ($request->has('min_road_width') && is_numeric($request->min_road_width)) {
            $query->where('road_width', '>=', $request->min_road_width);
        }
        if ($request->has('max_road_width') && is_numeric($request->max_road_width)) {
            $query->where('road_width', '<=', $request->max_road_width);
        }
        
        // Length range filter
        if ($request->has('min_length') && is_numeric($request->min_length)) {
            $query->where('length', '>=', $request->min_length);
        }
        if ($request->has('max_length') && is_numeric($request->max_length)) {
            $query->where('length', '<=', $request->max_length);
        }
        
        // Height range filter
        if ($request->has('min_height') && is_numeric($request->min_height)) {
            $query->where('height', '>=', $request->min_height);
        }
        if ($request->has('max_height') && is_numeric($request->max_height)) {
            $query->where('height', '<=', $request->max_height);
        }

        // ========== DATE RANGE FILTERS ==========
        
        // Created at date range
        if ($request->has('created_from') && !empty($request->created_from)) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        if ($request->has('created_to') && !empty($request->created_to)) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }
        
        // Updated at date range
        if ($request->has('updated_from') && !empty($request->updated_from)) {
            $query->whereDate('updated_at', '>=', $request->updated_from);
        }
        if ($request->has('updated_to') && !empty($request->updated_to)) {
            $query->whereDate('updated_at', '<=', $request->updated_to);
        }
        
        // Verified at date range
        if ($request->has('verified_from') && !empty($request->verified_from)) {
            $query->whereDate('verified_at', '>=', $request->verified_from);
        }
        if ($request->has('verified_to') && !empty($request->verified_to)) {
            $query->whereDate('verified_at', '<=', $request->verified_to);
        }

        // ========== VERIFICATION FILTERS ==========
        
        // Filter by verification status
        if ($request->has('is_verified') && $request->is_verified !== '') {
            if (filter_var($request->is_verified, FILTER_VALIDATE_BOOLEAN)) {
                $query->whereNotNull('verified_at');
            } else {
                $query->whereNull('verified_at');
            }
        }

        // ========== ADDRESS/RELATED MODEL FILTERS ==========
        
        // Filter by address fields
        if ($request->has('city') && !empty($request->city)) {
            $query->whereHas('address', function($q) use ($request) {
                $q->where('city', 'LIKE', '%' . $request->city . '%');
            });
        }
        
        if ($request->has('state') && !empty($request->state)) {
            $query->whereHas('address', function($q) use ($request) {
                $q->where('state', 'LIKE', '%' . $request->state . '%');
            });
        }
        
        if ($request->has('country') && !empty($request->country)) {
            $query->whereHas('address', function($q) use ($request) {
                $q->where('country', 'LIKE', '%' . $request->country . '%');
            });
        }
        
        if ($request->has('zip_code') && !empty($request->zip_code)) {
            $query->whereHas('address', function($q) use ($request) {
                $q->where('zip_code', 'LIKE', '%' . $request->zip_code . '%');
            });
        }
        
        if ($request->has('address_line') && !empty($request->address_line)) {
            $query->whereHas('address', function($q) use ($request) {
                $q->where('address_line', 'LIKE', '%' . $request->address_line . '%')
                  ->orWhere('address_line_2', 'LIKE', '%' . $request->address_line . '%');
            });
        }

        // ========== HOUSE DETAILS FILTERS ==========
        
        if ($request->has('bedrooms') && is_numeric($request->bedrooms)) {
            $query->whereHas('houseDetails', function($q) use ($request) {
                $q->where('bedrooms', $request->bedrooms);
            });
        }
        
        if ($request->has('bathrooms') && is_numeric($request->bathrooms)) {
            $query->whereHas('houseDetails', function($q) use ($request) {
                $q->where('bathrooms', $request->bathrooms);
            });
        }
        
        if ($request->has('kitchens') && is_numeric($request->kitchens)) {
            $query->whereHas('houseDetails', function($q) use ($request) {
                $q->where('kitchens', $request->kitchens);
            });
        }
        
        if ($request->has('floors') && is_numeric($request->floors)) {
            $query->whereHas('houseDetails', function($q) use ($request) {
                $q->where('floors', $request->floors);
            });
        }
        
        if ($request->has('parking') && $request->parking !== '') {
            $query->whereHas('houseDetails', function($q) use ($request) {
                $q->where('parking', filter_var($request->parking, FILTER_VALIDATE_BOOLEAN));
            });
        }
        
        if ($request->has('furnished') && $request->furnished !== '') {
            $query->whereHas('houseDetails', function($q) use ($request) {
                $q->where('furnished', filter_var($request->furnished, FILTER_VALIDATE_BOOLEAN));
            });
        }

        // ========== GEOGRAPHIC FILTERS ==========
        
        // Nearby location filter (requires latitude and longitude)
        if ($request->has('latitude') && $request->has('longitude') && $request->has('distance')) {
            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $distance = $request->distance; // in kilometers
            
            $haversine = "(6371 * acos(cos(radians($latitude)) 
                          * cos(radians(latitude)) 
                          * cos(radians(longitude) - radians($longitude)) 
                          + sin(radians($latitude)) 
                          * sin(radians(latitude))))";
            
            $query->select('*')
                ->selectRaw("{$haversine} AS distance")
                ->whereRaw("{$haversine} < ?", [$distance])
                ->orderBy('distance');
        }

        // ========== SPECIAL FILTERS ==========
        
        // Filter by multiple IDs
        if ($request->has('ids') && !empty($request->ids)) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->whereIn('id', $ids);
        }
        
        // Exclude specific IDs
        if ($request->has('exclude_ids') && !empty($request->exclude_ids)) {
            $excludeIds = is_array($request->exclude_ids) ? $request->exclude_ids : explode(',', $request->exclude_ids);
            $query->whereNotIn('id', $excludeIds);
        }
        
        // Filter by has images
        if ($request->has('has_images') && $request->has_images !== '') {
            if (filter_var($request->has_images, FILTER_VALIDATE_BOOLEAN)) {
                $query->has('images');
            } else {
                $query->doesntHave('images');
            }
        }
        
        // Filter by has address
        if ($request->has('has_address') && $request->has_address !== '') {
            if (filter_var($request->has_address, FILTER_VALIDATE_BOOLEAN)) {
                $query->has('address');
            } else {
                $query->doesntHave('address');
            }
        }

        // Apply ordering
        $query->orderBy($orderByField, $orderBy);

        // Apply pagination
        $paginatedResults = $query->paginate($limit, ['*'], 'page', $page);

        // Return JSON response with collection resource
        return response()->json([
            'status' => true,
            'message' => 'List of properties',
            'data' => PropertyFrontResources::collection($paginatedResults->getCollection()),
            'filters_applied' => $request->all(), // Optional: show which filters were applied
            'pagination' => [
                'total' => $paginatedResults->total(),
                'per_page' => $paginatedResults->perPage(),
                'current_page' => $paginatedResults->currentPage(),
                'last_page' => $paginatedResults->lastPage(),
                'from' => $paginatedResults->firstItem(),
                'to' => $paginatedResults->lastItem(),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Error fetching properties',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}
