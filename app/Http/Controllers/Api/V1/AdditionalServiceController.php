<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdditionalService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdditionalServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = AdditionalService::query();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('service_name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $services = $query->orderBy('id')->paginate($this->perPage($request));

        return $this->paginated($services, 'Additional services retrieved successfully.');
    }

    public function store(Request $request)
    {
        $service = AdditionalService::create($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Additional service created successfully.',
            'data' => $service,
        ], Response::HTTP_CREATED);
    }

    public function show(int $id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Additional service retrieved successfully.',
            'data' => AdditionalService::findOrFail($id),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $service = AdditionalService::findOrFail($id);
        $service->update($this->validated($request, true));

        return response()->json([
            'success' => true,
            'message' => 'Additional service updated successfully.',
            'data' => $service->fresh(),
        ]);
    }

    public function destroy(int $id)
    {
        AdditionalService::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Additional service deleted successfully.',
        ]);
    }

    private function validated(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes|required' : 'required';

        return $request->validate([
            'service_name' => [$required, 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_add_on' => ['sometimes', 'boolean'],
            'price_npr' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->query('limit', 10), 1), 100);
    }

    private function paginated($paginator, string $message)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
