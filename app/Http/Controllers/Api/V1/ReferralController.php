<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\Setting;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReferralController extends Controller
{
    public function __construct(private readonly ReferralService $referrals) {}

    /** Student: my referral code, program terms, and my referral history. */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $code = $this->referrals->ensureCode($user);

        $list = Referral::where('referrer_id', $user->id)
            ->with('referredUser:id,first_name,last_name,email')
            ->latest('id')
            ->get()
            ->map(fn (Referral $r) => [
                'friend_name' => $r->referredUser?->display_name ?? $r->referred_email ?? 'A friend',
                'status' => $r->status, // pending | qualified
                'joined_at' => $r->created_at?->toDateString(),
                'rewarded_at' => $r->qualified_at?->toDateString(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => Setting::getBool('referral_enabled', false),
                'referral_code' => $code,
                'friend_discount' => [
                    'course' => (float) Setting::getFloat('referral_friend_course', 500),
                    'mock' => (float) Setting::getFloat('referral_friend_mock', 500),
                    'exam' => (float) Setting::getFloat('referral_friend_exam', 500),
                ],
                'referrer_reward' => [
                    'course' => (float) Setting::getFloat('referral_reward_course', 500),
                    'mock' => (float) Setting::getFloat('referral_reward_mock', 500),
                    'exam' => (float) Setting::getFloat('referral_reward_exam', 500),
                ],
                'total_referred' => $list->count(),
                'total_qualified' => $list->where('status', Referral::STATUS_QUALIFIED)->count(),
                'referrals' => $list->values(),
            ],
        ]);
    }

    /** Public: referral program terms for the Offers & Bonuses page (no user data). */
    public function publicProgram(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => Setting::getBool('referral_enabled', false),
                'friend_discount' => [
                    'course' => (float) Setting::getFloat('referral_friend_course', 500),
                    'mock' => (float) Setting::getFloat('referral_friend_mock', 500),
                    'exam' => (float) Setting::getFloat('referral_friend_exam', 500),
                ],
                'referrer_reward' => [
                    'course' => (float) Setting::getFloat('referral_reward_course', 500),
                    'mock' => (float) Setting::getFloat('referral_reward_mock', 500),
                    'exam' => (float) Setting::getFloat('referral_reward_exam', 500),
                ],
            ],
        ]);
    }

    /** Admin: read referral program settings. */
    public function adminShow(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        return response()->json(['success' => true, 'data' => $this->settingsPayload()]);
    }

    /** Admin: update referral program settings. */
    public function adminUpdate(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'referral_enabled' => ['sometimes', 'boolean'],
            'referral_friend_course' => ['sometimes', 'numeric', 'min:0', 'max:100000'],
            'referral_friend_mock' => ['sometimes', 'numeric', 'min:0', 'max:100000'],
            'referral_friend_exam' => ['sometimes', 'numeric', 'min:0', 'max:100000'],
            'referral_reward_course' => ['sometimes', 'numeric', 'min:0', 'max:100000'],
            'referral_reward_mock' => ['sometimes', 'numeric', 'min:0', 'max:100000'],
            'referral_reward_exam' => ['sometimes', 'numeric', 'min:0', 'max:100000'],
            'referral_max_per_user' => ['sometimes', 'integer', 'min:0', 'max:1000'],
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, is_bool($value) ? ($value ? '1' : '0') : (string) $value);
        }

        return response()->json([
            'success' => true,
            'message' => 'Referral settings saved.',
            'data' => $this->settingsPayload(),
        ]);
    }

    private function settingsPayload(): array
    {
        return [
            'referral_enabled' => Setting::getBool('referral_enabled', false),
            'referral_friend_course' => (float) Setting::getFloat('referral_friend_course', 500),
            'referral_friend_mock' => (float) Setting::getFloat('referral_friend_mock', 500),
            'referral_friend_exam' => (float) Setting::getFloat('referral_friend_exam', 500),
            'referral_reward_course' => (float) Setting::getFloat('referral_reward_course', 500),
            'referral_reward_mock' => (float) Setting::getFloat('referral_reward_mock', 500),
            'referral_reward_exam' => (float) Setting::getFloat('referral_reward_exam', 500),
            'referral_max_per_user' => (int) Setting::getFloat('referral_max_per_user', 10),
        ];
    }

    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || (! $user->hasAnyRole(['Super Admin', 'Admin']) && ! $user->can('manage_all'))) {
            abort(Response::HTTP_FORBIDDEN, 'Only admins can manage referral settings.');
        }
    }
}
