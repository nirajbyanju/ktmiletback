<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Enrollment::with(['batch.course:id,name', 'invoice:id,invoice_number,status,total_npr']);

        if (!$this->canManageAll($request)) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->integer('batch_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where('student_name', 'LIKE', "%{$search}%");
        }

        $enrollments = $query->latest('id')->paginate($this->perPage($request));

        return $this->paginated($enrollments, 'Enrollments retrieved successfully.');
    }

    public function store(Request $request)
    {
        $this->authorizeManageAll($request);

        $enrollment = Enrollment::create($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Enrollment created successfully.',
            'data' => $enrollment->load('batch.course:id,name'),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, int $id)
    {
        $enrollment = Enrollment::with('batch.course:id,name')->findOrFail($id);

        if (!$this->canManageAll($request) && $enrollment->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot access this enrollment.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Enrollment retrieved successfully.',
            'data' => $enrollment,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeManageAll($request);

        $enrollment = Enrollment::findOrFail($id);
        $enrollment->update($this->validated($request, true));

        return response()->json([
            'success' => true,
            'message' => 'Enrollment updated successfully.',
            'data' => $enrollment->fresh('batch.course:id,name'),
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $this->authorizeManageAll($request);

        Enrollment::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Enrollment deleted successfully.',
        ]);
    }

    private function validated(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes|required' : 'required';

        return $request->validate([
            'student_name' => [$required, 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'batch_id' => [$required, 'integer', 'exists:batches,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'enrollment_date' => ['nullable', 'date'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:30'],
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

    private function canManageAll(Request $request): bool
    {
        return $request->user()->hasAnyRole(['Super Admin', 'Admin']) || $request->user()->can('manage_all');
    }

    private function authorizeManageAll(Request $request): void
    {
        if (!$this->canManageAll($request)) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can manage enrollments.');
        }
    }
}
