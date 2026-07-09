<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MockTestEnrollment;
use App\Models\MockTestSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class MockTestEnrollmentController extends Controller
{
    // ── User: own enrollments ─────────────────────────────────────────────────

    public function userIndex(Request $request): JsonResponse
    {
        $enrollments = MockTestEnrollment::with([
            'subscription:id,subscriptions_name,subscriptions_type,subscriptions_category,price,duration,duration_type',
            'invoice:id,invoice_number,status,total_npr,crm_payment_status',
        ])
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate($this->perPage($request));

        return $this->paginated($enrollments, 'Your mock test enrollments retrieved.');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $enrollment = MockTestEnrollment::with(['subscription', 'invoice', 'user:id,first_name,last_name,email'])
            ->findOrFail($id);

        if (!$this->canManageAll($request) && $enrollment->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot access this enrollment.');
        }

        return response()->json(['success' => true, 'message' => 'Enrollment retrieved.', 'data' => $enrollment]);
    }

    // ── Admin: full view + management ────────────────────────────────────────

    public function adminStats(Request $request): JsonResponse
    {
        $this->authorizeManageAll($request);

        $today = now()->toDateString();

        return response()->json([
            'success' => true,
            'data'    => [
                'total'   => MockTestEnrollment::count(),
                'active'  => MockTestEnrollment::where('subscription_end', '>=', $today)->count(),
                'expired' => MockTestEnrollment::where('subscription_end', '<', $today)->count(),
            ],
        ]);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $this->authorizeManageAll($request);

        $query = MockTestEnrollment::with([
            'subscription:id,subscriptions_name,subscriptions_type,subscriptions_category,price,duration,duration_type',
            'invoice:id,invoice_number,status,total_npr',
            'user:id,first_name,last_name,email,phone',
        ])->latest('id');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->whereHas('user', fn ($u) =>
                    $u->where('first_name', 'like', $s)
                      ->orWhere('last_name', 'like', $s)
                      ->orWhere('email', 'like', $s)
                )
                ->orWhereHas('subscription', fn ($p) =>
                    $p->where('subscriptions_name', 'like', $s)
                      ->orWhere('subscriptions_type', 'like', $s)
                );
            });
        }

        return $this->paginated(
            $query->paginate($this->perPage($request)),
            'Enrollments retrieved successfully.'
        );
    }

    // ── Admin: create enrollment + auto-generate invoice ─────────────────────

    public function store(Request $request): JsonResponse
    {
        $this->authorizeManageAll($request);

        $data = $request->validate([
            'subscription_id'    => 'required|integer|exists:mock_test_subscriptions,id',
            'user_id'            => 'required|integer|exists:users,id',
            'enrollment_date'    => 'required|date',
            'subscription_start' => 'required|date',
            'subscription_end'   => 'required|date|after_or_equal:subscription_start',
        ]);

        $plan = MockTestSubscription::findOrFail($data['subscription_id']);

        $price    = (float) ($plan->price ?? 0);
        $discount = (float) ($plan->discount ?? 0);
        $total    = max($price - $discount, 0);

        $invoiceNumber = 'MOCK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

        $invoice = Invoice::create([
            'invoice_number'            => $invoiceNumber,
            'user_id'                   => $data['user_id'],
            'mock_test_subscription_id' => $plan->id,
            'subtotal_npr'              => $price,
            'discount_npr'              => $discount,
            'tax_npr'                   => 0,
            'total_npr'                 => $total,
            'status'                    => 'unpaid',
            'payment_method'            => 'bank_qr',
            'invoice_date'              => now()->toDateString(),
            'due_date'                  => now()->addDays(7)->toDateString(),
        ]);

        $enrollment = MockTestEnrollment::create([
            'subscription_id'    => $data['subscription_id'],
            'invoice_id'         => $invoice->id,
            'user_id'            => $data['user_id'],
            'enrollment_date'    => $data['enrollment_date'],
            'subscription_start' => $data['subscription_start'],
            'subscription_end'   => $data['subscription_end'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Enrolled successfully. Invoice generated.',
            'data'    => $enrollment->load([
                'subscription:id,subscriptions_name,subscriptions_type,price,duration,duration_type',
                'invoice:id,invoice_number,status,total_npr',
                'user:id,first_name,last_name,email',
            ]),
        ], Response::HTTP_CREATED);
    }

    public function adminUpdate(Request $request, int $id): JsonResponse
    {
        $this->authorizeManageAll($request);

        $enrollment = MockTestEnrollment::findOrFail($id);

        $data = $request->validate([
            'subscription_start' => ['sometimes', 'required', 'date'],
            'subscription_end'   => ['sometimes', 'required', 'date', 'after_or_equal:subscription_start'],
        ]);

        $enrollment->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment updated.',
            'data'    => $enrollment->fresh(['subscription', 'invoice', 'user:id,first_name,last_name,email']),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeManageAll($request);
        MockTestEnrollment::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Enrollment deleted.']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function perPage(Request $request): int
    {
        return min(max((int) $request->query('limit', 20), 1), 100);
    }

    private function paginated($paginator, string $message): JsonResponse
    {
        return response()->json([
            'success'    => true,
            'message'    => $message,
            'data'       => $paginator->items(),
            'pagination' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    private function canManageAll(Request $request): bool
    {
        return $request->user()->hasAnyRole(['Super Admin', 'Admin']) || $request->user()->can('manage_all');
    }

    private function authorizeManageAll(Request $request): void
    {
        if (!$this->canManageAll($request)) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can perform this action.');
        }
    }
}
