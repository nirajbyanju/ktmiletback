<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Enrollment;
use App\Models\ExamBookingEnrollment;
use App\Models\Invoice;
use App\Models\MockTestSubscription;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoiceService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with([
            'batch.course:id,course_name',
            'mockTestSubscription:id,subscriptions_name,subscriptions_type,subscriptions_category,price',
            'examBookingEnrollment.examBooking:id,exam_name,exam_type,price',
            'user:id,name,first_name,last_name,email,phone',
        ])->latest('id');

        if (!$this->canManageInvoices($request)) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('type')) {
            match ($request->string('type')->toString()) {
                'course'    => $query->whereNotNull('batch_id'),
                'mock_test' => $query->whereNotNull('mock_test_subscription_id'),
                'exam'      => $query->whereNotNull('exam_booking_enrollment_id'),
                default     => null,
            };
        }

        $invoices = $query->paginate(min(max((int) $request->query('limit', 10), 1), 100));

        return response()->json([
            'success'    => true,
            'message'    => 'Invoices retrieved successfully.',
            'data'       => $invoices->items(),
            'pagination' => [
                'total'        => $invoices->total(),
                'per_page'     => $invoices->perPage(),
                'current_page' => $invoices->currentPage(),
                'last_page'    => $invoices->lastPage(),
            ],
        ]);
    }

    // -- Create for course batch --------------------------------------------

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'batch_id'       => ['required', 'integer', 'exists:batches,id'],
            'offer_claim_id' => ['nullable', 'integer', 'exists:offer_claims,id'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'notes'          => ['nullable', 'string'],
        ]);

        $batch = Batch::with('course')->where('is_active', true)->findOrFail($data['batch_id']);

        if ($batch->is_price_variable || $batch->price_npr === null) {
            return response()->json([
                'success' => false,
                'message' => 'This batch uses variable pricing. Please contact admin.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

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

        $alreadyEnrolled = Enrollment::where('user_id', $request->user()->id)
            ->where('batch_id', $batch->id)
            ->whereIn('crm_status', ['active', 'completed'])
            ->exists();

        if ($alreadyEnrolled) {
            return response()->json([
                'success' => false,
                'message' => 'You are already enrolled in this batch. Contact admin if you need assistance.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $invoice = $this->invoiceService->createForBatch($batch, $request->user(), $data);

        return response()->json([
            'success' => true,
            'message' => 'Invoice generated successfully.',
            'data'    => $invoice,
        ], Response::HTTP_CREATED);
    }

    // -- Create for mock test subscription ----------------------------------

    public function storeForMockTest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mock_test_subscription_id' => ['required', 'integer', 'exists:mock_test_subscriptions,id'],
            'offer_claim_id'            => ['nullable', 'integer', 'exists:offer_claims,id'],
            'payment_method'            => ['nullable', 'string', 'max:50'],
            'notes'                     => ['nullable', 'string'],
        ]);

        $subscription = MockTestSubscription::findOrFail($data['mock_test_subscription_id']);

        if ($subscription->price === null) {
            return response()->json([
                'success' => false,
                'message' => 'This subscription has no price set. Contact admin.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $invoice = $this->invoiceService->createForMockTest($subscription, $request->user(), $data);

        return response()->json([
            'success' => true,
            'message' => 'Mock test invoice generated successfully.',
            'data'    => $invoice,
        ], Response::HTTP_CREATED);
    }

    // -- Create for exam booking --------------------------------------------

    public function storeForExam(Request $request): JsonResponse
    {
        $data = $request->validate([
            'exam_booking_enrollment_id' => ['required', 'integer', 'exists:exam_bookings_enrollments,id'],
            'offer_claim_id'             => ['nullable', 'integer', 'exists:offer_claims,id'],
            'payment_method'             => ['nullable', 'string', 'max:50'],
            'notes'                      => ['nullable', 'string'],
        ]);

        $enrollment = ExamBookingEnrollment::with('examBooking')->findOrFail($data['exam_booking_enrollment_id']);

        if ($enrollment->user_id !== $request->user()->id && !$this->canManageInvoices($request)) {
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

        return response()->json([
            'success' => true,
            'message' => 'Exam booking invoice generated successfully.',
            'data'    => $invoice,
        ], Response::HTTP_CREATED);
    }

    // -- Show ---------------------------------------------------------------

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoiceAccess($request, $invoice);

        return response()->json([
            'success' => true,
            'message' => 'Invoice retrieved successfully.',
            'data'    => $invoice->load([
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
        if (!$this->canManageInvoices($request)) {
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
                'message' => 'Only unpaid invoices can be marked as paid. Current status: ' . $invoice->status,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data    = $request->validate(['notes' => ['nullable', 'string']]);
        $invoice = $this->invoiceService->markPaid($invoice, $request->user(), $data['notes'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Invoice marked paid and enrollment activated successfully.',
            'data'    => $invoice,
        ]);
    }

    // -- Upload payment screenshot (student, unpaid only) ------------------

    public function uploadScreenshot(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeInvoiceAccess($request, $invoice);

        if ($invoice->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot upload a screenshot for this invoice.');
        }

        if (in_array($invoice->status, [Invoice::STATUS_CANCELLED, Invoice::STATUS_REFUNDED])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot upload a screenshot for a ' . $invoice->status . ' invoice.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $request->validate([
            'screenshot' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $file = $request->file('screenshot');
        $path = $file->store('payment_screenshots/' . $request->user()->id, 'local');

        $invoice->update([
            'payment_screenshot_path' => $path,
            'screenshot_uploaded_at'  => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment screenshot uploaded successfully. Admin will verify shortly.',
            'data'    => $invoice->fresh([
                'batch.course',
                'mockTestSubscription',
                'examBookingEnrollment.examBooking',
                'user:id,name,first_name,last_name,email,phone',
            ]),
        ]);
    }

    // -- Refund invoice (admin only, paid only) ----------------------------

    public function refund(Request $request, Invoice $invoice): JsonResponse
    {
        if (!$this->canManageInvoices($request)) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can process refunds.');
        }

        if ($invoice->status !== Invoice::STATUS_PAID) {
            return response()->json([
                'success' => false,
                'message' => 'Only paid invoices can be refunded.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $request->validate([
            'refund_amount' => ['required', 'numeric', 'min:0.01', 'max:' . $invoice->total_npr],
            'reason'        => ['required', 'string', 'max:1000'],
        ]);

        $invoice = $this->invoiceService->processRefund(
            $invoice,
            $request->user(),
            (float) $data['refund_amount'],
            $data['reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'Refund processed successfully. Enrollment has been deactivated.',
            'data'    => $invoice,
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

        return response()->json([
            'success' => true,
            'message' => 'Invoice cancelled successfully. You can generate a new invoice at any time.',
            'data'    => $invoice->fresh([
                'batch.course',
                'mockTestSubscription',
                'examBookingEnrollment.examBooking',
                'user:id,name,first_name,last_name,email,phone',
            ]),
        ]);
    }

    // -- Helpers -----------------------------------------------------------

    private function canManageInvoices(Request $request): bool
    {
        $user = $request->user();
        return $user && ($user->hasAnyRole(['Super Admin', 'Admin']) || $user->can('manage_all'));
    }

    private function authorizeInvoiceAccess(Request $request, Invoice $invoice): void
    {
        if ($this->canManageInvoices($request)) return;
        if ($invoice->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have access to this invoice.');
        }
    }
}
