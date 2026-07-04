<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BatchType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchTypeController extends Controller
{
    /** GET /batch-types — list all (active first, then sorted) */
    public function index(): JsonResponse
    {
        $types = BatchType::orderBy('sort_order')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data'    => $types,
        ]);
    }

    /** POST /batch-types */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:batch_types,name'],
            'description' => ['nullable', 'string'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $type = BatchType::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Batch type created successfully.',
            'data'    => $type,
        ], 201);
    }

    /** PATCH /batch-types/{batchType} */
    public function update(Request $request, BatchType $batchType): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:100', "unique:batch_types,name,{$batchType->id}"],
            'description' => ['nullable', 'string'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $batchType->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Batch type updated successfully.',
            'data'    => $batchType->fresh(),
        ]);
    }

    /** DELETE /batch-types/{batchType} */
    public function destroy(BatchType $batchType): JsonResponse
    {
        $batchType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Batch type deleted successfully.',
        ]);
    }
}
