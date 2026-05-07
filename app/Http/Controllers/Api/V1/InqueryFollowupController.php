<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Models\InqueryFollowup;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\V1\BaseController;
use App\Services\InqueryFollowupService;

class InqueryFollowupController extends BaseController
{
    protected InqueryFollowupService $inqueryFollowupService;
    public function __construct(InqueryFollowupService $inqueryFollowupService)
    {
        $this->inqueryFollowupService = $inqueryFollowupService;
    }

    public function index(Request $request, $inqueryId)
    {
        $paginatedResults = $this->inqueryFollowupService->listActiveInqueryFollowup($request, $inqueryId);

        if ($paginatedResults->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No inquery followups available',
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
            'message' => 'List of inquery followups',
            'data' => $paginatedResults->items(),
            'pagination' => [
                'total' => $paginatedResults->total(), // Total records
                'per_page' => $paginatedResults->perPage(), // Items per page
                'current_page' => $paginatedResults->currentPage(), // Current page 
                'last_page' => $paginatedResults->lastPage(), // Last page number
            ],
        ], 200);
    }
    public function store(Request $request, int $inquiryId)
    {
        $validator = Validator::make($request->all(), [
            'contact_method_id' => 'nullable|integer',
            'followup_status_id' => 'nullable|integer',
            'message' => 'nullable|string',
            'next_followup_date' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $inqueryFollowup = $this->inqueryFollowupService->store([
            ...$validator->validated(),
            'inquiry_id' => $inquiryId,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Inquiry followup submitted successfully',
            'data' => $inqueryFollowup
        ], 201);
    }

    public function show($id)
    {
        $inqueryFollowup = InqueryFollowup::find($id);

        return response()->json([
            'status' => true,
            'message' => 'Inquiry followup retrieved successfully',
            'data' => $inqueryFollowup
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [

            'inquiry_id' => 'nullable|integer',
            'contact_method_id' => 'nullable|integer',
            'followup_status_id' => 'nullable|integer',
            'message' => 'nullable|string',
            'next_followup_date' => 'nullable|date',
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


        $inqueryFollowup = InqueryFollowup::find($id);

        $inqueryFollowup->update([
            'inquiry_id' => $request->inquiry_id,
            'contact_method_id' => $request->contact_method_id,
            'followup_status_id' => $request->followup_status_id,
            'message' => $request->message,
            'next_followup_date' => $request->next_followup_date,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Inquiry followup updated successfully',
            'data' => $inqueryFollowup
        ], 201);
    }

    public function destroy($id)
    {
        $inqueryFollowup = InqueryFollowup::find($id);

        $inqueryFollowup->delete();

        return response()->json([
            'status' => true,
            'message' => 'Inquiry followup deleted successfully',
        ]);
    }
}
