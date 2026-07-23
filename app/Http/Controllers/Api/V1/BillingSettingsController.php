<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Billing & VAT settings — VAT rate and per-product applicability. */
class BillingSettingsController extends Controller
{
    /** Public: checkout pages need the rate/toggles to display estimates. */
    public function publicShow(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->payload()]);
    }

    public function show(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        return response()->json(['success' => true, 'data' => $this->payload()]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'vat_rate' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'vat_apply_course' => ['sometimes', 'required', 'boolean'],
            'vat_apply_mock_test' => ['sometimes', 'required', 'boolean'],
            'vat_apply_exam_booking' => ['sometimes', 'required', 'boolean'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, is_bool($value) ? ($value ? '1' : '0') : (string) $value);
        }

        return response()->json([
            'success' => true,
            'message' => 'Billing settings updated. New invoices will use these rules.',
            'data' => $this->payload(),
        ]);
    }

    private function payload(): array
    {
        return [
            'vat_rate' => Setting::getFloat('vat_rate', 13),
            'vat_apply_course' => Setting::getBool('vat_apply_course', true),
            'vat_apply_mock_test' => Setting::getBool('vat_apply_mock_test', true),
            'vat_apply_exam_booking' => Setting::getBool('vat_apply_exam_booking', false),
        ];
    }

    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || (! $user->hasAnyRole(['Super Admin', 'Admin']) && ! $user->can('manage_all'))) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can manage billing settings.');
        }
    }
}
