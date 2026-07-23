<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::withCount('batches')->with(['batches', 'modules']);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where('course_name', 'LIKE', "%{$search}%");
        }

        $courses = $query->orderBy('course_name')->paginate($this->perPage($request));

        return $this->paginated($courses, 'Courses retrieved successfully.');
    }

    public function store(Request $request)
    {
        $course = Course::create($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Course created successfully.',
            'data'    => $course->load(['batches', 'modules']),
        ], Response::HTTP_CREATED);
    }

    public function show(int $id)
    {
        $course = Course::with(['batches', 'modules'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Course retrieved successfully.',
            'data'    => $course,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $course = Course::findOrFail($id);
        $course->update($this->validated($request, true));

        return response()->json([
            'success' => true,
            'message' => 'Course updated successfully.',
            'data'    => $course->fresh(['batches', 'modules']),
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
        $required = $isUpdate ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'course_name'   => [...$required, 'string', 'max:100'],
            'description'   => ['nullable', 'string'],
            'duration'      => ['nullable', 'integer', 'min:1'],
            'duration_type' => ['nullable', 'string', 'max:50'],
            'delivery_mode' => ['nullable', 'string', 'max:100'],
            'delivery'      => [...$required, Rule::in(['online', 'offline', 'hybrid'])],
            'support'       => ['nullable', 'array'],
            'instruction'   => ['nullable', 'array'],
            'schedule'      => ['nullable', 'array'],
            'features'      => ['nullable', 'array'],
        ]);
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->query('limit', 10), 1), 100);
    }

    private function paginated($paginator, string $message)
    {
        return response()->json([
            'success'    => true,
            'message'    => $message,
            'data'       => $paginator->items(),
            'pagination' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }
}
