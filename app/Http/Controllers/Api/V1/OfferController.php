<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class OfferController extends Controller
{
    // ── Public ────────────────────────────────────────────────────────────────

    public function publicIndex(Request $request): JsonResponse
    {
        $query = Offer::where('status', Offer::STATUS_ACTIVE)
            ->where('is_referral', false) // internal referral vouchers never show publicly
            ->where('valid_date', '>=', Carbon::today()->toDateString())
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', Carbon::today()->toDateString());
            });

        // Filter by applicable_type — always include 'all' offers alongside the requested type
        if ($request->filled('type') && in_array($request->input('type'), Offer::APPLICABLE_TYPES, true)) {
            $type = $request->input('type');
            $query->where(function ($q) use ($type) {
                $q->where('applicable_type', $type)
                    ->orWhere('applicable_type', Offer::APPLICABLE_ALL);
            });
        }

        // Filter by badge keyword (case-insensitive contains match)
        if ($request->filled('badge')) {
            $badge = $request->string('badge');
            $query->where('badge', 'LIKE', '%'.$badge.'%');
        }

        $offers = $query->orderBy('sort_order')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Offers retrieved successfully.',
            'data' => $offers,
        ]);
    }

    // ── Admin: full CRUD ──────────────────────────────────────────────────────

    private function authorizeAdmin(Request $request): void
    {
        if (! $request->user()->hasAnyRole(['Super Admin', 'Admin']) && ! $request->user()->can('manage_all')) {
            abort(403, 'Only admins can perform this action.');
        }
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        // Internal referral vouchers are managed by the referral system, not
        // the offers screen — hide them here to avoid accidental edits.
        $query = Offer::withCount('claims')->where('is_referral', false);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('badge', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $offers = $query->orderBy('sort_order')->orderBy('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Offers retrieved successfully.',
            'data' => [
                'data' => $offers->items(),
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
                'total' => $offers->total(),
                'per_page' => $offers->perPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        $offer = Offer::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Offer created successfully.',
            'data' => $offer,
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);
        $offer = Offer::withCount('claims')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Offer retrieved successfully.',
            'data' => $offer,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);
        $offer = Offer::findOrFail($id);
        $data = $this->validated($request, true);
        $data['updated_by'] = $request->user()->id;
        $offer->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Offer updated successfully.',
            'data' => $offer->fresh(),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);
        $offer = Offer::findOrFail($id);
        $offer->update(['deleted_by' => request()->user()?->id]);
        $offer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Offer deleted successfully.',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validated(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'title' => [...$required, 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date', 'before_or_equal:valid_date'],
            'valid_date' => [...$required, 'date'],
            'claim_discount_amount' => [...$required, 'numeric', 'min:0'],
            'status' => ['in:active,inactive'],
            'badge' => ['nullable', 'string', 'max:100'],
            'cta_text' => ['nullable', 'string', 'max:150'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['integer', 'min:0'],
            'applicable_type' => ['nullable', Rule::in(Offer::APPLICABLE_TYPES)],
            'applicable_id' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
