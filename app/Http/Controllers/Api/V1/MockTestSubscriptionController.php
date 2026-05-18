<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MockTestSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class MockTestSubscriptionController extends Controller
{
    // ── User: own subscriptions ───────────────────────────────────────────────

    public function userIndex(Request $request)
    {
        $subs = MockTestSubscription::where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate($this->perPage($request));

        return $this->paginated($subs, 'Your mock test subscriptions retrieved.');
    }

    public function show(Request $request, int $id)
    {
        $sub = MockTestSubscription::findOrFail($id);

        if (!$this->canManageAll($request) && $sub->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot access this subscription.');
        }

        return response()->json(['success' => true, 'message' => 'Subscription retrieved.', 'data' => $sub]);
    }

    // ── Admin: full management ────────────────────────────────────────────────

    public function adminStats(Request $request)
    {
        $this->authorizeManageAll($request);

        $today = now()->toDateString();

        return response()->json([
            'success' => true,
            'data' => [
                'total'      => MockTestSubscription::count(),
                'paid'       => MockTestSubscription::where('payment_status', 'paid')->count(),
                'not_paid'   => MockTestSubscription::where('payment_status', 'not_paid')->count(),
                'login_sent' => MockTestSubscription::where('login_sent', true)->count(),
                'active'     => MockTestSubscription::whereNotNull('subscription_end')
                    ->where('subscription_end', '>=', $today)
                    ->count(),
            ],
        ]);
    }

    public function adminIndex(Request $request)
    {
        $this->authorizeManageAll($request);

        $query = MockTestSubscription::with('user:id,first_name,last_name,email,phone')
            ->latest('id');

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('login_sent')) {
            $query->where('login_sent', filter_var($request->login_sent, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('student_name', 'like', $search)
                  ->orWhere('phone', 'like', $search)
                  ->orWhere('login_email', 'like', $search)
                  ->orWhere('subscription_id', 'like', $search)
                  ->orWhere('lead_id', 'like', $search);
            });
        }

        return $this->paginated(
            $query->paginate($this->perPage($request)),
            'Subscriptions retrieved successfully.'
        );
    }

    public function store(Request $request)
    {
        $this->authorizeManageAll($request);

        $sub = MockTestSubscription::create($this->validatedFields($request));

        return response()->json([
            'success' => true,
            'message' => 'Mock test subscription created.',
            'data'    => $sub,
        ], Response::HTTP_CREATED);
    }

    public function adminUpdate(Request $request, int $id)
    {
        $this->authorizeManageAll($request);

        $sub = MockTestSubscription::findOrFail($id);
        $sub->update($this->validatedFields($request, true));

        return response()->json([
            'success' => true,
            'message' => 'Subscription updated.',
            'data'    => $sub->fresh('user:id,first_name,last_name,email,phone'),
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $this->authorizeManageAll($request);
        MockTestSubscription::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Subscription deleted.']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validatedFields(Request $request, bool $isUpdate = false): array
    {
        $sometimes = $isUpdate ? 'sometimes|' : '';

        return $request->validate([
            'lead_id'                 => 'nullable|string|max:30',
            'student_name'            => $sometimes . 'required|string|max:255',
            'phone'                   => 'nullable|string|max:20',
            'course'                  => 'nullable|string|max:255',
            'package'                 => 'nullable|string|max:255',
            'price'                   => 'nullable|numeric|min:0',
            'payment_status'          => ['nullable', Rule::in(MockTestSubscription::PAYMENT_STATUSES)],
            'payment_screenshot_link' => 'nullable|string|max:2048',
            'login_email'             => 'nullable|email|max:255',
            'login_sent'              => 'nullable|boolean',
            'subscription_start'      => 'nullable|date',
            'subscription_end'        => 'nullable|date',
            'platform'                => 'nullable|string|max:100',
            'admin_notes'             => 'nullable|string|max:5000',
            'user_id'                 => 'nullable|integer|exists:users,id',
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
        return $request->user()->hasAnyRole(['Super Admin', 'Admin'])
            || $request->user()->can('manage_all');
    }

    private function authorizeManageAll(Request $request): void
    {
        if (!$this->canManageAll($request)) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can perform this action.');
        }
    }
}
