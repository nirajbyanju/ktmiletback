<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FieldVisits;
use App\Http\Controllers\Api\V1\BaseController;
use App\Services\FieldVisitsService;
use Illuminate\Http\Response;

class FieldVisitsController extends BaseController
{
    protected $fieldVisitsService;

    public function __construct(FieldVisitsService $fieldVisitsService)
    {
        $this->fieldVisitsService = $fieldVisitsService;
    }
    public static function middleware(): array
    {
        return [
            // new Middleware('permission:edit articles', only: ['edit', 'update']),
            // new Middleware('permission:create articles', only: ['create', 'store']),
            // new Middleware('permission:delete articles', only: ['delete', 'destroy']),
            // new Middleware('permission:view articles', only: ['index', 'show']),
        ];
    }

    public function index(Request $request, ?int $propertyId = null)
    {
        $paginatedResults = $this->fieldVisitsService->listActiveFieldVisits($request, $propertyId);

        if ($paginatedResults->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No field visits available.',
                'data' => [],
                'meta' => [
                    'scoped_property_id' => $propertyId,
                ],
                'pagination' => [
                    'total' => $paginatedResults->total(),
                    'per_page' => $paginatedResults->perPage(),
                    'current_page' => $paginatedResults->currentPage(),
                    'last_page' => $paginatedResults->lastPage(),
                ],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Field visits retrieved successfully.',
            'data' => $paginatedResults->items(),
            'meta' => [
                'scoped_property_id' => $propertyId,
            ],
            'pagination' => [
                'total' => $paginatedResults->total(),
                'per_page' => $paginatedResults->perPage(),
                'current_page' => $paginatedResults->currentPage(),
                'last_page' => $paginatedResults->lastPage(),
            ],
        ], 200);
    }

    public function store(Request $request, ?int $propertyId = null)
    {
        try {
            $payload = $request->all();

            if ($propertyId !== null) {
                $payload['property_id'] = $propertyId;
            }

            $fieldVisit = $this->fieldVisitsService->store($payload);

            return response()->json([
                'success' => true,
                'message' => 'Field visit created successfully.',
                'data' => $fieldVisit->load('property'),
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

    public function show(?int $propertyId, FieldVisits $fieldVisit)
    {
        $fieldVisit = $this->resolveFieldVisit($fieldVisit, $propertyId);

        return response()->json([
            'success' => true,
            'message' => 'Field visit retrieved successfully.',
            'data' => $fieldVisit->load('property'),
        ]);
    }

    public function update(Request $request, ?int $propertyId, FieldVisits $fieldVisit)
    {
        $fieldVisit = $this->resolveFieldVisit($fieldVisit, $propertyId);
        $payload = $request->only($fieldVisit->getFillable());

        if ($propertyId !== null) {
            $payload['property_id'] = $propertyId;
        }

        $fieldVisit->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Field visit updated successfully.',
            'data' => $fieldVisit->fresh()->load('property'),
        ]);
    }

    public function destroy(?int $propertyId, FieldVisits $fieldVisit)
    {
        $fieldVisit = $this->resolveFieldVisit($fieldVisit, $propertyId);
        $fieldVisit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Field visit deleted successfully.',
        ]);
    }

    public function updateStatus(Request $request, ?int $propertyId, FieldVisits $fieldVisit)
    {
        $fieldVisit = $this->resolveFieldVisit($fieldVisit, $propertyId);
        $validated = $request->validate([
            'status' => 'nullable|string|in:pending,confirmed,cancelled,completed',
        ]);

        if (!array_key_exists('status', $validated)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => [
                    'status' => ['The status field is required.'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $fieldVisit->status = $validated['status'];
        $fieldVisit->save();

        return response()->json([
            'success' => true,
            'message' => 'Field visit status updated successfully.',
            'data' => $fieldVisit->fresh()->load('property'),
        ]);
    }



    public function frontTour(Request $request)
    {
        try {

            $request->validate([
                'property_id' => 'required|integer',
                'date' => 'required|date',
                'time' => 'required',
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'nullable|email',
                'message' => 'nullable|string'
            ]);

            $tour = FieldVisits::create([
                'property_id' => $request->property_id,
                'date' => $request->date,
                'time' => $request->time,
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'message' => $request->message,
                'accept_term' => $request->accept_term
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Tour booked successfully',
                'data' => $tour
            ], 201);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Error booking tour',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function resolveFieldVisit(FieldVisits $fieldVisit, ?int $propertyId): FieldVisits
    {
        if ($propertyId === null) {
            return $fieldVisit;
        }

        return FieldVisits::query()
            ->whereKey($fieldVisit->getKey())
            ->where('property_id', $propertyId)
            ->firstOrFail();
    }
}
