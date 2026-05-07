<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inquery;
use App\Services\InqueryService;
use App\Http\Resources\InqueryReasource;
use Illuminate\Support\Facades\Validator;

class InqueryController extends Controller
{

    protected InqueryService $inqueryService;
    public function __construct(InqueryService $inqueryService)
    {
        $this->inqueryService = $inqueryService;
    }

    public function index(Request $request)
    {
        $paginatedResults = $this->inqueryService->listActiveInquery($request);

        if ($paginatedResults->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No inqueries available',
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
            'message' => 'List of inquiries',
            'data' => InqueryReasource::collection($paginatedResults->items()),
            'pagination' => [
                'total' => $paginatedResults->total(),
                'per_page' => $paginatedResults->perPage(),
                'current_page' => $paginatedResults->currentPage(),
                'last_page' => $paginatedResults->lastPage(),
            ],
        ], 200);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'property_id' => 'nullable|integer',
            'inquiry_type_id' => 'nullable|integer',
            'property_type_id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'location' => 'nullable|string|max:255',
            'message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $imagePaths = [];

        if ($request->hasFile('images')) {

            foreach ($request->file('images') as $image) {

                $path = $image->store('request_posts', 'public');

                $imagePaths[] = asset('storage/' . $path);
            }
        }

        $inquery =Inquery::create([
            'from' => $request->from,
            'property_id' => $request->property_id,
            'inquiry_type_id' => $request->inquiry_type_id,
            'property_type_id' => $request->property_type_id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'preferred_location' => $request->preferred_location,
            'min_price' => $request->min_price,
            'max_price' => $request->max_price,
            'message' => $request->message,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Inquiry submitted successfully',
            'data' => $inquery
        ], 201);
    }

    public function show($id)
    {
        $inquery = Inquery::find($id);

        return response()->json([
            'status' => true,
            'message' => 'Inquiry retrieved successfully',
            'data' => $inquery
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [

            'property_id' => 'nullable|integer',
            'inquiry_type_id' => 'nullable|integer',
            'property_type_id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'preferred_location' => 'nullable|string|max:255',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $imagePaths = [];

        if ($request->hasFile('images')) {

            foreach ($request->file('images') as $image) {

                $path = $image->store('request_posts', 'public');

                $imagePaths[] = asset('storage/' . $path);
            }
        }

        $inquery = Inquery::find($id);

        $inquery->update([
            'property_id' => $request->property_id,
            'inquiry_type_id' => $request->inquiry_type_id,
            'property_type_id' => $request->property_type_id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'preferred_location' => $request->preferred_location,
            'min_price' => $request->min_price,
            'max_price' => $request->max_price,
            'message' => $request->message,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Inquiry updated successfully',
            'data' => $inquery
        ]);
    }

    public function destroy($id)
    {
        $inquery = Inquery::find($id);

        $inquery->delete();

        return response()->json([
            'status' => true,
            'message' => 'Inquiry deleted successfully',
        ]);
    }




    public function frontInquery(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'nullable|integer',
            'inquiry_type_id' => 'nullable|integer',
            'property_type_id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'location' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'from' => 'required|string|max:255',
        ]);

        $inquery = Inquery::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inquiry submitted successfully',
            'data' => $inquery
        ], 201);
    }
}
