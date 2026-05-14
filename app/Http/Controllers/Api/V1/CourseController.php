<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::withCount('batches')->with('batches');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $courses = $query->orderBy('name')->paginate($this->perPage($request));

        return $this->paginated($courses, 'Courses retrieved successfully.');
    }

    public function store(Request $request)
    {
        $course = Course::create($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Course created successfully.',
            'data' => $course->load('batches'),
        ], Response::HTTP_CREATED);
    }

    public function show(int $id)
    {
        $course = Course::with('batches')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Course retrieved successfully.',
            'data' => $course,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $course = Course::findOrFail($id);
        $course->update($this->validated($request, true));

        return response()->json([
            'success' => true,
            'message' => 'Course updated successfully.',
            'data' => $course->fresh('batches'),
        ]);
    }

    public function destroy(int $id)
    {
        Course::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully.',
        ]);
    }

    private function validated(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes|required' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:100'],
            'duration_weeks' => [$required, 'integer', 'min:1'],
            'total_hours' => [$required, 'integer', 'min:1'],
            'delivery_mode' => [$required, 'string', 'max:50'],
            'instruction_lang' => [$required, 'string', 'max:50'],
            'skills' => ['nullable', 'string'],
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
