<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Services\AdminNotificationService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly AdminNotificationService $notifications,
        private readonly InvoiceService $invoices,
    ) {}

    // ── Student: own enrollments ───────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Enrollment::with([
            'batch:id,course_id,package_id,batch_type,class_time,class_link,start_date,end_date,schedule_notes,min_size,max_size,teacher_id,is_active',
            'batch.course:id,course_name',
            'batch.teacher:id,name,email,phone',
            'invoice:id,invoice_number,status,total_npr',
        ])->withCount([
            // Attendance breakdown for the dashboard bar: present out of total marked.
            'attendances as attendance_present_count' => fn ($q) => $q->where('status', 'present'),
            'attendances as attendance_total_count',
        ]);

        $query->whereNull('archived_at');

        if (! $this->canManageAll($request)) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->integer('batch_id'));
        }

        if ($request->filled('search')) {
            $query->where('student_name', 'LIKE', '%'.$request->string('search').'%');
        }

        $paginator = $query->latest('id')->paginate($this->perPage($request));

        // Students only receive the class link once payment is confirmed.
        if (! $this->canManageAll($request)) {
            $paginator->getCollection()->each(fn ($enr) => $this->hideClassLinkIfUnpaid($enr));
        }

        return $this->paginated($paginator, 'Enrollments retrieved successfully.');
    }

    public function show(Request $request, int $id)
    {
        $enrollment = Enrollment::with([
            'batch:id,course_id,package_id,batch_type,class_time,class_link,start_date,end_date,schedule_notes,min_size,max_size,teacher_id',
            'batch.course:id,course_name',
            'batch.teacher:id,name,email,phone',
            'invoice:id,invoice_number,status,total_npr',
        ])->findOrFail($id);

        if (! $this->canManageAll($request) && $enrollment->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot access this enrollment.');
        }

        if (! $this->canManageAll($request)) {
            $this->hideClassLinkIfUnpaid($enrollment);
        }

        return response()->json(['success' => true, 'message' => 'Enrollment retrieved.', 'data' => $enrollment]);
    }

    /**
     * Student: switch an unpaid enrollment to a different active batch.
     * The linked unpaid invoice is re-priced for the new batch; paid or
     * under-review enrollments cannot be changed.
     */
    public function changeBatch(Request $request, int $id)
    {
        $enrollment = Enrollment::with(['invoice', 'batch'])->findOrFail($id);

        if (! $this->canManageAll($request) && $enrollment->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot modify this enrollment.');
        }

        $isConfirmed = $enrollment->payment_status === 'confirmed';
        $isUnderReview = $enrollment->payment_status === 'under_review';

        if (! in_array($enrollment->payment_status, ['action_required', 'not_verified', 'under_review']) && ! $isConfirmed) {
            return response()->json([
                'success' => false,
                'message' => 'This enrollment can no longer be changed. Please contact support.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($isConfirmed) {
            if (in_array($enrollment->crm_status, ['completed', 'dropped']) || $enrollment->archived_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'This enrollment can no longer be changed. Please contact support.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            if ($enrollment->end_date && now()->startOfDay()->gt($enrollment->end_date)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your course period has ended, so the time slot can no longer be changed.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        if ($isConfirmed || $isUnderReview) {
            if ($enrollment->start_date && now()->startOfDay()->gte($enrollment->start_date)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your class has already started, so the time slot can no longer be changed.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
        ]);

        $batch = Batch::with('course:id,course_name')->findOrFail($validated['batch_id']);

        // Paid or under-review students may only switch between time slots of the SAME package
        // (same price) — package changes after payment go through admin.
        if (($isConfirmed || $isUnderReview) && (int) $batch->package_id !== (int) $enrollment->batch?->package_id) {
            return response()->json([
                'success' => false,
                'message' => 'After payment or receipt upload you can only switch time slots within your package. To change package, please contact our team.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $batch->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This batch is not open for enrollment.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ((int) $batch->id === (int) $enrollment->batch_id) {
            return response()->json([
                'success' => false,
                'message' => 'You are already enrolled in this batch.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Slot capacity: block switching into a batch that is already full
        // (live leader bookings only — members and ended bookings don't count)
        if ($batch->max_size) {
            $taken = Enrollment::where('batch_id', $batch->id)
                ->where('id', '!=', $enrollment->id)
                ->whereNull('parent_enrollment_id')
                ->whereNull('archived_at')
                ->whereNotIn('crm_status', ['completed', 'dropped'])
                ->whereIn('payment_status', ['action_required', 'under_review', 'not_verified', 'confirmed', 'fee_waived'])
                ->where(fn ($d) => $d->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString()))
                ->count();

            if ($taken >= $batch->max_size) {
                return response()->json([
                    'success' => false,
                    'message' => 'This time slot is already taken. Please choose another slot.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $invoice = DB::transaction(function () use ($enrollment, $batch, $request, $isConfirmed) {
            $invoice = $enrollment->invoice;

            if ($isConfirmed) {
                // Paid time-slot swap: same package & price — invoice unchanged
            } elseif ($invoice && $invoice->status === Invoice::STATUS_UNPAID) {
                $invoice = $this->invoices->retargetBatchInvoice($invoice, $batch);
            } else {
                $invoice = $this->invoices->createForBatch($batch, $request->user());
                // createForBatch auto-creates a placeholder enrollment for the new
                // invoice — remove it so the student keeps a single record.
                Enrollment::where('invoice_id', $invoice->id)
                    ->where('id', '!=', $enrollment->id)
                    ->forceDelete();
                $enrollment->invoice_id = $invoice->id;
            }

            $enrollment->batch_id = $batch->id;
            $enrollment->save();

            // Group booking: members move to the new time slot with the leader
            Enrollment::where('parent_enrollment_id', $enrollment->id)
                ->update(['batch_id' => $batch->id]);

            return $invoice;
        });

        $fresh = $enrollment->fresh([
            'batch:id,course_id,package_id,batch_type,class_time,class_link,start_date,end_date,schedule_notes,teacher_id',
            'batch.course:id,course_name',
            'invoice:id,invoice_number,status,total_npr',
        ]);
        $this->hideClassLinkIfUnpaid($fresh);

        return response()->json([
            'success' => true,
            'message' => 'Batch changed successfully.',
            'data' => [
                'enrollment' => $fresh,
                'invoice' => $invoice,
            ],
        ]);
    }

    /**
     * Student: cancel their own UNPAID class booking. The invoice is cancelled
     * and the booking archived (admin keeps history in the Archived view);
     * the course immediately unlocks for a fresh booking.
     */
    public function cancelRequest(Request $request, int $id)
    {
        $enrollment = Enrollment::with('invoice')->findOrFail($id);

        if ($enrollment->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot modify this enrollment.');
        }

        if (! in_array($enrollment->payment_status, ['action_required', 'not_verified']) || $enrollment->archived_at) {
            return response()->json([
                'success' => false,
                'message' => 'Only unpaid bookings can be cancelled. If you have already paid, please contact our team.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::transaction(function () use ($enrollment) {
            if ($enrollment->invoice && $enrollment->invoice->status === Invoice::STATUS_UNPAID) {
                $enrollment->invoice->update(['status' => Invoice::STATUS_CANCELLED]);
            }
            $enrollment->forceFill(['archived_at' => now()])->save();
            // Group booking: members follow the leader
            Enrollment::where('parent_enrollment_id', $enrollment->id)
                ->update(['archived_at' => now()]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Your booking request has been cancelled. You can enroll again anytime.',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManageAll($request);

        $validated = $this->validated($request);

        // ── Duplicate guard (admin path) ───────────────────────────────────────
        // Prevent admins from accidentally creating a second active enrollment
        // for the same student in the same course.
        if (! empty($validated['user_id']) && ! empty($validated['batch_id'])) {
            $batch = Batch::findOrFail($validated['batch_id']);
            $courseId = $batch->course_id;
            $userId = $validated['user_id'];

            $today = now()->toDateString();

            // Check A: confirmed (paid) enrollment only — prospect/pending do not block.
            // A student whose own end_date has passed (or cycle completed/dropped)
            // may be re-enrolled.
            $hasActiveEnrollment = Enrollment::where('user_id', $userId)
                ->whereIn('payment_status', ['confirmed', 'fee_waived'])
                ->whereNull('archived_at')
                ->whereNotIn('crm_status', ['completed', 'dropped'])
                ->where(function ($q) use ($today) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $today);
                })
                ->whereHas('batch', function ($q) use ($courseId, $today) {
                    $q->where('course_id', $courseId)
                        ->where(function ($q2) use ($today) {
                            $q2->whereNull('end_date')
                                ->orWhere('end_date', '>=', $today);
                        });
                })
                ->exists();

            // Check B: paid invoice for same course (unexpired batch),
            // ignoring invoices whose enrollment is completed, dropped, or expired
            $hasPaidCourseInvoice = Invoice::where('user_id', $userId)
                ->where('status', Invoice::STATUS_PAID)
                ->whereDoesntHave('enrollment', function ($q) use ($today) {
                    $q->whereIn('crm_status', ['completed', 'dropped'])
                        ->orWhere('end_date', '<', $today)
                        ->orWhereNotNull('archived_at');
                })
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
            'data' => $enrollment->load('batch.course:id,course_name'),
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
            'data' => $enrollment->fresh('batch.course:id,course_name'),
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
                'total' => Enrollment::whereNull('archived_at')->count(),
                'paid' => Enrollment::whereNull('archived_at')->where('payment_status', 'confirmed')->count(),
                'payment_due' => Enrollment::whereNull('archived_at')->whereIn('payment_status', ['action_required', 'under_review', 'not_verified'])->count(),
                'completed' => Enrollment::whereNull('archived_at')->where('crm_status', 'completed')->count(),
                'cert_eligible' => Enrollment::whereNull('archived_at')->where('certificate_eligible', true)->count(),
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

        // Archived records are hidden by default; ?archived=1 shows only them
        $request->boolean('archived')
            ? $query->whereNotNull('archived_at')
            : $query->whereNull('archived_at');

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
            $search = '%'.$request->search.'%';
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

        // attendance_percentage is intentionally NOT accepted here — it is derived
        // automatically from the attendance sheet (present/absent marks) and must
        // not be hand-edited, so it stays the single source of truth.
        $validated = $request->validate([
            'lead_id' => 'nullable|integer',
            'student_name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'teacher' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'preferred_schedule' => 'nullable|string|max:255',
            'certificate_eligible' => 'nullable|boolean',
            'payment_status' => ['nullable', Rule::in(Enrollment::PAYMENT_STATUSES)],
            'crm_status' => ['nullable', Rule::in(Enrollment::CRM_STATUSES)],
            'notes' => 'nullable|string|max:5000',
            'enrollment_date' => 'nullable|date',
            'batch_id' => 'nullable|integer|exists:batches,id',
            'status' => 'nullable|string|max:30',
        ]);

        // Student's course completes automatically when their own end date passes.
        if (! empty($validated['end_date'])
            && $validated['end_date'] < now()->toDateString()
            && empty($validated['crm_status'])) {
            $validated['crm_status'] = 'completed';
        }

        // Confirming payment here should keep the CRM status in sync: a paid (or
        // fee-waived) student is active. Matches the invoice-verification flow, so
        // "Confirmed" never leaves a student stranded on "Prospect". An explicit
        // crm_status in the request, or an already completed/dropped enrollment, wins.
        if (empty($validated['crm_status'])
            && ! empty($validated['payment_status'])
            && in_array($validated['payment_status'], ['confirmed', 'fee_waived'], true)
            && ! in_array($enrollment->crm_status, ['completed', 'dropped'], true)) {
            $validated['crm_status'] = 'active';
        }

        $enrollment->update($validated);

        // Sync associated invoice status when enrollment payment status changes
        if ($enrollment->invoice_id) {
            $invoice = $enrollment->invoice()->first();
            if ($invoice) {
                if (in_array($enrollment->payment_status, ['confirmed', 'fee_waived'])) {
                    if ($invoice->status === Invoice::STATUS_UNPAID) {
                        $invoice->status = Invoice::STATUS_PAID;
                        $invoice->save();
                    }
                } elseif (in_array($enrollment->payment_status, ['action_required', 'not_verified', 'under_review'])) {
                    if ($invoice->status === Invoice::STATUS_PAID) {
                        $invoice->status = Invoice::STATUS_UNPAID;
                        $invoice->save();
                    }
                }
            }
        }

        // (The "below 80% attendance" warning email now fires automatically from
        // the attendance sheet — see AttendanceController::recomputePercentage.)

        // Fire notification if crm_status changed (single fresh() call)
        $freshEnrollment = $enrollment->fresh(['user', 'batch.course']);
        $newCrmStatus = $freshEnrollment?->crm_status;
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
            'data' => $enrollment->fresh(['user:id,first_name,last_name,email,phone', 'batch.course:id,course_name']),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Blank out the batch class link (Zoom/Meet) on enrollments whose payment
     * has not been confirmed yet, so unpaid students never receive it.
     */
    private function hideClassLinkIfUnpaid(Enrollment $enrollment): void
    {
        if (! in_array($enrollment->payment_status, ['confirmed', 'fee_waived']) && $enrollment->relationLoaded('batch') && $enrollment->batch) {
            $enrollment->batch->class_link = null;
        }
    }

    private function validated(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'student_name' => [...$required, 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'batch_id' => [...$required, 'integer', 'exists:batches,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'enrollment_date' => ['nullable', 'date'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:30'],
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
            'data' => $paginator,
        ]);
    }

    private function canManageAll(Request $request): bool
    {
        return $request->user()->hasAnyRole(['Super Admin', 'Admin']) || $request->user()->can('manage_all');
    }

    private function authorizeManageAll(Request $request): void
    {
        if (! $this->canManageAll($request)) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can perform this action.');
        }
    }
}
