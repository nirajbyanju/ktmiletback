<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class EnrollmentController extends Controller
{
    // ── Student: own enrollments ───────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Enrollment::with([
            'batch.course:id,name',
            'invoice:id,invoice_number,status,total_npr',
        ]);

        if (!$this->canManageAll($request)) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->integer('batch_id'));
        }

        if ($request->filled('search')) {
            $query->where('student_name', 'LIKE', '%' . $request->string('search') . '%');
        }

        return $this->paginated(
            $query->latest('id')->paginate($this->perPage($request)),
            'Enrollments retrieved successfully.'
        );
    }

    public function show(Request $request, int $id)
    {
        $enrollment = Enrollment::with('batch.course:id,name')->findOrFail($id);

        if (!$this->canManageAll($request) && $enrollment->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot access this enrollment.');
        }

        return response()->json(['success' => true, 'message' => 'Enrollment retrieved.', 'data' => $enrollment]);
    }

    public function store(Request $request)
    {
        $this->authorizeManageAll($request);

        $enrollment = Enrollment::create($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Enrollment created successfully.',
            'data'    => $enrollment->load('batch.course:id,name'),
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeManageAll($request);

        $enrollment = Enrollment::findOrFail($id);
        $enrollment->update($this->validated($request, true));

        return response()->json([
            'success' => true,
            'message' => 'Enrollment updated successfully.',
            'data'    => $enrollment->fresh('batch.course:id,name'),
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $this->authorizeManageAll($request);
        Enrollment::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Enrollment deleted successfully.']);
    }

    // ── Admin: full student management ────────────────────────────────────────

    public function adminStats(Request $request)
    {
        $this->authorizeManageAll($request);

        return response()->json([
            'success' => true,
            'data' => [
                'total'         => Enrollment::count(),
                'paid'          => Enrollment::where('payment_status', 'paid')->count(),
                'payment_due'   => Enrollment::where('payment_status', 'pending')->count(),
                'completed'     => Enrollment::where('crm_status', 'completed')->count(),
                'cert_eligible' => Enrollment::where('certificate_eligible', true)->count(),
            ],
        ]);
    }

    public function adminIndex(Request $request)
    {
        $this->authorizeManageAll($request);

        $query = Enrollment::with([
            'user:id,first_name,last_name,email,phone',
            'batch:id,course_id,batch_type,class_time,is_active',
            'batch.course:id,name',
            'invoice:id,invoice_number,status,total_npr',
        ])->latest('id');

        if ($request->filled('crm_status')) {
            $query->where('crm_status', $request->crm_status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->integer('batch_id'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('student_name', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('phone', 'like', $search)
                  ->orWhereHas('user', fn ($u) => $u->where('email', 'like', $search)
                      ->orWhere('first_name', 'like', $search)
                      ->orWhere('last_name', 'like', $search));
            });
        }

        return $this->paginated($query->paginate($this->perPage($request)), 'Students retrieved successfully.');
    }

    public function adminUpdate(Request $request, int $id)
    {
        $this->authorizeManageAll($request);

        $enrollment = Enrollment::findOrFail($id);

        $validated = $request->validate([
            'lead_id'               => 'nullable|integer',
            'student_name'          => 'sometimes|required|string|max:255',
            'phone'                 => 'nullable|string|max:20',
            'email'                 => 'nullable|email|max:255',
            'teacher'               => 'nullable|string|max:255',
            'attendance_percentage' => 'nullable|numeric|min:0|max:100',
            'certificate_eligible'  => 'nullable|boolean',
            'payment_status'        => ['nullable', Rule::in(Enrollment::PAYMENT_STATUSES)],
            'crm_status'            => ['nullable', Rule::in(Enrollment::CRM_STATUSES)],
            'notes'                 => 'nullable|string|max:5000',
            'enrollment_date'       => 'nullable|date',
            'batch_id'              => 'nullable|integer|exists:batches,id',
            'status'                => 'nullable|string|max:30',
        ]);

        $enrollment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Student record updated.',
            'data'    => $enrollment->fresh(['user:id,first_name,last_name,email,phone', 'batch.course:id,name']),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validated(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'student_name'   => [...$required, 'string', 'max:255'],
            'user_id'        => ['nullable', 'integer', 'exists:users,id'],
            'batch_id'       => [...$required, 'integer', 'exists:batches,id'],
            'invoice_id'     => ['nullable', 'integer', 'exists:invoices,id'],
            'enrollment_date'=> ['nullable', 'date'],
            'amount_paid'    => ['nullable', 'numeric', 'min:0'],
            'status'         => ['nullable', 'string', 'max:30'],
        ]);
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->query('limit', 20), 1), 100);
    }

    private function paginated($paginator, string $message)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $paginator,
        ]);
    }

    private function canManageAll(Request $request): bool
    {
        return $request->user()->hasAnyRole(['Super Admin', 'Admin']) || $request->user()->can('manage_all');
    }

    private function authorizeManageAll(Request $request): void
    {
        if (!$this->canManageAll($request)) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can perform this action.');
        }
    }
}
