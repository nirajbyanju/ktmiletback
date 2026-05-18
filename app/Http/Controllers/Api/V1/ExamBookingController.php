<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ExamBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ExamBookingController extends BaseController
{
    public function userIndex(Request $request): JsonResponse
    {
        $bookings = ExamBooking::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $bookings]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'test_type'              => ['required', Rule::in(['IELTS', 'PTE'])],
            'preferred_date'         => 'required|date|after_or_equal:today',
            'test_location'          => 'required_without:preferred_test_centre|string|max:255',
            'preferred_test_centre'  => 'required_without:test_location|string|max:255',
            'passport_name'          => 'required|string|max:255',
            'passport_number'        => 'required|string|max:50',
            'date_of_birth'          => 'required|date|before:today',
            'contact_number'         => 'required_without:phone|string|max:20',
            'phone'                  => 'required_without:contact_number|string|max:20',
            'email'                  => 'required|email|max:255',
            'passport_copy'          => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'student_name'           => 'nullable|string|max:255',
            'preferred_time'         => 'nullable|date_format:H:i',
            'special_message'        => 'nullable|string|max:1000',
        ]);

        $passportCopyPath = null;
        $originalName     = null;

        if ($request->hasFile('passport_copy')) {
            $file             = $request->file('passport_copy');
            $originalName     = $file->getClientOriginalName();
            $passportCopyPath = $file->store('passport_copies/' . $request->user()->id, 'local');
        }

        $centre = $validated['preferred_test_centre'] ?? $validated['test_location'];
        $phone  = $validated['phone'] ?? $validated['contact_number'];

        $booking = ExamBooking::create([
            'user_id'                     => $request->user()->id,
            'student_name'                => $validated['student_name'] ?? null,
            'test_type'                   => $validated['test_type'],
            'preferred_date'              => $validated['preferred_date'],
            'preferred_time'              => $validated['preferred_time'] ?? null,
            'test_location'               => $centre,
            'preferred_test_centre'       => $centre,
            'passport_name'               => $validated['passport_name'],
            'passport_number'             => $validated['passport_number'],
            'date_of_birth'               => $validated['date_of_birth'],
            'contact_number'              => $phone,
            'phone'                       => $phone,
            'email'                       => $validated['email'],
            'passport_copy_path'          => $passportCopyPath,
            'passport_copy_original_name' => $originalName,
            'special_message'             => $validated['special_message'] ?? null,
            'status'                      => 'new_request',
            'payment_status'              => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Exam booking request submitted successfully.',
            'data'    => $booking,
        ], 201);
    }

    public function show(Request $request, ExamBooking $examBooking): JsonResponse
    {
        if (!$this->canAccess($request, $examBooking)) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $examBooking->load('user')]);
    }

    public function downloadPassport(Request $request, ExamBooking $examBooking)
    {
        if (!$this->canAccess($request, $examBooking)) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        if (!$examBooking->passport_copy_path || !Storage::disk('local')->exists($examBooking->passport_copy_path)) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        return Storage::disk('local')->download(
            $examBooking->passport_copy_path,
            $examBooking->passport_copy_original_name ?? 'passport_copy'
        );
    }

    // ── Admin ──────────────────────────────────────────────────────────────────

    public function adminStats(Request $request): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $counts = ExamBooking::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'success' => true,
            'data'    => [
                'total'           => ExamBooking::count(),
                'new_request'     => (int) ($counts['new_request']     ?? 0),
                'payment_pending' => (int) ($counts['payment_pending'] ?? 0),
                'booked'          => (int) ($counts['booked']          ?? 0),
                'cancelled'       => (int) ($counts['cancelled']       ?? 0),
            ],
        ]);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $query = ExamBooking::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('test_type')) {
            $query->where('test_type', $request->test_type);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('passport_name', 'like', $search)
                  ->orWhere('passport_number', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('student_name', 'like', $search)
                  ->orWhere('phone', 'like', $search)
                  ->orWhere('contact_number', 'like', $search)
                  ->orWhere('test_location', 'like', $search)
                  ->orWhere('preferred_test_centre', 'like', $search);
            });
        }

        $bookings = $query->paginate((int) $request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $bookings,
        ]);
    }

    public function adminShow(Request $request, ExamBooking $examBooking): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json(['success' => true, 'data' => $examBooking->load('user')]);
    }

    public function adminUpdate(Request $request, ExamBooking $examBooking): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'status'                     => ['sometimes', 'required', Rule::in(ExamBooking::STATUSES)],
            'payment_status'             => ['sometimes', 'required', Rule::in(ExamBooking::PAYMENT_STATUSES)],
            'available_slot_checked'     => 'sometimes|boolean',
            'pte_login_details_received' => 'sometimes|boolean',
            'pte_username_email'         => 'nullable|string|max:255',
            'pte_password_notes'         => 'nullable|string|max:1000',
            'admin_notes'                => 'nullable|string|max:2000',
        ]);

        $examBooking->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Booking updated successfully.',
            'data'    => $examBooking->fresh('user'),
        ]);
    }

    // kept for backward compat with old PATCH .../status route
    public function updateStatus(Request $request, ExamBooking $examBooking): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'status'      => ['required', Rule::in(ExamBooking::STATUSES)],
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $examBooking->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'data'    => $examBooking->fresh('user'),
        ]);
    }

    private function isAdmin(Request $request): bool
    {
        $user = $request->user();
        return $user->hasAnyRole(['Super Admin', 'Admin']) || $user->can('manage_all');
    }

    private function canAccess(Request $request, ExamBooking $examBooking): bool
    {
        return $this->isAdmin($request) || $examBooking->user_id === $request->user()->id;
    }
}
