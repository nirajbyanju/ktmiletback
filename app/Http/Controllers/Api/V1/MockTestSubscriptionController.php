<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MockTestSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MockTestSubscriptionController extends Controller
{
    // ── Public: browse available plans ───────────────────────────────────────

    public function userIndex(Request $request)
    {
        $plans = MockTestSubscription::latest('id')
            ->paginate($this->perPage($request));

        return $this->paginated($plans, 'Available mock test plans retrieved.');
    }

    public function show(int $id)
    {
        $plan = MockTestSubscription::findOrFail($id);

        return response()->json(['success' => true, 'message' => 'Plan retrieved.', 'data' => $plan]);
    }

    // ── Admin: plan management ────────────────────────────────────────────────

    public function adminStats(Request $request)
    {
        $this->authorizeManageAll($request);

        return response()->json([
            'success' => true,
            'data'    => [
                'total' => MockTestSubscription::count(),
            ],
        ]);
    }

    public function adminIndex(Request $request)
    {
        $this->authorizeManageAll($request);

        $query = MockTestSubscription::latest('id');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('subscriptions_name', 'like', $s)
                  ->orWhere('subscriptions_type', 'like', $s)
                  ->orWhere('subscriptions_category', 'like', $s)
                  ->orWhere('company_name', 'like', $s);
            });
        }

        return $this->paginated($query->paginate($this->perPage($request)), 'Plans retrieved.');
    }

    public function store(Request $request)
    {
        $this->authorizeManageAll($request);

        $plan = MockTestSubscription::create($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Mock test plan created.',
            'data'    => $plan,
        ], Response::HTTP_CREATED);
    }

    public function adminUpdate(Request $request, int $id)
    {
        $this->authorizeManageAll($request);

        $plan = MockTestSubscription::findOrFail($id);
        $plan->update($this->validated($request, true));

        return response()->json(['success' => true, 'message' => 'Plan updated.', 'data' => $plan->fresh()]);
    }

    public function destroy(Request $request, int $id)
    {
        $this->authorizeManageAll($request);
        MockTestSubscription::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Plan deleted.']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validated(Request $request, bool $isUpdate = false): array
    {
        $req = $isUpdate ? 'sometimes|required' : 'required';

        return $request->validate([
            'subscriptions_name'     => "$req|string|max:255",
            'subscriptions_type'     => "$req|string|max:100",
            'subscriptions_category' => "$req|string|max:100",
            'company_name'           => "$req|string|max:255",
            'country'                => "$req|string|max:100",
            'price'                  => 'nullable|numeric|min:0',
            'discount'               => 'nullable|numeric|min:0',
            'duration'               => "$req|integer|min:1",
            'duration_type'          => "$req|string|max:50",
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
