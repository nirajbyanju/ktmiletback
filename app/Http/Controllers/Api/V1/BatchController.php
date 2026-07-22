<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        $query = Batch::with(['course:id,course_name', 'teacher:id,name', 'package:id,name,price_npr,is_flexible,is_group,duration_weeks'])
            ->withCount([
                // Same definition as the public catalog: LIVE leader bookings only
                // (group members and expired/ended/archived bookings don't hold a slot)
                'enrollments as booked_count' => fn ($e) => $e
                    ->whereNull('parent_enrollment_id')
                    ->whereNull('archived_at')
                    ->whereNotIn('crm_status', ['completed', 'dropped'])
                    ->whereIn('payment_status', ['confirmed', 'fee_waived'])
                    ->where(fn ($d) => $d->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString())),
                'enrollments as reserved_count' => fn ($e) => $e
                    ->whereNull('parent_enrollment_id')
                    ->whereNull('archived_at')
                    ->whereNotIn('crm_status', ['completed', 'dropped'])
                    ->whereIn('payment_status', ['action_required', 'under_review', 'not_verified'])
                    ->where(fn ($d) => $d->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString())),
            ]);

        if ($request->filled('package_id')) {
            $query->where('package_id', $request->integer('package_id'));
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('batch_type', 'LIKE', "%{$search}%")
                    ->orWhereHas('course', fn ($q) => $q->where('course_name', 'LIKE', "%{$search}%"));
            });
        }

        $batches = $query->orderBy('id')->paginate($this->perPage($request));

        return $this->paginated($batches, 'Batches retrieved successfully.');
    }

    public function store(Request $request)
    {
        $batch = Batch::create($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Batch created successfully.',
            'data' => $batch->load('course:id,course_name'),
        ], Response::HTTP_CREATED);
    }

    public function show(int $id)
    {
        $batch = Batch::with(['course:id,course_name', 'enrollments'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Batch retrieved successfully.',
            'data' => $batch,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $batch = Batch::findOrFail($id);
        $batch->update($this->validated($request, true));

        return response()->json([
            'success' => true,
            'message' => 'Batch updated successfully.',
            'data' => $batch->fresh('course:id,course_name'),
        ]);
    }

    public function destroy(int $id)
    {
        Batch::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Batch deleted successfully.',
        ]);
    }

    private function validated(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? ['sometimes', 'required'] : ['required'];

        $validated = $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'package_id' => $isUpdate
                ? ['sometimes', 'nullable', 'integer', 'exists:packages,id']
                : ['required', 'integer', 'exists:packages,id'],
            'batch_type' => ['nullable', 'string', 'max:80'],
            'min_size' => ['nullable', 'integer', 'min:0'],
            'max_size' => ['nullable', 'integer', 'min:0', 'gte:min_size'],
            'price_npr' => ['nullable', 'numeric', 'min:0'],
            'schedule_notes' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'class_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'class_link' => ['nullable', 'url'],
            'is_active' => ['sometimes', 'boolean'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ]);

        // A batch belongs to a package; course + display name follow the package
        // automatically so old batch_type-based displays keep working.
        if (! empty($validated['package_id'])) {
            $package = Package::findOrFail($validated['package_id']);
            $validated['course_id'] = $package->course_id;
            $validated['batch_type'] = $package->name;
        }

        return $validated;
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->query('limit', 10), 1), 100);
    }

    private function paginated(LengthAwarePaginator $paginator, string $message)
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
