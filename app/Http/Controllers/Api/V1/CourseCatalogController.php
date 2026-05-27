<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Course;
use Illuminate\Http\JsonResponse;

class CourseCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Course catalog retrieved successfully.',
            'data'    => $this->catalogPayload(),
        ]);
    }

    public function show(string $course): JsonResponse
    {
        $normalized = str_replace('-', ' ', strtolower($course));

        $matchedCourse = Course::with([
            'batches'  => fn ($q) => $q->orderBy('price_npr')->orderBy('id'),
            'modules'  => fn ($q) => $q->orderBy('module_no'),
        ])
            ->whereRaw('LOWER(course_name) LIKE ?', ["%{$normalized}%"])
            ->orWhereRaw('LOWER(course_name) LIKE ?', ["%{$course}%"])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Course catalog retrieved successfully.',
            'data'    => $this->catalogPayload($matchedCourse),
        ]);
    }

    private function catalogPayload(?Course $course = null): array
    {
        $courseQuery = Course::with([
            'batches' => fn ($q) => $q->orderBy('price_npr')->orderBy('id'),
            'modules' => fn ($q) => $q->orderBy('module_no'),
        ])->orderBy('course_name');

        if ($course !== null) {
            $courseQuery->whereKey($course->id);
        }

        $courses    = $courseQuery->get();
        $courseIds  = $courses->pluck('id');

        return [
            'courses' => $courses,
            'batches' => Batch::with('course:id,course_name')
                ->when($course !== null, fn ($q) => $q->whereIn('course_id', $courseIds))
                ->orderBy('price_npr')
                ->orderBy('id')
                ->get(),
        ];
    }
}
