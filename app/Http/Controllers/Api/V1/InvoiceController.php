<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\ExamBookingEnrollment;
use App\Models\Invoice;
use App\Models\MockTestSubscription;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

    // ── Create for course batch ───────────────────────────────────────────────

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

        $invoice = $this->invoiceService->createForBatch($batch, $request->user(), $data);

        return response()->json([
            'success' => true,
            'message' => 'Invoice generated successfully.',
            'data'    => $invoice,
        ], Response::HTTP_CREATED);
    }

    // ── Create for mock test subscription ─────────────────────────────────────

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

    // ── Create for exam booking ───────────────────────────────────────────────

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

        $invoice = $this->invoiceService->createForExamBookingEnrollment($enrollment, $request->user(), $data);

        return response()->json([
            'success' => true,
            'message' => 'Exam booking invoice generated successfully.',
            'data'    => $invoice,
        ], Response::HTTP_CREATED);
    }

    // ── Show / mark paid ──────────────────────────────────────────────────────

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

    public function markPaid(Request $request, Invoice $invoice): JsonResponse
    {
        if (!$this->canManageInvoices($request)) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can verify invoice payment.');
        }

        $data    = $request->validate(['notes' => ['nullable', 'string']]);
        $invoice = $this->invoiceService->markPaid($invoice, $request->user(), $data['notes'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Invoice marked paid and enrollment activated successfully.',
            'data'    => $invoice,
        ]);
    }

    // ── Auth helpers ──────────────────────────────────────────────────────────

    private function authorizeInvoiceAccess(Request $request, Invoice $invoice): void
    {
        if ($this->canManageInvoices($request)) {
            return;
        }

        if ($invoice->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot access this invoice.');
        }
    }

    private function canManageInvoices(Request $request): bool
    {
        return $request->user()->hasAnyRole(['Super Admin', 'Admin']) || $request->user()->can('manage_all');
    }
}
