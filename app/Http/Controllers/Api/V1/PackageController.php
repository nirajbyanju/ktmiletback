<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/** Admin CRUD for catalog packages (Course Type → Package → Batches). */
class PackageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Package::with('course:id,course_name')
            ->withCount('batches')
            ->orderBy('course_id')
            ->orderBy('sort_order');

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Packages retrieved successfully.',
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $package = Package::create($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Package created successfully.',
            'data' => $package->load('course:id,course_name'),
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $package = Package::findOrFail($id);
        $package->update($this->validated($request, true, $id));

        return response()->json([
            'success' => true,
            'message' => 'Package updated successfully.',
            'data' => $package->fresh('course:id,course_name'),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $package = Package::withCount('batches')->findOrFail($id);

        if ($package->batches_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'This package still has batches. Move or delete its batches first.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $package->delete();

        return response()->json(['success' => true, 'message' => 'Package deleted successfully.']);
    }

    private function validated(Request $request, bool $isUpdate = false, ?int $id = null): array
    {
        $required = $isUpdate ? ['sometimes', 'required'] : ['required'];
        $courseId = $request->input('course_id');
        if ($isUpdate && ! $courseId && $id) {
            $courseId = Package::where('id', $id)->value('course_id');
        }

        $nameRule = Rule::unique('packages', 'name')->where(function ($query) use ($courseId) {
            return $query->where('course_id', $courseId);
        });

        if ($isUpdate && $id) {
            $nameRule->ignore($id);
        }

        return $request->validate([
            'course_id' => [...$required, 'integer', 'exists:courses,id'],
            'name' => [...$required, 'string', 'max:100', $nameRule],
            'description' => ['nullable', 'string', 'max:2000'],
            'size_label' => ['nullable', 'string', 'max:100'],
            'schedule_notes' => ['nullable', 'string', 'max:255'],
            'duration_weeks' => ['nullable', 'integer', 'min:1', 'max:104'],
            'price_npr' => ['nullable', 'numeric', 'min:0'],
            'is_flexible' => ['nullable', 'boolean'],
            'is_group' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || (! $user->hasAnyRole(['Super Admin', 'Admin']) && ! $user->can('manage_all'))) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can manage packages.');
        }
    }
}
