<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\DemoRequest;
use App\Services\TemplateMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            'course_name' => 'nullable|string|max:255',
            'course_id' => 'nullable|integer',
            'phone' => 'required|string|max:20',
            'country' => 'nullable|string|max:100',
            'preferred_at' => 'nullable|string|max:255',
        ]);

        $courseName = $validated['course_name'] ?? null;
        $courseId = $validated['course_id'] ?? null;

        // Check if there is an existing demo request for the same user and course
        $existingQuery = DemoRequest::where('user_id', $user->id)
            ->whereNull('archived_at');

        if ($courseId) {
            $existingQuery->where(function ($q) use ($courseId, $courseName) {
                $q->where('course_id', $courseId)
                    ->orWhere('course_name', $courseName);
            });
        } elseif ($courseName) {
            $existingQuery->where('course_name', $courseName);
        }

        $existing = $existingQuery->first();

        if ($existing) {
            $courseLabel = $courseName ?: 'this course';

            return response()->json([
                'success' => false,
                'message' => "You have already booked a live demo for {$courseLabel}. You can only book a demo once per course.",
            ], 422);
        }

        $displayName = $user->name
            ?? trim(($user->first_name ?? '').' '.($user->last_name ?? ''))
            ?: $user->email;

        $demoRequest = DemoRequest::create([
            ...$validated,
            'user_id' => $user->id,
            'name' => $displayName,
            'email' => $user->email,
            'phone' => $validated['phone'] ?? $user->phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your demo request has been submitted! Our team will contact you with session details.',
            'data' => $demoRequest,
        ], 201);
    }

    /**
     * Authenticated user — reschedule (update) their own PENDING demo request.
     */
    public function userUpdate(Request $request, DemoRequest $demoRequest): JsonResponse
    {
        if ($demoRequest->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'You cannot modify this request.'], 403);
        }

        if ($demoRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been processed. Please book a new demo instead.',
            ], 422);
        }

        $validated = $request->validate([
            'preferred_at' => 'required|string|max:255',
        ]);

        $demoRequest->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Your preferred time has been updated. Our team will confirm shortly.',
            'data' => $demoRequest->fresh(),
        ]);
    }

    /**
     * Authenticated user — their own demo requests.
     */
    public function userIndex(Request $request): JsonResponse
    {
        $requests = DemoRequest::where('user_id', $request->user()->id)
            ->whereNull('archived_at')
            ->with('attendances:id,demo_request_id,attended_on,status')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (DemoRequest $demo) {
                // A demo is a single session — surface its Present/Absent mark (if any)
                // so the student's dashboard can show Attended / Missed.
                $latest = $demo->attendances->sortByDesc('attended_on')->first();
                $demo->unsetRelation('attendances');
                $demo->setAttribute('attendance_status', $latest?->status);

                return $demo;
            });

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Admin — paginated list with search and status filter.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        if (! $this->canViewDemos($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $query = DemoRequest::with('user')->orderBy('created_at', 'desc');

        // Archived records are hidden by default; ?archived=1 shows only them
        $request->boolean('archived')
            ? $query->whereNotNull('archived_at')
            : $query->whereNull('archived_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = '%'.$request->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('course_name', 'like', $search);
            });
        }

        $requests = $query->paginate((int) $request->query('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Admin — view single demo request.
     */
    public function adminShow(Request $request, DemoRequest $demoRequest): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if (! $demoRequest->read_at) {
            $demoRequest->update(['read_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'data' => $demoRequest->load('user'),
        ]);
    }

    /**
     * Admin — approve (with Zoom link + time) or reject.
     * Sends confirmation email on approval.
     */
    public function adminUpdate(Request $request, DemoRequest $demoRequest): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(DemoRequest::STATUSES)],
            'zoom_url' => 'nullable|url|max:500',
            'scheduled_at' => 'nullable|date',
            'teacher' => 'nullable|string|max:255',
            'batch_id' => 'nullable|integer|exists:batches,id',
            'admin_notes' => 'nullable|string|max:3000',
        ]);

        if ($validated['status'] === 'approved') {
            $request->validate([
                'zoom_url' => 'required|url|max:500',
                'scheduled_at' => 'nullable|date',
            ]);
        }

        $wasApproved = $demoRequest->status !== 'approved' && $validated['status'] === 'approved';

        $demoRequest->update($validated);

        if ($wasApproved) {
            $fresh = $demoRequest->fresh();
            app(TemplateMailer::class)->send('demo_confirmed', $fresh->email, [
                'StudentName' => $fresh->name,
                'FirstName' => explode(' ', trim($fresh->name))[0] ?? $fresh->name,
                'TestName' => $fresh->course_name ?? 'IELTS / PTE',
                'DemoDate' => optional($fresh->scheduled_at)->format('j M Y'),
                'DemoTime' => optional($fresh->scheduled_at)->format('g:i A'),
                'TeacherName' => $fresh->teacher,
                'ZoomMeetingID' => (function () use ($fresh) {
                    if ($fresh->zoom_url && preg_match('/zoom\.us\/j\/(\d+)/i', $fresh->zoom_url, $m)) {
                        return trim(chunk_split($m[1], 3, ' '));
                    }

                    return null;
                })(),
                'AdminNotes' => $fresh->admin_notes ? "\n\nNote from our team: ".$fresh->admin_notes : '',
            ], ['user_id' => $fresh->user_id, 'related' => ['demo_approved', $fresh->id]]);
        }

        $message = $wasApproved
            ? 'Demo approved — confirmation email sent to '.$demoRequest->email
            : 'Demo request updated.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $demoRequest->fresh()->load('user'),
        ]);
    }

    /**
     * Admin — counts per status.
     */
    public function stats(Request $request): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $counts = DemoRequest::whereNull('archived_at')
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => DemoRequest::whereNull('archived_at')->count(),
                'pending' => $counts->get('pending', 0),
                'approved' => $counts->get('approved', 0),
                'rejected' => $counts->get('rejected', 0),
            ],
        ]);
    }

    private function isAdmin(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Super Admin', 'Admin']) || $user->can('manage_all');
    }

    private function canViewDemos(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Super Admin', 'Admin', 'Teacher'])
            || $user->can('manage_all');
    }
}
