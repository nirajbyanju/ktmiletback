<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $query = Teacher::query();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('teacher_id', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('course', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $teachers = $query->orderBy('teacher_id')->paginate($this->perPage($request));

        return $this->paginated($teachers, 'Teachers retrieved successfully.');
    }

    public function store(Request $request)
    {
        $teacher = Teacher::create($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Teacher created successfully.',
            'data'    => $teacher,
        ], Response::HTTP_CREATED);
    }

    public function show(int $id)
    {
        $teacher = Teacher::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Teacher retrieved successfully.',
            'data'    => $teacher,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->update($this->validated($request, true));

        return response()->json([
            'success' => true,
            'message' => 'Teacher updated successfully.',
            'data'    => $teacher->fresh(),
        ]);
    }

    public function destroy(int $id)
    {
        Teacher::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Teacher deleted successfully.',
        ]);
    }

    private function validated(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'teacher_id'     => [...$required, 'string', 'max:20'],
            'name'           => [...$required, 'string', 'max:100'],
            'course'         => [...$required, 'string', 'max:50'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'email'          => ['nullable', 'email', 'max:100'],
            'available_time' => [...$required, 'string', 'max:50'],
            'status'         => ['nullable', 'string', 'in:Active,Backup,Inactive'],
            'notes'          => ['nullable', 'string'],
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
