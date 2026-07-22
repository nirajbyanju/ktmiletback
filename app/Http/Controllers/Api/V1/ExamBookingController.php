<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ExamBooking;
use App\Models\ExamBookingEnrollment;
use App\Services\InvoiceService;
use App\Services\TemplateMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ExamBookingController extends BaseController
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    // ── Public: browse available plans ───────────────────────────────────────

    public function userPlanIndex(Request $request): JsonResponse
    {
        $plans = ExamBooking::orderBy('exam_type')->orderBy('exam_name')->get();

        return response()->json(['success' => true, 'data' => $plans]);
    }

    // ── User: submit enrollment + auto-generate invoice ───────────────────────

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exam_booking_id' => 'required|integer|exists:exam_bookings,id',
            'preferred_date' => 'required|date|after_or_equal:today',
            'preferred_time' => 'nullable|date_format:H:i',
            'test_location' => 'nullable|string|max:255',
            'preferred_test_centre' => 'nullable|string|max:255',
            'passport_name' => 'required|string|max:255',
            'passport_number' => 'required|string|max:50',
            'date_of_birth' => 'required|date|before:today',
            'contact_number' => 'required_without:phone|nullable|string|max:20',
            'phone' => 'required_without:contact_number|nullable|string|max:20',
            'email' => 'required|email|max:255',
            'passport_copy' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'special_message' => 'nullable|string|max:1000',
        ]);

        $passportCopyPath = null;
        $originalName = null;

        if ($request->hasFile('passport_copy')) {
            $file = $request->file('passport_copy');
            $originalName = $file->getClientOriginalName();
            $passportCopyPath = $file->store('passport_copies/'.$request->user()->id, 'local');
        }

        $plan = ExamBooking::findOrFail($validated['exam_booking_id']);

        $contact = $validated['contact_number'] ?? $validated['phone'];
        $phone = $validated['phone'] ?? $validated['contact_number'];

        $enrollment = ExamBookingEnrollment::create([
            'user_id' => $request->user()->id,
            'exam_booking_id' => $plan->id,
            'preferred_date' => $validated['preferred_date'],
            'preferred_time' => $validated['preferred_time'] ?? null,
            'test_location' => $validated['test_location'] ?? $validated['preferred_test_centre'] ?? null,
            'preferred_test_centre' => $validated['preferred_test_centre'] ?? $validated['test_location'] ?? null,
            'passport_name' => $validated['passport_name'],
            'passport_number' => $validated['passport_number'],
            'date_of_birth' => $validated['date_of_birth'],
            'contact_number' => $contact,
            'phone' => $phone,
            'email' => $validated['email'],
            'passport_copy_path' => $passportCopyPath,
            'passport_copy_original_name' => $originalName,
            'special_message' => $validated['special_message'] ?? null,
            'status' => 'new_request',
        ]);

        // Auto-generate the invoice right away (the form promises this) so the
        // dashboard's Payment section appears immediately. Plans without a
        // price fall back to manual invoicing by admin.
        $invoice = null;
        if ((float) ($plan->price ?? 0) > 0) {
            try {
                $invoice = $this->invoiceService->createForExamBookingEnrollment($enrollment, $request->user());
            } catch (\Throwable $e) {
                Log::warning('Auto invoice failed for exam booking #'.$enrollment->id.': '.$e->getMessage());
            }
        }

        app(TemplateMailer::class)->sendToUser('exam_booking_received', $request->user(), [
            'TestName' => $plan->exam_type ?? 'IELTS / PTE',
            'ExamFormat' => $plan->exam_name ?? '',
            'ExamDate' => $enrollment->preferred_date,
            'ExamCentre' => $enrollment->preferred_test_centre ?? $enrollment->test_location,
        ], ['related' => ['exam_booking_received', $enrollment->id]]);

        return response()->json([
            'success' => true,
            'message' => 'Exam booking submitted successfully.',
            'data' => [
                'enrollment' => $enrollment->fresh()->load([
                    'examBooking:id,exam_name,exam_type,price,discount',
                ]),
                'invoice' => $invoice,
            ],
        ], 201);
    }

    // ── User: edit own enrollment (only while status is new_request / document_pending) ──

    public function userUpdate(Request $request, int $id): JsonResponse
    {
        $enrollment = ExamBookingEnrollment::where('user_id', $request->user()->id)
            ->findOrFail($id);

        // Fully editable while new; personal/passport details remain editable
        // while awaiting payment (matches the dashboard edit form).
        $fullyEditable = ['new_request', 'document_pending'];
        $personalEditable = ['payment_pending'];

        if (! in_array($enrollment->status, [...$fullyEditable, ...$personalEditable])) {
            return response()->json([
                'success' => false,
                'message' => 'This booking can no longer be edited because the admin has already started processing it.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $personalOnly = in_array($enrollment->status, $personalEditable);

        $validated = $request->validate([
            // Date/centre rules only apply while those fields are still editable
            'preferred_date' => $personalOnly ? 'nullable' : 'required|date|after_or_equal:today',
            'preferred_time' => 'nullable|date_format:H:i',
            'preferred_test_centre' => $personalOnly ? 'nullable' : 'required|string|max:255',
            'passport_name' => 'required|string|max:255',
            'passport_number' => 'required|string|max:50',
            'date_of_birth' => 'required|date|before:today',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'special_message' => 'nullable|string|max:1000',
            'passport_copy' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $updates = [
            'passport_name' => $validated['passport_name'],
            'passport_number' => $validated['passport_number'],
            'date_of_birth' => $validated['date_of_birth'],
            'contact_number' => $validated['phone'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'special_message' => $validated['special_message'] ?? null,
        ];

        // Date / time / centre are locked once payment is pending
        if (! $personalOnly) {
            $updates['preferred_date'] = $validated['preferred_date'];
            $updates['preferred_time'] = $validated['preferred_time'] ?? null;
            $updates['preferred_test_centre'] = $validated['preferred_test_centre'];
            $updates['test_location'] = $validated['preferred_test_centre'];
        }

        $enrollment->update($updates);

        if ($request->hasFile('passport_copy')) {
            $file = $request->file('passport_copy');
            $enrollment->update([
                'passport_copy_path' => $file->store('passport_copies/'.$request->user()->id, 'local'),
                'passport_copy_original_name' => $file->getClientOriginalName(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking updated successfully.',
            'data' => $enrollment->fresh([
                'examBooking:id,exam_name,exam_type,price,discount',
                'invoice:id,invoice_number,status,total_npr',
            ]),
        ]);
    }

    // ── User: list own enrollments ────────────────────────────────────────────

    public function userIndex(Request $request): JsonResponse
    {
        $enrollments = ExamBookingEnrollment::with([
            'examBooking:id,exam_name,exam_type,price,discount',
            'invoice:id,invoice_number,status,total_npr',
        ])
            ->where('user_id', $request->user()->id)
            ->whereNull('archived_at')
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $enrollments]);
    }

    // ── User/Admin: download passport copy ────────────────────────────────────

    public function downloadPassport(Request $request, int $id)
    {
        $enrollment = ExamBookingEnrollment::findOrFail($id);

        if (! $this->isAdmin($request) && $enrollment->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        if (! $enrollment->passport_copy_path || ! Storage::disk('local')->exists($enrollment->passport_copy_path)) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        return Storage::disk('local')->response(
            $enrollment->passport_copy_path,
            $enrollment->passport_copy_original_name ?? 'passport_copy'
        );
    }

    // ── Admin: plan management ────────────────────────────────────────────────

    public function adminPlanIndex(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $plans = ExamBooking::withCount('enrollments')
            ->orderBy('exam_type')
            ->orderBy('exam_name')
            ->get();

        return response()->json(['success' => true, 'data' => $plans]);
    }

    public function adminPlanStore(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'exam_name' => 'nullable|string|max:255',
            'exam_type' => 'required|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
        ]);

        $validated['discount'] = $validated['discount'] ?? 0;
        $plan = ExamBooking::create($validated);

        return response()->json(['success' => true, 'message' => 'Plan created.', 'data' => $plan], 201);
    }

    public function adminPlanUpdate(Request $request, int $id): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $plan = ExamBooking::findOrFail($id);

        $validated = $request->validate([
            'exam_name' => 'nullable|string|max:255',
            'exam_type' => 'sometimes|required|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
        ]);

        $plan->update($validated);

        return response()->json(['success' => true, 'message' => 'Plan updated.', 'data' => $plan->fresh()]);
    }

    public function adminPlanDestroy(Request $request, int $id): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        ExamBooking::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Plan deleted.']);
    }

    // ── Admin: enrollment stats ───────────────────────────────────────────────

    public function adminStats(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $counts = ExamBookingEnrollment::whereNull('archived_at')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => ExamBookingEnrollment::whereNull('archived_at')->count(),
                'new_request' => (int) ($counts['new_request'] ?? 0),
                'payment_pending' => (int) ($counts['payment_pending'] ?? 0),
                'booked' => (int) ($counts['booked'] ?? 0),
                'cancelled' => (int) ($counts['cancelled'] ?? 0),
            ],
        ]);
    }

    // ── Admin: enrollment list ────────────────────────────────────────────────

    public function adminIndex(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $query = ExamBookingEnrollment::with([
            'user:id,first_name,last_name,email,phone',
            'examBooking:id,exam_name,exam_type,price,discount',
            'invoice:id,invoice_number,status,total_npr',
        ])->latest();

        // Archived records are hidden by default; ?archived=1 shows only them
        $request->boolean('archived')
            ? $query->whereNotNull('archived_at')
            : $query->whereNull('archived_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('exam_type')) {
            $query->whereHas('examBooking', fn ($q) => $q->where('exam_type', $request->exam_type));
        }

        if ($request->filled('search')) {
            $s = '%'.$request->search.'%';
            $query->where(function ($q) use ($s) {
                $q->where('passport_name', 'like', $s)
                    ->orWhere('passport_number', 'like', $s)
                    ->orWhere('email', 'like', $s)
                    ->orWhere('phone', 'like', $s)
                    ->orWhere('contact_number', 'like', $s)
                    ->orWhere('preferred_test_centre', 'like', $s)
                    ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', $s)
                        ->orWhere('last_name', 'like', $s)
                        ->orWhere('email', 'like', $s)
                    );
            });
        }

        $bookings = $query->paginate((int) $request->get('per_page', 20));

        return response()->json(['success' => true, 'data' => $bookings]);
    }

    public function adminShow(Request $request, int $id): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $enrollment = ExamBookingEnrollment::with(['user', 'examBooking', 'invoice'])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $enrollment]);
    }

    public function adminUpdate(Request $request, int $id): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $enrollment = ExamBookingEnrollment::findOrFail($id);
        $wasBooked = $enrollment->status === 'booked';

        $validated = $request->validate([
            'status' => ['sometimes', 'required', Rule::in(ExamBookingEnrollment::STATUSES)],
            'available_slot_checked' => 'sometimes|boolean',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $enrollment->update($validated);

        if (! $wasBooked && ($validated['status'] ?? null) === 'booked') {
            $enrollment->loadMissing(['user', 'examBooking']);
            app(TemplateMailer::class)->sendToUser('exam_booking_confirmed', $enrollment->user, [
                'TestName' => $enrollment->examBooking?->exam_type ?? 'IELTS / PTE',
                'ExamDate' => $enrollment->preferred_date,
                'ExamCentre' => $enrollment->preferred_test_centre ?? $enrollment->test_location,
            ], ['related' => ['exam_booked', $enrollment->id]]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Enrollment updated.',
            'data' => $enrollment->fresh(['user', 'examBooking', 'invoice']),
        ]);
    }

    public function adminDestroy(Request $request, int $id): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        ExamBookingEnrollment::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Enrollment deleted.']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function isAdmin(Request $request): bool
    {
        $user = $request->user();

        return $user->hasAnyRole(['Super Admin', 'Admin']) || $user->can('manage_all');
    }
}
