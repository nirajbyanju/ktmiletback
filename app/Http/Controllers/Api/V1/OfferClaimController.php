<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\OfferClaim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class OfferClaimController extends Controller
{
    // ── User: claim an offer ───────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'offer_id'    => ['required', 'integer', 'exists:offers,id'],
            'source_type' => ['nullable', 'string', 'in:course,mock_test,booking'],
            'source_id'   => ['nullable', 'integer', 'min:1'],
        ]);

        $offer = Offer::findOrFail($request->offer_id);

        if (!$offer->isClaimable()) {
            return response()->json([
                'success' => false,
                'message' => 'This offer is not available for claiming.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $today = Carbon::today()->toDateString();

        // Claim date must not exceed valid_date
        if ($today > $offer->valid_date->toDateString()) {
            return response()->json([
                'success' => false,
                'message' => 'This offer expired on ' . $offer->valid_date->format('d M Y') . '.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Prevent duplicate claims
        $existing = OfferClaim::where('user_id', $request->user()->id)
            ->where('offer_id', $offer->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You have already claimed this offer.',
                'data'    => $existing->load('offer'),
            ], Response::HTTP_CONFLICT);
        }

        // Inherit source context from the offer itself when not explicitly provided
        $sourceType = $request->source_type
            ?? ($offer->applicable_type !== 'all' ? $offer->applicable_type : null);
        $sourceId = $request->source_id ?? $offer->applicable_id;

        $claim = OfferClaim::create([
            'user_id'          => $request->user()->id,
            'offer_id'         => $offer->id,
            'offer_claim_date' => $today,
            'source_type'      => $sourceType,
            'source_id'        => $sourceId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Offer claimed successfully! Bring this confirmation when booking.',
            'data'    => $claim->load(['offer', 'user']),
        ], Response::HTTP_CREATED);
    }

    // ── User: list their own claims ───────────────────────────────────────────

    public function userIndex(Request $request): JsonResponse
    {
        $claims = OfferClaim::with(['offer'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Your claimed offers retrieved successfully.',
            'data'    => $claims,
        ]);
    }

    // ── Admin: list all claims ────────────────────────────────────────────────

    public function adminIndex(Request $request): JsonResponse
    {
        $query = OfferClaim::with(['user', 'offer']);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('first_name', 'LIKE', "%{$search}%")
                       ->orWhere('last_name', 'LIKE', "%{$search}%")
                       ->orWhere('email', 'LIKE', "%{$search}%")
                       ->orWhere('username', 'LIKE', "%{$search}%");
                })->orWhereHas('offer', function ($oq) use ($search) {
                    $oq->where('title', 'LIKE', "%{$search}%");
                });
            });
        }

        if ($request->filled('offer_id')) {
            $query->where('offer_id', $request->integer('offer_id'));
        }

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);
        $claims  = $query->orderByDesc('created_at')->paginate($perPage);

        // Append display_name to each user in the result
        $items = collect($claims->items())->map(function ($claim) {
            $claim->user->append('display_name');
            return $claim;
        });

        return response()->json([
            'success' => true,
            'message' => 'Offer claims retrieved successfully.',
            'data'    => [
                'data'         => $items,
                'current_page' => $claims->currentPage(),
                'last_page'    => $claims->lastPage(),
                'total'        => $claims->total(),
                'per_page'     => $claims->perPage(),
            ],
        ]);
    }

    // ── Admin: delete a claim ────────────────────────────────────────────────

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->hasAnyRole(['Super Admin', 'Admin']) && !$request->user()->can('manage_all')) {
            abort(403, 'Only admins can perform this action.');
        }
        $claim = OfferClaim::findOrFail($id);
        $claim->delete();

        return response()->json([
            'success' => true,
            'message' => 'Claim removed successfully.',
        ]);
    }
}
