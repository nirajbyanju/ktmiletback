<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdditionalService;
use App\Models\Batch;
use App\Models\Course;
use App\Models\SkillModule;
use App\Models\SupportChannel;
use Illuminate\Http\JsonResponse;

class CourseCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Course catalog retrieved successfully.',
            'data' => $this->catalogPayload(),
        ]);
    }

    public function show(string $course): JsonResponse
    {
        $normalized = str_replace('-', ' ', strtolower($course));

        $matchedCourse = Course::with(['batches' => fn ($query) => $query->orderBy('price_npr')->orderBy('id')])
            ->whereRaw('LOWER(name) LIKE ?', ["%{$normalized}%"])
            ->orWhereRaw('LOWER(name) LIKE ?', ["%{$course}%"])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Course catalog retrieved successfully.',
            'data' => $this->catalogPayload($matchedCourse),
        ]);
    }

    private function catalogPayload(?Course $course = null): array
    {
        $courseQuery = Course::with(['batches' => fn ($query) => $query->orderBy('price_npr')->orderBy('id')])
            ->orderBy('name');

        if ($course !== null) {
            $courseQuery->whereKey($course->id);
        }

        $courses = $courseQuery->get();
        $courseIds = $courses->pluck('id');

        return [
            'courses' => $courses,
            'batches' => Batch::with('course:id,name')
                ->when($course !== null, fn ($query) => $query->whereIn('course_id', $courseIds))
                ->orderBy('price_npr')
                ->orderBy('id')
                ->get(),
            'support_channels' => SupportChannel::orderBy('id')->get(),
            'skills_modules' => SkillModule::orderBy('id')->get(),
            'additional_services' => AdditionalService::orderBy('id')->get(),
        ];
    }
}
