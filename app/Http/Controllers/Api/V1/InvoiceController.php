<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Enrollment;
use App\Models\ExamBookingEnrollment;
use App\Models\Invoice;
use App\Models\InvoiceScreenshot;
use App\Models\MockTestEnrollment;
use App\Models\MockTestSubscription;
use App\Models\User;
use App\Services\AdminNotificationService;
use App\Services\InvoiceService;
use App\Services\TemplateMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly AdminNotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with([
            'batch.course:id,course_name',
            'mockTestSubscription:id,subscriptions_name,subscriptions_type,subscriptions_category,price',
            'examBookingEnrollment.examBooking:id,exam_name,exam_type,price',
            'user:id,name,first_name,last_name,email,phone',
        ])->latest('id');

        if (! $this->canManageInvoices($request)) {
            $query->where('user_id', $request->user()->id)
                // Students never see invoices whose booking was archived by admin
                ->whereDoesntHave('enrollment', fn ($q) => $q->whereNotNull('archived_at'))
                ->whereDoesntHave('examBookingEnrollment', fn ($q) => $q->whereNotNull('archived_at'))
                ->whereDoesntHave('mockTestEnrollment', fn ($q) => $q->whereNotNull('archived_at'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('type')) {
            match ($request->string('type')->toString()) {
                'course' => $query->whereNotNull('batch_id'),
                'mock_test' => $query->whereNotNull('mock_test_subscription_id'),
                'exam' => $query->whereNotNull('exam_booking_enrollment_id'),
                default => null,
            };
        }

        // Only invoices that have no linked enrollment record yet
        if ($request->boolean('no_enrollment')) {
            $query->doesntHave('enrollment');
        }

        $invoices = $query->paginate(min(max((int) $request->query('limit', 10), 1), 100));

        return response()->json([
            'success' => true,
            'message' => 'Invoices retrieved successfully.',
            'data' => $invoices->items(),
            'pagination' => [
                'total' => $invoices->total(),
                'per_page' => $invoices->perPage(),
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
            ],
        ]);
    }

    // -- Create for course batch --------------------------------------------

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'offer_claim_id' => ['nullable', 'integer', 'exists:offer_claims,id'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            // Flexible packages (no fixed class time): student's requested date/time
            'preferred_schedule' => ['nullable', 'string', 'max:255'],
            // Friends Private Group: the members joining this booking
            'group_members' => ['nullable', 'array', 'max:10'],
            'group_members.*.name' => ['required_with:group_members', 'string', 'max:255'],
            'group_members.*.email' => ['nullable', 'email', 'max:255'],
            'group_members.*.phone' => ['nullable', 'string', 'max:20'],
        ]);

        $batch = Batch::with('course')->where('is_active', true)->findOrFail($data['batch_id']);

        $alreadyPaid = Invoice::where('user_id', $request->user()->id)
            ->where('batch_id', $batch->id)
            ->where('status', Invoice::STATUS_PAID)
            ->exists();

        if ($alreadyPaid) {
            return response()->json([
                'success' => false,
                'message' => 'You are already enrolled in this batch. Contact admin if you need assistance.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // ── Duplicate active-course-enrollment guard ───────────────────────────
        // Block only CONFIRMED (paid) enrollments. Pending enrollments
        // (payment_status='action_required', crm_status='prospect') are created at
        // invoice-generation time and must NOT block the student from paying or
        // re-accessing their existing unpaid invoice (handled by $existingUnpaid below).
        //
        // Check A — confirmed enrollment record in the same course.
        // Each student has their OWN course period: once their individual
        // end_date passes (or the cycle is completed/dropped), re-booking unlocks.
        $hasActiveEnrollment = Enrollment::where('user_id', $request->user()->id)
            ->whereIn('payment_status', ['confirmed', 'fee_waived'])
            ->whereNull('archived_at')
            ->whereNotIn('crm_status', ['completed', 'dropped'])
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            })
            ->whereHas('batch', function ($q) use ($batch) {
                $q->where('course_id', $batch->course_id)
                    ->where(function ($q2) {
                        $q2->whereNull('end_date')
                            ->orWhere('end_date', '>=', now()->toDateString());
                    });
            })
            ->exists();

        // Check B — paid invoice for same course (backup guard).
        // Skip invoices whose enrollment is completed, dropped, or expired.
        $hasPaidCourseInvoice = Invoice::where('user_id', $request->user()->id)
            ->where('status', Invoice::STATUS_PAID)
            ->whereDoesntHave('enrollment', function ($q) {
                $q->whereIn('crm_status', ['completed', 'dropped'])
                    ->orWhere('end_date', '<', now()->toDateString())
                    ->orWhereNotNull('archived_at');
            })
            ->whereHas('batch', function ($q) use ($batch) {
                $q->where('course_id', $batch->course_id)
                    ->where(function ($q2) {
                        $q2->whereNull('end_date')
                            ->orWhere('end_date', '>=', now()->toDateString());
                    });
            })
            ->exists();

        if ($hasActiveEnrollment || $hasPaidCourseInvoice) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active enrollment in this course. You can re-enroll once your current enrollment expires.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check C — pending (unpaid) booking for the same course in a DIFFERENT batch.
        // One booking per course: the student must complete or change that booking
        // instead of creating a second one. Same-batch re-access is handled below.
        $hasPendingCourseBooking = Enrollment::where('user_id', $request->user()->id)
            ->where('batch_id', '!=', $batch->id)
            ->whereNull('archived_at')
            ->whereNotIn('payment_status', ['refund_completed'])
            ->whereNotIn('crm_status', ['dropped', 'completed'])
            ->whereHas('batch', function ($q) use ($batch) {
                $q->where('course_id', $batch->course_id)
                    ->where(function ($q2) {
                        $q2->whereNull('end_date')
                            ->orWhere('end_date', '>=', now()->toDateString());
                    });
            })
            ->exists();

        if ($hasPendingCourseBooking) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a booking for this course. Please complete its payment, or use "Change Batch" on your dashboard to switch batches.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // ── Duplicate pending-invoice guard ───────────────────────────────────────
        // If the student already has an unpaid invoice for this exact batch,
        // return it instead of creating a second duplicate.
        $existingUnpaid = Invoice::where('user_id', $request->user()->id)
            ->where('batch_id', $batch->id)
            ->where('status', Invoice::STATUS_UNPAID)
            // Never resurrect an invoice whose booking was archived by admin
            ->whereDoesntHave('enrollment', fn ($q) => $q->whereNotNull('archived_at'))
            ->latest('id')
            ->first();

        if ($existingUnpaid) {
            // Ensure the pending enrollment exists even for invoices created before this logic was added.
            $pending = Enrollment::firstOrCreate(
                ['invoice_id' => $existingUnpaid->id],
                [
                    'user_id' => $request->user()->id,
                    'student_name' => $request->user()->display_name ?? $request->user()->name ?? $request->user()->email ?? 'Student',
                    'batch_id' => $batch->id,
                    'status' => 'inactive',
                    'crm_status' => 'prospect',
                    'payment_status' => 'action_required',
                    'amount_paid' => 0,
                ]
            );

            if (! empty($data['preferred_schedule'])) {
                $pending->update(['preferred_schedule' => $data['preferred_schedule']]);
            }

            if (array_key_exists('group_members', $data) && is_array($data['group_members'])) {
                $this->invoiceService->syncGroupMembers($pending, $batch, $data['group_members']);
            }

            return response()->json([
                'success' => true,
                'message' => 'You already have a pending invoice for this batch.',
                'data' => $existingUnpaid,
            ]);
        }

        // Slot capacity: block booking a batch that is already full
        // (live leader bookings only — members and ended bookings don't count)
        if ($batch->max_size) {
            $taken = Enrollment::where('batch_id', $batch->id)
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

        $invoice = $this->invoiceService->createForBatch($batch, $request->user(), $data);

        $this->notifications->notifyInvoiceCreated($invoice, $request->user());

        app(TemplateMailer::class)->sendToUser('enrollment_received', $request->user(), [
            'CourseName' => $invoice->batch?->course?->course_name ?? 'your course',
            'PlanName' => $invoice->batch?->batch_type ?? '',
            'InvoiceNumber' => $invoice->invoice_number,
            'PaymentAmount' => 'NPR '.number_format((float) $invoice->total_npr),
        ], ['related' => ['invoice_created', $invoice->id]]);

        return response()->json([
            'success' => true,
            'message' => 'Invoice generated successfully.',
            'data' => $invoice,
        ], Response::HTTP_CREATED);
    }

    // -- Create for mock test subscription ----------------------------------

    public function storeForMockTest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mock_test_subscription_id' => ['required', 'integer', 'exists:mock_test_subscriptions,id'],
            'offer_claim_id' => ['nullable', 'integer', 'exists:offer_claims,id'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $subscription = MockTestSubscription::findOrFail($data['mock_test_subscription_id']);

        if ($subscription->price === null) {
            return response()->json([
                'success' => false,
                'message' => 'This subscription has no price set. Contact admin.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // ── Duplicate paid-subscription guard ─────────────────────────────────────
        $alreadyPaid = Invoice::where('user_id', $request->user()->id)
            ->where('mock_test_subscription_id', $subscription->id)
            ->where('status', Invoice::STATUS_PAID)
            ->exists();

        if ($alreadyPaid) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active subscription for this mock test plan. Contact admin if you need assistance.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // ── Duplicate pending-invoice guard ───────────────────────────────────────
        $existingUnpaid = Invoice::where('user_id', $request->user()->id)
            ->where('mock_test_subscription_id', $subscription->id)
            ->where('status', Invoice::STATUS_UNPAID)
            ->latest('id')
            ->first();

        if ($existingUnpaid) {
            return response()->json([
                'success' => true,
                'message' => 'You already have a pending invoice for this mock test plan.',
                'data' => $existingUnpaid,
            ]);
        }

        $invoice = $this->invoiceService->createForMockTest($subscription, $request->user(), $data);

        $this->notifications->notifyInvoiceCreated($invoice, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Mock test invoice generated successfully.',
            'data' => $invoice,
        ], Response::HTTP_CREATED);
    }

    // -- Create for exam booking --------------------------------------------

    public function storeForExam(Request $request): JsonResponse
    {
        $data = $request->validate([
            'exam_booking_enrollment_id' => ['required', 'integer', 'exists:exam_bookings_enrollments,id'],
            'offer_claim_id' => ['nullable', 'integer', 'exists:offer_claims,id'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $enrollment = ExamBookingEnrollment::with('examBooking')->findOrFail($data['exam_booking_enrollment_id']);

        if ($enrollment->user_id !== $request->user()->id && ! $this->canManageInvoices($request)) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot create an invoice for this enrollment.');
        }

        $alreadyPaid = Invoice::where('user_id', $enrollment->user_id)
            ->where('exam_booking_enrollment_id', $enrollment->id)
            ->where('status', Invoice::STATUS_PAID)
            ->exists();

        if ($alreadyPaid) {
            return response()->json([
                'success' => false,
                'message' => 'This exam booking has already been paid. Contact admin if you need assistance.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $invoice = $this->invoiceService->createForExamBookingEnrollment($enrollment, $request->user(), $data);

        $this->notifications->notifyInvoiceCreated($invoice, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Exam booking invoice generated successfully.',
            'data' => $invoice,
        ], Response::HTTP_CREATED);
    }

    // -- Show ---------------------------------------------------------------

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoiceAccess($request, $invoice);

        return response()->json([
            'success' => true,
            'message' => 'Invoice retrieved successfully.',
            'data' => $invoice->load([
                'batch.course',
                'mockTestSubscription',
                'examBookingEnrollment.examBooking',
                'user:id,name,first_name,last_name,email,phone',
                'enrollment',
                'mockTestEnrollment',
            ]),
        ]);
    }

    // -- Mark paid (admin only) --------------------------------------------

    public function markPaid(Request $request, Invoice $invoice): JsonResponse
    {
        if (! $this->canManageInvoices($request)) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can verify invoice payment.');
        }

        if ($invoice->status === Invoice::STATUS_PAID) {
            return response()->json([
                'success' => false,
                'message' => 'This invoice is already marked as paid.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($invoice->status !== Invoice::STATUS_UNPAID) {
            return response()->json([
                'success' => false,
                'message' => 'Only unpaid invoices can be marked as paid. Current status: '.$invoice->status,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $request->validate(['notes' => ['nullable', 'string']]);
        $invoice = $this->invoiceService->markPaid($invoice, $request->user(), $data['notes'] ?? null);

        $this->notifications->notifyInvoicePaid($invoice, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Invoice marked paid and enrollment activated successfully.',
            'data' => $invoice,
        ]);
    }

    // -- Upload payment screenshot (student, unpaid only) ------------------

    public function uploadScreenshot(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoiceAccess($request, $invoice);

        // Students may only upload for their own invoices; admins may upload for any invoice.
        if (! $this->canManageInvoices($request) && $invoice->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot upload a screenshot for this invoice.');
        }

        if (in_array($invoice->status, [Invoice::STATUS_CANCELLED, Invoice::STATUS_REFUNDED])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot upload a screenshot for a '.$invoice->status.' invoice.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $request->validate([
            // Allow images and PDF bank receipts. 'image' rule is removed because it rejects PDFs.
            'screenshot' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:10240'],
        ]);

        $file = $request->file('screenshot');
        $path = $file->store('payment_screenshots/'.$invoice->id, 'public');

        // ── Save to screenshot history (never overwrite, always append) ────────
        InvoiceScreenshot::create([
            'invoice_id' => $invoice->id,
            'file_path' => $path,
            'uploaded_by' => $request->user()->id,
        ]);

        // ── Update invoice with latest screenshot ─────────────────────────────
        $invoice->update([
            'payment_screenshot_path' => $path,
            'screenshot_uploaded_at' => now(),
            // Auto-advance CRM status so both dashboards immediately show "Payment Under Review"
            'crm_payment_status' => 'under_review',
        ]);

        // Keep the linked course enrollment in sync — the student dashboard reads
        // enrollment.payment_status, so it must also advance to "under review".
        $invoice->enrollment()
            ->whereIn('payment_status', ['action_required', 'not_verified'])
            ->update(['payment_status' => 'under_review']);

        $this->notifications->notifyScreenshotUploaded($invoice->fresh(), $request->user());

        $screenshotOwner = $invoice->user ?? User::find($invoice->user_id);
        app(TemplateMailer::class)->sendToUser('screenshot_received', $screenshotOwner, [
            'PaymentAmount' => 'NPR '.number_format((float) $invoice->total_npr),
            'ItemName' => $this->invoiceItemName($invoice),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment screenshot uploaded successfully. Admin will verify shortly.',
            'data' => $invoice->fresh([
                'batch.course',
                'mockTestSubscription',
                'examBookingEnrollment.examBooking',
                'user:id,name,first_name,last_name,email,phone',
            ]),
        ]);
    }

    // -- Screenshot upload history (student sees own, admin sees all) ------

    public function getScreenshotHistory(Request $request, Invoice $invoice): JsonResponse
    {
        if (! $this->canManageInvoices($request) && $invoice->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot access this invoice.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Screenshot history retrieved.',
            'data' => $invoice->screenshots()->get(),
        ]);
    }

    // -- Refund invoice (admin only, paid only) ----------------------------

    public function refund(Request $request, Invoice $invoice): JsonResponse
    {
        if (! $this->canManageInvoices($request)) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can process refunds.');
        }

        if ($invoice->status !== Invoice::STATUS_PAID) {
            return response()->json([
                'success' => false,
                'message' => 'Only paid invoices can be refunded.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $request->validate([
            'refund_amount' => ['required', 'numeric', 'min:0.01', 'max:'.$invoice->total_npr],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $invoice = $this->invoiceService->processRefund(
            $invoice,
            $request->user(),
            (float) $data['refund_amount'],
            $data['reason']
        );

        $this->notifications->notifyInvoiceRefunded($invoice, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Refund processed successfully. Enrollment has been deactivated.',
            'data' => $invoice,
        ]);
    }

    // -- Cancel invoice (student only, unpaid only) ------------------------

    public function cancel(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot cancel this invoice.');
        }

        if ($invoice->status === Invoice::STATUS_PAID) {
            return response()->json([
                'success' => false,
                'message' => 'This invoice has already been verified and cannot be cancelled.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($invoice->status === Invoice::STATUS_CANCELLED) {
            return response()->json([
                'success' => false,
                'message' => 'This invoice is already cancelled.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($invoice->status === Invoice::STATUS_REFUNDED) {
            return response()->json([
                'success' => false,
                'message' => 'This invoice has been refunded and cannot be cancelled.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $invoice->update(['status' => Invoice::STATUS_CANCELLED]);

        // A cancelled invoice's pending booking must not linger in the CRM or
        // dashboard: archive it (restorable), same as the Cancel Request flow.
        // Only unpaid invoices reach this point, so these are pending records.
        $pendingIds = Enrollment::where('invoice_id', $invoice->id)->pluck('id');
        if ($pendingIds->isNotEmpty()) {
            Enrollment::whereIn('id', $pendingIds)->update(['archived_at' => now()]);
            // Group booking: members follow the leader
            Enrollment::whereIn('parent_enrollment_id', $pendingIds)->update(['archived_at' => now()]);
        }
        MockTestEnrollment::where('invoice_id', $invoice->id)->update(['archived_at' => now()]);

        $this->notifications->notifyInvoiceCancelled($invoice->fresh(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Invoice cancelled successfully. You can generate a new invoice at any time.',
            'data' => $invoice->fresh([
                'batch.course',
                'mockTestSubscription',
                'examBookingEnrollment.examBooking',
                'user:id,name,first_name,last_name,email,phone',
            ]),
        ]);
    }

    // -- Serve payment screenshot (owner or admin) -------------------------

    public function serveScreenshot(Request $request, Invoice $invoice): StreamedResponse|JsonResponse
    {
        $this->authorizeInvoiceAccess($request, $invoice);

        $path = $invoice->payment_screenshot_path;

        if (! $path) {
            return response()->json(['success' => false, 'message' => 'Screenshot not found.'], Response::HTTP_NOT_FOUND);
        }

        // New uploads use 'public' disk; legacy uploads (before disk fix) used 'local'.
        if (Storage::disk('public')->exists($path)) {
            $disk = 'public';
        } elseif (Storage::disk('local')->exists($path)) {
            $disk = 'local';
        } else {
            return response()->json(['success' => false, 'message' => 'Screenshot not found.'], Response::HTTP_NOT_FOUND);
        }

        $mime = Storage::disk($disk)->mimeType($path) ?: 'image/jpeg';
        $contents = Storage::disk($disk)->get($path);

        return response()->stream(function () use ($contents) {
            echo $contents;
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    // -- Update CRM payment status (admin only) ----------------------------

    public function updateCrmStatus(Request $request, Invoice $invoice): JsonResponse
    {
        if (! $this->canManageInvoices($request)) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can update the payment CRM status.');
        }

        $data = $request->validate([
            'crm_payment_status' => [
                'required',
                'string',
                'in:action_required,under_review,not_verified,fee_waived,confirmed,refund_under_review,refund_not_approved,refund_completed',
            ],
        ]);

        $invoice->update(['crm_payment_status' => $data['crm_payment_status']]);

        // Keep the linked course enrollment in sync — the student dashboard's
        // buttons (change batch / cancel / pay) follow enrollment.payment_status.
        if (in_array($data['crm_payment_status'], Enrollment::PAYMENT_STATUSES, true)) {
            $invoice->enrollment()->update(['payment_status' => $data['crm_payment_status']]);
        }

        $statusOwner = $invoice->user ?? User::find($invoice->user_id);
        if ($data['crm_payment_status'] === 'not_verified') {
            app(TemplateMailer::class)->sendToUser('payment_not_verified', $statusOwner, [
                'PaymentAmount' => 'NPR '.number_format((float) $invoice->total_npr),
                'ItemName' => $this->invoiceItemName($invoice),
            ]);
        } elseif ($data['crm_payment_status'] === 'refund_not_approved') {
            app(TemplateMailer::class)->sendToUser('refund_declined', $statusOwner, [
                'ItemName' => $this->invoiceItemName($invoice),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated successfully.',
            'data' => $invoice->fresh([
                'batch.course',
                'mockTestSubscription',
                'examBookingEnrollment.examBooking',
                'user:id,name,first_name,last_name,email,phone',
            ]),
        ]);
    }

    // -- Helpers -----------------------------------------------------------

    private function invoiceItemName(Invoice $invoice): string
    {
        $invoice->loadMissing(['batch.course', 'mockTestSubscription', 'examBookingEnrollment.examBooking']);

        return match ($invoice->type) {
            Invoice::TYPE_COURSE => trim(($invoice->batch?->course?->course_name ?? 'Course').' — '.($invoice->batch?->batch_type ?? '')),
            Invoice::TYPE_MOCK_TEST => 'Mock Test — '.($invoice->mockTestSubscription?->subscriptions_name ?? ''),
            Invoice::TYPE_EXAM => 'Exam Booking — '.($invoice->examBookingEnrollment?->examBooking?->exam_type ?? ''),
            default => 'your booking',
        };
    }

    private function canManageInvoices(Request $request): bool
    {
        $user = $request->user();

        return $user && ($user->hasAnyRole(['Super Admin', 'Admin']) || $user->can('manage_all'));
    }

    private function authorizeInvoiceAccess(Request $request, Invoice $invoice): void
    {
        if ($this->canManageInvoices($request)) {
            return;
        }
        if ($invoice->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have access to this invoice.');
        }
    }
}
