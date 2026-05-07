<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Property;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\BaseController;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Services\PropertyStoreService;
use Illuminate\Http\Response;

class PropertyController extends BaseController  implements HasMiddleware
{
    protected PropertyStoreService $propertyStoreService;

    public function __construct(PropertyStoreService $propertyStoreService)
    {
        $this->propertyStoreService = $propertyStoreService;
    }
    public static function middleware(): array
    {
        return [];
    }

    public function index(Request $request)
    {
        $paginatedResults = $this->propertyStoreService->listActiveProperty($request);

        if ($paginatedResults->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No posts available',
                'data' => [],
                'pagination' => [
                    'total' => $paginatedResults->total(),
                    'per_page' => $paginatedResults->perPage(),
                    'current_page' => $paginatedResults->currentPage(),
                    'last_page' => $paginatedResults->lastPage(),
                ],
            ], 200);
        }

        return response()->json([
            'status' => true,
            'message' => 'List of posts',
            'data' => $paginatedResults->items(),
            'pagination' => [
                'total' => $paginatedResults->total(), // Total records
                'per_page' => $paginatedResults->perPage(), // Items per page
                'current_page' => $paginatedResults->currentPage(), // Current page 
                'last_page' => $paginatedResults->lastPage(), // Last page number
            ],
        ], 200);
    }

    public function store(Request $request)
    {
        try {
            $property = $this->propertyStoreService->store($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Property created successfully.',
                'data' => $property,
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating vacancy.',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function show(Property $property)
    {
        $property->load([
            'address',
            'images',
            'nearbyPlaces',
            'houseDetails',
            'houseDetails.furnishing',
            'houseDetails.houseType',
            'houseDetails.constructionStatus',
            'houseDetails.roofType',
            'houseDetails.buildingFace',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Property details fetched successfully',
            'data' => $property,
        ], 200);
    }

    public function update(Request $request, Property $property)
    {
        $property = $this->propertyStoreService->update($property, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Property updated successfully.',
            'data' => $property,
        ]);
    }

    public function destroy(Property $property)
    {
        $property->delete();

        return response()->json([
            'success' => true,
            'message' => 'Property deleted successfully.',
        ]);
    }

    public function updateStatus(Request $request, Property $property)
    {
        $validated = $request->validate([
            'isStatus' => 'nullable|boolean',
            'is_status' => 'nullable|boolean',
        ]);

        $property->is_status = $validated['is_status'] ?? $validated['isStatus'] ?? $property->is_status;
        $property->save();

        return response()->json([
            'success' => true,
            'message' => 'Property status updated successfully.',
            'data' => $property,
        ]);
    }
}
