<?php

namespace App\Http\Controllers\Api\V1;

use App\Mail\DemoApprovedMail;
use App\Models\DemoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class DemoRequestController extends BaseController
{
    /**
     * Authenticated user submits a demo request.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'course_name'     => 'nullable|string|max:255',
            'course_id'       => 'nullable|integer',
            'education_level' => 'required|string|max:100',
            'pass_year'       => 'nullable|string|max:10',
            'phone'           => 'nullable|string|max:20',
        ]);

        $existing = DemoRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending demo request. Our team will contact you shortly.',
            ], 422);
        }

        $displayName = $user->name
            ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
            ?: $user->email;

        $demoRequest = DemoRequest::create([
            ...$validated,
            'user_id' => $user->id,
            'name'    => $displayName,
            'email'   => $user->email,
            'phone'   => $validated['phone'] ?? $user->phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your demo request has been submitted! Our team will contact you with session details.',
            'data'    => $demoRequest,
        ], 201);
    }

    /**
     * Admin — paginated list with search and status filter.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $query = DemoRequest::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('course_name', 'like', $search);
            });
        }

        $requests = $query->paginate((int) $request->query('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $requests,
        ]);
    }

    /**
     * Admin — view single demo request.
     */
    public function adminShow(Request $request, DemoRequest $demoRequest): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if (!$demoRequest->read_at) {
            $demoRequest->update(['read_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'data'    => $demoRequest->load('user'),
        ]);
    }

    /**
     * Admin — approve (with Zoom link + time) or reject.
     * Sends confirmation email on approval.
     */
    public function adminUpdate(Request $request, DemoRequest $demoRequest): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'status'       => ['required', Rule::in(DemoRequest::STATUSES)],
            'zoom_url'     => 'nullable|url|max:500',
            'scheduled_at' => 'nullable|date',
            'admin_notes'  => 'nullable|string|max:3000',
        ]);

        if ($validated['status'] === 'approved') {
            $request->validate([
                'zoom_url'     => 'required|url|max:500',
                'scheduled_at' => 'required|date',
            ]);
        }

        $wasApproved = $demoRequest->status !== 'approved' && $validated['status'] === 'approved';

        $demoRequest->update($validated);

        if ($wasApproved) {
            try {
                Mail::to($demoRequest->email)->send(new DemoApprovedMail($demoRequest->fresh()));
            } catch (\Exception $e) {
                Log::warning('Demo approval email failed for request #' . $demoRequest->id . ': ' . $e->getMessage());
            }
        }

        $message = $wasApproved
            ? 'Demo approved — confirmation email sent to ' . $demoRequest->email
            : 'Demo request updated.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $demoRequest->fresh()->load('user'),
        ]);
    }

    /**
     * Admin — counts per status.
     */
    public function stats(Request $request): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $counts = DemoRequest::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'success' => true,
            'data'    => [
                'total'    => DemoRequest::count(),
                'pending'  => $counts->get('pending', 0),
                'approved' => $counts->get('approved', 0),
                'rejected' => $counts->get('rejected', 0),
            ],
        ]);
    }

    private function isAdmin(Request $request): bool
    {
        $user = $request->user();
        if (!$user) return false;
        return $user->hasAnyRole(['Super Admin', 'Admin']) || $user->can('manage_all');
    }
}
