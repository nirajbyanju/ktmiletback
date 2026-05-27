<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
    // ── Admin: full CRUD ──────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = Teacher::with('user:id,first_name,last_name,email');

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

    public function store(Request $request): JsonResponse
    {
        $teacher = Teacher::create($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Teacher created successfully.',
            'data'    => $teacher->load('user:id,first_name,last_name,email'),
        ], Response::HTTP_CREATED);
    }

    public function show(int $id): JsonResponse
    {
        $teacher = Teacher::with('user:id,first_name,last_name,email')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Teacher retrieved successfully.',
            'data'    => $teacher,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->update($this->validated($request, true));

        return response()->json([
            'success' => true,
            'message' => 'Teacher updated successfully.',
            'data'    => $teacher->fresh('user:id,first_name,last_name,email'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $teacher = Teacher::findOrFail($id);
        $this->deletePhotoFile($teacher);
        $teacher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Teacher deleted successfully.',
        ]);
    }

    // ── Teacher self-service: own profile ────────────────────────────────────

    public function myProfile(Request $request): JsonResponse
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Your teacher profile retrieved.',
            'data'    => $teacher,
        ]);
    }

    public function updateMyProfile(Request $request): JsonResponse
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();

        $data = $request->validate([
            'course'         => ['sometimes', 'required', 'string', 'max:50'],
            'available_time' => ['sometimes', 'required', 'string', 'max:50'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'email'          => ['nullable', 'email', 'max:100'],
            'notes'          => ['nullable', 'string'],
        ]);

        $teacher->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data'    => $teacher->fresh(),
        ]);
    }

    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();

        $this->deletePhotoFile($teacher);

        $path = $request->file('photo')->store("teacher_photos/{$teacher->id}", 'public');
        $teacher->update(['profile_photo' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Profile photo uploaded.',
            'data'    => ['profile_photo' => $path, 'url' => Storage::disk('public')->url($path)],
        ]);
    }

    public function deletePhoto(Request $request): JsonResponse
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();

        if (!$teacher->profile_photo) {
            return response()->json(['success' => false, 'message' => 'No profile photo to delete.'], 404);
        }

        $this->deletePhotoFile($teacher);
        $teacher->update(['profile_photo' => null]);

        return response()->json(['success' => true, 'message' => 'Profile photo deleted.']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validated(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'user_id'        => ['nullable', 'integer', 'exists:users,id'],
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

    private function deletePhotoFile(Teacher $teacher): void
    {
        if ($teacher->profile_photo && Storage::disk('public')->exists($teacher->profile_photo)) {
            Storage::disk('public')->delete($teacher->profile_photo);
        }
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->query('limit', 10), 1), 100);
    }

    private function paginated($paginator, string $message): JsonResponse
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
