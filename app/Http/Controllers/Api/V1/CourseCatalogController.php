<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Package;
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

        $matchedCourse = Course::with([
            'batches' => fn ($q) => $q->orderBy('price_npr')->orderBy('id'),
            'modules' => fn ($q) => $q->orderBy('module_no'),
        ])
            ->whereRaw('LOWER(course_name) LIKE ?', ["%{$normalized}%"])
            ->orWhereRaw('LOWER(course_name) LIKE ?', ["%{$course}%"])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Course catalog retrieved successfully.',
            'data' => $this->catalogPayload($matchedCourse),
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

        $courses = $courseQuery->get();
        $courseIds = $courses->pluck('id');

        return [
            'courses' => $courses,
            // Hierarchy: Course Type → Package (priced) → Batches (time slots)
            'packages' => Package::with([
                'course:id,course_name',
                'batches' => fn ($q) => $q
                    ->with('teacher:id,name')
                    ->withCount([
                        'enrollments as enrollments_count' => fn ($e) => $e
                            ->whereNull('archived_at')
                            ->whereNotIn('crm_status', ['completed', 'dropped'])
                            ->whereIn('payment_status', ['confirmed', 'fee_waived', 'under_review', 'not_verified', 'action_required'])
                            ->where(fn ($d) => $d->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString())),
                        // Flexible slots: LIVE leader bookings only (group members
                        // and expired/ended/archived bookings don't hold a slot)
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
                    ])
                    ->orderBy('class_time')
                    ->orderBy('id'),
            ])
                ->when($course !== null, fn ($q) => $q->whereIn('course_id', $courseIds))
                ->orderBy('course_id')
                ->orderBy('sort_order')
                ->get(),
            // Flat batches list kept for backward compatibility
            'batches' => Batch::with(['course:id,course_name', 'package:id,name,price_npr,is_flexible,is_group,duration_weeks'])
                ->withCount([
                    'enrollments as enrollments_count' => fn ($q) => $q
                        ->whereNull('archived_at')
                        ->whereNotIn('crm_status', ['completed', 'dropped'])
                        ->whereIn('payment_status', ['confirmed', 'fee_waived', 'under_review', 'not_verified', 'action_required'])
                        ->where(fn ($d) => $d->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString())),
                ])
                ->when($course !== null, fn ($q) => $q->whereIn('course_id', $courseIds))
                ->orderBy('price_npr')
                ->orderBy('id')
                ->get(),
        ];
    }
}
