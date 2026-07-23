<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Per-product referral amounts. Replaces the single friend-discount and single
 * referrer-reward with one amount each for course / mock test / exam booking.
 *
 * Friend-discount offers are typed (course / mock_test / booking) so the right
 * amount auto-applies at the friend's checkout. Reward offers stay
 * applicable_type='all' (voucher spendable on anything); we keep one per amount
 * so an earned voucher's value never shifts.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $friendBase = DB::table('settings')->where('key', 'referral_friend_discount')->value('value') ?? '500';
        $rewardBase = DB::table('settings')->where('key', 'referral_referrer_reward')->value('value') ?? '500';

        // Six per-product amounts, seeded from the old single values.
        $amounts = [
            'referral_friend_course' => $friendBase,
            'referral_friend_mock' => $friendBase,
            'referral_friend_exam' => $friendBase,
            'referral_reward_course' => $rewardBase,
            'referral_reward_mock' => $rewardBase,
            'referral_reward_exam' => $rewardBase,
        ];
        foreach ($amounts as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value, 'created_at' => $now, 'updated_at' => $now]);
        }

        // Reuse the existing two offers as the "course" variants.
        $friendCourseId = (int) DB::table('settings')->where('key', 'referral_friend_offer_id')->value('value');
        $rewardCourseId = (int) DB::table('settings')->where('key', 'referral_reward_offer_id')->value('value');
        if ($friendCourseId) {
            DB::table('offers')->where('id', $friendCourseId)->update([
                'title' => 'Referral — Friend Welcome (Class)',
                'applicable_type' => 'course',
            ]);
        }
        if ($rewardCourseId) {
            DB::table('offers')->where('id', $rewardCourseId)->update([
                'title' => 'Referral — Referrer Reward (Class)',
                'applicable_type' => 'all',
            ]);
        }

        $makeOffer = function (string $title, string $type, string $amount) use ($now): int {
            return DB::table('offers')->insertGetId([
                'title' => $title,
                'description' => 'Internal referral voucher.',
                'badge' => 'Referral',
                'cta_text' => 'Redeem',
                'valid_date' => '2099-12-31',
                'claim_discount_amount' => $amount,
                'status' => 'active',
                'applicable_type' => $type,
                'is_referral' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        };

        // New friend offers are typed so the discount applies to that product only.
        $friendMockId = $makeOffer('Referral — Friend Welcome (Mock Test)', 'mock_test', $friendBase);
        $friendExamId = $makeOffer('Referral — Friend Welcome (Exam Booking)', 'booking', $friendBase);
        // New reward offers stay 'all' (spendable anywhere).
        $rewardMockId = $makeOffer('Referral — Referrer Reward (Mock Test)', 'all', $rewardBase);
        $rewardExamId = $makeOffer('Referral — Referrer Reward (Exam Booking)', 'all', $rewardBase);

        $offerIds = [
            'referral_friend_offer_course' => $friendCourseId,
            'referral_friend_offer_mock' => $friendMockId,
            'referral_friend_offer_exam' => $friendExamId,
            'referral_reward_offer_course' => $rewardCourseId,
            'referral_reward_offer_mock' => $rewardMockId,
            'referral_reward_offer_exam' => $rewardExamId,
        ];
        foreach ($offerIds as $key => $id) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => (string) $id, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        // Remove the offers created here (keep the two original course ones).
        foreach ([
            'referral_friend_offer_mock', 'referral_friend_offer_exam',
            'referral_reward_offer_mock', 'referral_reward_offer_exam',
        ] as $key) {
            $id = (int) DB::table('settings')->where('key', $key)->value('value');
            if ($id) {
                DB::table('offers')->where('id', $id)->delete();
            }
        }

        // Restore the two original offers to their pre-migration state.
        $friendCourseId = (int) DB::table('settings')->where('key', 'referral_friend_offer_id')->value('value');
        $rewardCourseId = (int) DB::table('settings')->where('key', 'referral_reward_offer_id')->value('value');
        if ($friendCourseId) {
            DB::table('offers')->where('id', $friendCourseId)->update(['title' => 'Referral — Friend Welcome Discount', 'applicable_type' => 'all']);
        }
        if ($rewardCourseId) {
            DB::table('offers')->where('id', $rewardCourseId)->update(['title' => 'Referral — Referrer Reward Voucher', 'applicable_type' => 'all']);
        }

        DB::table('settings')->whereIn('key', [
            'referral_friend_course', 'referral_friend_mock', 'referral_friend_exam',
            'referral_reward_course', 'referral_reward_mock', 'referral_reward_exam',
            'referral_friend_offer_course', 'referral_friend_offer_mock', 'referral_friend_offer_exam',
            'referral_reward_offer_course', 'referral_reward_offer_mock', 'referral_reward_offer_exam',
        ])->delete();
    }
};
