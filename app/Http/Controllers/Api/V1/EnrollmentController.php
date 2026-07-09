<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EnrollmentController extends Controller
{
    public function __construct(private readonly AdminNotificationService $notifications)
    {
    }

    // ── Student: own enrollments ───────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Enrollment::with([
            'batch:id,course_id,batch_type,class_time,class_link,start_date,end_date,schedule_notes,min_size,max_size,teacher_id,is_active',
            'batch.course:id,course_name',
            'batch.teacher:id,name,email,phone',
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
        $enrollment = Enrollment::with([
            'batch:id,course_id,batch_type,class_time,class_link,start_date,end_date,schedule_notes,min_size,max_size,teacher_id',
            'batch.course:id,course_name',
            'batch.teacher:id,name,email,phone',
            'invoice:id,invoice_number,status,total_npr',
        ])->findOrFail($id);

        if (!$this->canManageAll($request) && $enrollment->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot access this enrollment.');
        }

        return response()->json(['success' => true, 'message' => 'Enrollment retrieved.', 'data' => $enrollment]);
    }

    public function store(Request $request)
    {
        $this->authorizeManageAll($request);

        $validated = $this->validated($request);

        // ── Duplicate guard (admin path) ───────────────────────────────────────
        // Prevent admins from accidentally creating a second active enrollment
        // for the same student in the same course.
        if (!empty($validated['user_id']) && !empty($validated['batch_id'])) {
            $batch    = Batch::findOrFail($validated['batch_id']);
            $courseId = $batch->course_id;
            $userId   = $validated['user_id'];

            $today = now()->toDateString();

            // Check A: confirmed (paid) enrollment only — prospect/pending do not block
            $hasActiveEnrollment = Enrollment::where('user_id', $userId)
                ->where('payment_status', 'confirmed')
                ->whereHas('batch', function ($q) use ($courseId, $today) {
                    $q->where('course_id', $courseId)
                      ->where(function ($q2) use ($today) {
                          $q2->whereNull('end_date')
                             ->orWhere('end_date', '>=', $today);
                      });
                })
                ->exists();

            // Check B: paid invoice for same course (unexpired batch)
            $hasPaidCourseInvoice = Invoice::where('user_id', $userId)
                ->where('status', Invoice::STATUS_PAID)
                ->whereHas('batch', function ($q) use ($courseId, $today) {
                    $q->where('course_id', $courseId)
                      ->where(function ($q2) use ($today) {
                          $q2->whereNull('end_date')
                             ->orWhere('end_date', '>=', $today);
                      });
                })
                ->exists();

            if ($hasActiveEnrollment || $hasPaidCourseInvoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'This student already has an active enrollment in this course. They can re-enroll once the current enrollment expires.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $enrollment = Enrollment::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment created successfully.',
            'data'    => $enrollment->load('batch.course:id,course_name'),
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
            'data'    => $enrollment->fresh('batch.course:id,course_name'),
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
                'paid'          => Enrollment::where('payment_status', 'confirmed')->count(),
                'payment_due'   => Enrollment::whereIn('payment_status', ['action_required', 'under_review', 'not_verified'])->count(),
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
            'batch:id,course_id,batch_type,class_time,class_link,start_date,end_date,schedule_notes,is_active,teacher_id',
            'batch.course:id,course_name',
            'batch.teacher:id,name,email,phone',
            'invoice:id,invoice_number,status,total_npr,payment_screenshot_path,crm_payment_status,screenshot_uploaded_at',
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

        $enrollment = Enrollment::with('user')->findOrFail($id);
        $oldCrmStatus = $enrollment->crm_status;

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

        // Fire notification if crm_status changed (single fresh() call)
        $freshEnrollment = $enrollment->fresh(['user', 'batch.course']);
        $newCrmStatus    = $freshEnrollment?->crm_status;
        if ($oldCrmStatus !== $newCrmStatus && $newCrmStatus !== null) {
            $this->notifications->notifyEnrollmentStatusChanged(
                $freshEnrollment,
                (string) $oldCrmStatus,
                (string) $newCrmStatus,
                $request->user(),
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Student record updated.',
            'data'    => $enrollment->fresh(['user:id,first_name,last_name,email,phone', 'batch.course:id,course_name']),
        ]);
    }

    // ── Student: change batch (only when action_required) ────────────────────

    public function changeBatch(Request $request, int $id): JsonResponse
    {
        $enrollment = Enrollment::with(['invoice', 'batch.course'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        if ($enrollment->payment_status !== 'action_required') {
            return response()->json([
                'success' => false,
                'message' => 'Batch can only be changed when payment status is Action Required.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
        ]);

        if ((int) $enrollment->batch_id === (int) $data['batch_id']) {
            return response()->json([
                'success' => false,
                'message' => 'You are already enrolled in this batch.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $newBatch = Batch::with('course')->where('is_active', true)->findOrFail($data['batch_id']);

        // Cancel old invoice if it is still unpaid
        $oldInvoice = $enrollment->invoice;
        if ($oldInvoice && $oldInvoice->status === Invoice::STATUS_UNPAID) {
            $oldInvoice->update(['status' => Invoice::STATUS_CANCELLED]);
        }

        // Compute pricing for new batch
        $subtotal = (float) ($newBatch->price_npr ?? 0);
        $discount = 0.0;
        if ($newBatch->offer_label && $newBatch->discount_type && $newBatch->discount_value) {
            $value    = (float) $newBatch->discount_value;
            $discount = $newBatch->discount_type === 'percent'
                ? round($subtotal * min($value, 100) / 100, 2)
                : min($subtotal, $value);
        }
        $afterDiscount = max(0.0, $subtotal - $discount);
        $taxable       = round($afterDiscount * 100 / 113, 2);
        $tax           = round($afterDiscount - $taxable, 2);

        // Generate unique invoice number
        do {
            $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        } while (Invoice::where('invoice_number', $invoiceNumber)->exists());

        // Create new invoice for new batch
        $newInvoice = Invoice::create([
            'invoice_number'     => $invoiceNumber,
            'user_id'            => $request->user()->id,
            'batch_id'           => $newBatch->id,
            'subtotal_npr'       => $subtotal,
            'discount_npr'       => $discount,
            'tax_npr'            => $tax,
            'total_npr'          => $afterDiscount,
            'status'             => Invoice::STATUS_UNPAID,
            'crm_payment_status' => 'action_required',
            'payment_method'     => 'bank_qr',
            'invoice_date'       => now()->toDateString(),
            'due_date'           => now()->addDays(3)->toDateString(),
        ]);

        // Update enrollment: new batch + new invoice
        $enrollment->update([
            'batch_id'       => $newBatch->id,
            'invoice_id'     => $newInvoice->id,
            'payment_status' => 'action_required',
            'crm_status'     => 'prospect',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Batch changed successfully. New invoice generated.',
            'data'    => [
                'enrollment' => $enrollment->fresh([
                    'batch:id,course_id,batch_type,class_time,class_link,start_date,end_date,schedule_notes,teacher_id,is_active',
                    'batch.course:id,course_name',
                    'invoice:id,invoice_number,status,total_npr',
                ]),
                'invoice' => $newInvoice->load(['batch.course:id,course_name']),
            ],
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
