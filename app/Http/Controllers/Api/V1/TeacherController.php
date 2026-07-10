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
    // ── Public ────────────────────────────────────────────────────────────────

    /**
     * Public teacher listing for the website "Meet Our Teachers" page.
     * Only Active teachers, and only public-safe fields (no phone/email/notes).
     */
    public function publicIndex(): JsonResponse
    {
        $teachers = Teacher::with('courses:id,course_name')
            ->where('status', 'Active')
            ->orderBy('name')
            ->get()
            ->map(fn (Teacher $t) => [
                'id'                => $t->id,
                'name'              => $t->display_name,
                'specialization'    => $t->specialization,
                'qualification'     => $t->qualification,
                'experience_years'  => $t->experience_years,
                'bio'               => $t->bio,
                'profile_photo_url' => $t->profile_photo_url,
                'courses'           => $t->courses->pluck('course_name')->values(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Teachers retrieved successfully.',
            'data'    => $teachers,
        ]);
    }

    // ── Admin: full CRUD ──────────────────────────────────────────────────────

    private function authorizeAdmin(Request $request): void
    {
        if (!$request->user()->hasAnyRole(['Super Admin', 'Admin']) && !$request->user()->can('manage_all')) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can perform this action.');
        }
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $query = Teacher::with([
            'user:id,first_name,last_name,email,phone',
            'courses:id,course_name',
        ]);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('teacher_id', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('specialization', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('course_id')) {
            $query->whereHas('courses', fn ($q) => $q->where('courses.id', $request->query('course_id')));
        }

        $teachers = $query->orderBy('teacher_id')->paginate($this->perPage($request));

        return $this->paginated($teachers, 'Teachers retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data      = $this->validated($request);
        $courseIds = $data['course_ids'] ?? [];
        unset($data['course_ids']);

        if (empty($data['teacher_id'])) {
            $data['teacher_id'] = Teacher::nextTeacherId();
        }

        $teacher = Teacher::create($data);

        if (!empty($courseIds)) {
            $teacher->courses()->sync($courseIds);
        }

        return response()->json([
            'success' => true,
            'message' => 'Teacher created successfully.',
            'data'    => $teacher->load(['user:id,first_name,last_name,email,phone', 'courses:id,course_name']),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $teacher = Teacher::with([
            'user:id,first_name,last_name,email,phone',
            'courses:id,course_name',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Teacher retrieved successfully.',
            'data'    => $teacher,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $teacher   = Teacher::findOrFail($id);
        $data      = $this->validated($request, true);
        $courseIds = array_key_exists('course_ids', $data) ? $data['course_ids'] : null;
        unset($data['course_ids']);

        $teacher->update($data);

        if ($courseIds !== null) {
            $teacher->courses()->sync($courseIds);
        }

        return response()->json([
            'success' => true,
            'message' => 'Teacher updated successfully.',
            'data'    => $teacher->fresh(['user:id,first_name,last_name,email,phone', 'courses:id,course_name']),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $teacher = Teacher::findOrFail($id);
        $this->deletePhotoFile($teacher);
        $teacher->courses()->detach();
        $teacher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Teacher deleted successfully.',
        ]);
    }

    // ── Teacher self-service: own profile ─────────────────────────────────────

    public function myProfile(Request $request): JsonResponse
    {
        $teacher = Teacher::with(['user:id,first_name,last_name,email,phone', 'courses:id,course_name'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

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
            'available_time'   => ['sometimes', 'nullable', 'string', 'max:50'],
            'available_days'   => ['sometimes', 'nullable', 'array'],
            'available_days.*' => ['string', 'in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday'],
            'available_from'   => ['sometimes', 'nullable', 'date_format:H:i'],
            'available_to'     => ['sometimes', 'nullable', 'date_format:H:i'],
            'qualification'    => ['sometimes', 'nullable', 'string', 'max:200'],
            'specialization'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'experience_years' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:60'],
            'bio'              => ['sometimes', 'nullable', 'string'],
            'phone'            => ['sometimes', 'nullable', 'string', 'max:30'],
            'email'            => ['sometimes', 'nullable', 'email', 'max:100'],
            'notes'            => ['sometimes', 'nullable', 'string'],
        ]);

        $teacher->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data'    => $teacher->fresh(['user:id,first_name,last_name,email,phone', 'courses:id,course_name']),
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

    // ── Teacher self-service: my courses, students, invoices ──────────────────

    /**
     * GET /teacher/courses
     * Returns teacher's assigned courses with their active batches and enrollment counts.
     */
    public function myCourses(Request $request): JsonResponse
    {
        $teacher = Teacher::with([
            'courses' => function ($q) {
                $q->with([
                    'batches' => function ($b) {
                        $b->select('id', 'course_id', 'batch_type', 'size_label', 'price_npr',
                                   'schedule_notes', 'class_time', 'class_link', 'is_active',
                                   'teacher_id', 'start_date', 'end_date')
                          ->withCount('enrollments as enrollment_count');
                    },
                ])->select('courses.id', 'course_name', 'description', 'is_status');
            },
        ])->where('user_id', $request->user()->id)->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Your courses retrieved.',
            'data'    => $teacher->courses,
        ]);
    }

    /**
     * PATCH /teacher/batches/{batch}
     * Teacher can update class_link and class_time of their own batch.
     */
    public function updateMyBatch(Request $request, int $batchId): JsonResponse
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();

        $batch = \App\Models\Batch::where('teacher_id', $teacher->id)
            ->findOrFail($batchId);

        $data = $request->validate([
            'class_link'  => ['nullable', 'string', 'max:500', 'url'],
            'class_time'  => ['nullable', 'string', 'max:100'],
        ]);

        $batch->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Batch updated.',
            'data'    => $batch->only(['id', 'batch_type', 'class_time', 'class_link']),
        ]);
    }

    /**
     * GET /teacher/students
     * Returns enrollments for students in batches assigned to this teacher.
     */
    public function myStudents(Request $request): JsonResponse
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();

        $query = \App\Models\Enrollment::with([
            'user:id,first_name,last_name,email,phone',
            'batch:id,course_id,batch_type,class_time,schedule_notes,teacher_id',
            'batch.course:id,course_name',
            'invoice:id,invoice_number,status,total_npr,payment_method,created_at',
        ])
        ->whereHas('batch', fn ($b) => $b->where('teacher_id', $teacher->id))
        ->latest('id');

        if ($request->filled('search')) {
            $search = '%' . $request->string('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('student_name', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhereHas('user', fn ($u) =>
                      $u->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('email', 'like', $search));
            });
        }

        if ($request->filled('crm_status')) {
            $query->where('crm_status', $request->crm_status);
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->integer('batch_id'));
        }

        $paginator = $query->paginate((int) $request->query('limit', 20));

        return response()->json([
            'success'    => true,
            'message'    => 'Students retrieved.',
            'data'       => $paginator->items(),
            'pagination' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * GET /teacher/student-invoices
     * Returns course invoices for students enrolled in this teacher's batches.
     */
    public function myStudentInvoices(Request $request): JsonResponse
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();

        $batchIds = \App\Models\Batch::where('teacher_id', $teacher->id)->pluck('id');

        $query = \App\Models\Invoice::with([
            'user:id,first_name,last_name,email,phone',
            'batch:id,course_id,batch_type,teacher_id',
            'batch.course:id,course_name',
        ])
        ->whereIn('batch_id', $batchIds)
        ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->string('search') . '%';
            $query->whereHas('user', fn ($u) =>
                $u->where('first_name', 'like', $search)
                  ->orWhere('last_name', 'like', $search)
                  ->orWhere('email', 'like', $search));
        }

        $paginator = $query->paginate((int) $request->query('limit', 20));

        return response()->json([
            'success'    => true,
            'message'    => 'Student invoices retrieved.',
            'data'       => $paginator->items(),
            'pagination' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validated(Request $request, bool $isUpdate = false): array
    {
        $sometimes = $isUpdate ? 'sometimes|' : '';

        return $request->validate([
            'user_id'          => ['nullable', 'integer', 'exists:users,id'],
            'teacher_id'       => ['nullable', 'string', 'max:20'],
            'name'             => ["{$sometimes}required", 'string', 'max:150'],
            'phone'            => ['nullable', 'string', 'max:30'],
            'email'            => ['nullable', 'email', 'max:255'],
            'available_time'   => ['nullable', 'string', 'max:50'],
            'available_days'   => ['nullable', 'array'],
            'available_days.*' => ['string', 'in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday'],
            'available_from'   => ['nullable', 'date_format:H:i'],
            'available_to'     => ['nullable', 'date_format:H:i'],
            'qualification'    => ['nullable', 'string', 'max:200'],
            'specialization'   => ['nullable', 'string', 'max:100'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'bio'              => ['nullable', 'string'],
            'status'           => ['nullable', 'string', 'in:Active,Backup,Inactive'],
            'notes'            => ['nullable', 'string'],
            'course_ids'       => ['nullable', 'array'],
            'course_ids.*'     => ['integer', 'exists:courses,id'],
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
