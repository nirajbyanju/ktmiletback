<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Offer;
use App\Models\OfferClaim;
use App\Models\Referral;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Refer-a-friend. Reuses the existing offers/offer_claims discount machinery:
 * the friend gets a claim on the "Friend Welcome" offer at sign-up, and the
 * referrer gets a claim on the "Referrer Reward" offer only AFTER the friend's
 * first course payment is verified. All amounts/toggles live in `settings`.
 */
class ReferralService
{
    /** Product suffix => the OfferClaim source_type stamped on the friend claim. */
    private const PRODUCTS = [
        'course' => OfferClaim::SOURCE_COURSE,
        'mock' => OfferClaim::SOURCE_MOCK_TEST,
        'exam' => OfferClaim::SOURCE_BOOKING,
    ];

    /** Invoice type => product suffix used in the settings/offer keys. */
    private const TYPE_SUFFIX = [
        Invoice::TYPE_COURSE => 'course',
        Invoice::TYPE_MOCK_TEST => 'mock',
        Invoice::TYPE_EXAM => 'exam',
    ];

    public function __construct(private readonly TemplateMailer $mailer) {}

    public function isEnabled(): bool
    {
        return Setting::getBool('referral_enabled', false);
    }

    /** Generate and persist a unique referral code for a user that lacks one. */
    public function ensureCode(User $user): string
    {
        if ($user->referral_code) {
            return $user->referral_code;
        }

        do {
            $code = 'KTM'.strtoupper(Str::random(6));
        } while (User::where('referral_code', $code)->exists());

        $user->forceFill(['referral_code' => $code])->save();

        return $code;
    }

    /**
     * Called at registration when the new user entered a referral code.
     * Creates the pending referral and grants the friend their welcome discount.
     * Never throws — a bad code must not block sign-up.
     */
    public function applyReferralCode(User $newUser, ?string $code): void
    {
        try {
            if (! $this->isEnabled() || ! $code) {
                return;
            }

            $referrer = User::where('referral_code', strtoupper(trim($code)))->first();

            // Guards: referrer must exist, and no self-referral (by id or email).
            if (! $referrer
                || $referrer->id === $newUser->id
                || strcasecmp((string) $referrer->email, (string) $newUser->email) === 0) {
                return;
            }

            // One referral per new user.
            if (Referral::where('referred_user_id', $newUser->id)->exists()) {
                return;
            }

            DB::transaction(function () use ($referrer, $newUser) {
                // Grant one welcome claim per product type; the matching one
                // auto-applies at checkout. Only the first purchase keeps it —
                // rewardReferrerIfQualifying() voids the rest once one is paid.
                $firstClaimId = null;
                foreach (self::PRODUCTS as $suffix => $sourceType) {
                    $offer = $this->offer("referral_friend_offer_{$suffix}", (float) Setting::getFloat("referral_friend_{$suffix}", 500));
                    if (! $offer) {
                        continue;
                    }
                    $claim = OfferClaim::create([
                        'user_id' => $newUser->id,
                        'offer_id' => $offer->id,
                        'offer_claim_date' => now()->toDateString(),
                        'source_type' => $sourceType,
                        'source_id' => null,
                    ]);
                    $firstClaimId ??= $claim->id;
                }

                Referral::create([
                    'referrer_id' => $referrer->id,
                    'referred_user_id' => $newUser->id,
                    'referred_email' => $newUser->email,
                    'status' => Referral::STATUS_PENDING,
                    'friend_claim_id' => $firstClaimId,
                ]);
            });
        } catch (\Throwable) {
            // Referral is a bonus, never a blocker.
        }
    }

    /**
     * Called from InvoiceService::markPaid when a COURSE invoice is verified.
     * If the payer was referred and this is their qualifying payment, reward the
     * referrer with a voucher (respecting the per-user cap).
     */
    public function rewardReferrerIfQualifying(Invoice $invoice): void
    {
        try {
            // A referral qualifies on the friend's first paid purchase of any
            // kind — course, mock test, or exam booking.
            $qualifyingTypes = [Invoice::TYPE_COURSE, Invoice::TYPE_MOCK_TEST, Invoice::TYPE_EXAM];
            if (! $this->isEnabled() || ! in_array($invoice->type, $qualifyingTypes, true)) {
                return;
            }

            $referral = Referral::where('referred_user_id', $invoice->user_id)
                ->where('status', Referral::STATUS_PENDING)
                ->first();
            if (! $referral) {
                return;
            }

            // Cap: stop rewarding once the referrer hits their limit.
            $cap = (int) Setting::getFloat('referral_max_per_user', 10);
            $alreadyRewarded = Referral::where('referrer_id', $referral->referrer_id)
                ->where('status', Referral::STATUS_QUALIFIED)
                ->count();
            if ($cap > 0 && $alreadyRewarded >= $cap) {
                // Mark qualified so it doesn't retry forever, but grant no reward.
                $referral->update(['status' => Referral::STATUS_QUALIFIED, 'qualifying_invoice_id' => $invoice->id, 'qualified_at' => now()]);

                return;
            }

            // The reward amount depends on what the friend actually bought.
            $suffix = self::TYPE_SUFFIX[$invoice->type] ?? 'course';
            $rewardOffer = $this->offer("referral_reward_offer_{$suffix}", (float) Setting::getFloat("referral_reward_{$suffix}", 500));
            if (! $rewardOffer) {
                return;
            }

            DB::transaction(function () use ($referral, $rewardOffer, $invoice) {
                $rewardClaim = OfferClaim::create([
                    'user_id' => $referral->referrer_id,
                    'offer_id' => $rewardOffer->id,
                    'offer_claim_date' => now()->toDateString(),
                    'source_type' => OfferClaim::SOURCE_COURSE,
                    'source_id' => null,
                ]);

                $referral->update([
                    'status' => Referral::STATUS_QUALIFIED,
                    'referrer_claim_id' => $rewardClaim->id,
                    'qualifying_invoice_id' => $invoice->id,
                    'qualified_at' => now(),
                ]);

                // One-time: void the friend's remaining unused welcome claims
                // (the other product types) so the discount can't be reused.
                $friendOfferIds = array_filter([
                    (int) Setting::get('referral_friend_offer_course'),
                    (int) Setting::get('referral_friend_offer_mock'),
                    (int) Setting::get('referral_friend_offer_exam'),
                ]);
                if ($friendOfferIds) {
                    OfferClaim::where('user_id', $invoice->user_id)
                        ->whereIn('offer_id', $friendOfferIds)
                        ->whereNull('used_at')
                        ->update(['used_at' => now()]);
                }
            });

            $referrer = $referral->referrer;
            $friend = $invoice->user;
            $amount = 'NPR '.number_format((float) $rewardOffer->claim_discount_amount);

            if ($referrer?->email) {
                $this->mailer->sendToUser('referral_reward_earned', $referrer, [
                    'FriendName' => $friend?->display_name ?? 'your friend',
                    'RewardAmount' => $amount,
                ], ['related' => ['referral_reward', $referral->id]]);
            }
        } catch (\Throwable) {
            // Never let a reward failure break payment verification.
        }
    }

    /** Fetch a referral offer by its settings-stored id and keep its amount in sync. */
    private function offer(string $settingKey, float $amount): ?Offer
    {
        $id = (int) Setting::get($settingKey);
        if (! $id) {
            return null;
        }
        $offer = Offer::find($id);
        if ($offer && (float) $offer->claim_discount_amount !== $amount) {
            $offer->update(['claim_discount_amount' => $amount]);
        }

        return $offer;
    }
}
