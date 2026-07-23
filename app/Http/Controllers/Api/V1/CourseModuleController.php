<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CourseModuleController extends Controller
{
    public function index(int $courseId): JsonResponse
    {
        Course::findOrFail($courseId);

        $modules = CourseModule::where('course_id', $courseId)
            ->orderBy('module_no')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Modules retrieved successfully.',
            'data'    => $modules,
        ]);
    }

    public function store(Request $request, int $courseId): JsonResponse
    {
        Course::findOrFail($courseId);

        $data = $request->validate([
            'module_no'   => ['required', 'integer', 'min:1'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $module = CourseModule::create([
            'course_id'   => $courseId,
            'module_no'   => $data['module_no'],
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Module created successfully.',
            'data'    => $module,
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, int $courseId, int $id): JsonResponse
    {
        $module = CourseModule::where('course_id', $courseId)->findOrFail($id);

        $data = $request->validate([
            'module_no'   => ['sometimes', 'required', 'integer', 'min:1'],
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $module->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Module updated successfully.',
            'data'    => $module->fresh(),
        ]);
    }

    public function destroy(int $courseId, int $id): JsonResponse
    {
        $module = CourseModule::where('course_id', $courseId)->findOrFail($id);
        $module->delete();

        return response()->json([
            'success' => true,
            'message' => 'Module deleted successfully.',
        ]);
    }
}
