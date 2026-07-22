<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Each user gets a shareable referral code.
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 20)->nullable()->unique()->after('email');
        });

        // Marks the two internal referral voucher-offers so they never show on
        // public offer listings (they are granted per-user, not claimable openly).
        Schema::table('offers', function (Blueprint $table) {
            $table->boolean('is_referral')->default(false)->after('applicable_id');
        });

        // One row per referral: who referred whom, and its lifecycle.
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('referred_email')->nullable();
            // pending  = friend signed up, not yet paid
            // qualified = friend's first course payment verified → referrer rewarded
            $table->string('status', 20)->default('pending');
            $table->foreignId('friend_claim_id')->nullable()->constrained('offer_claims')->nullOnDelete();
            $table->foreignId('referrer_claim_id')->nullable()->constrained('offer_claims')->nullOnDelete();
            $table->foreignId('qualifying_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamps();
            $table->index(['referrer_id', 'status']);
        });

        // Backfill referral codes for existing users.
        DB::table('users')->whereNull('referral_code')->orderBy('id')->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'referral_code' => self::uniqueCode(),
            ]);
        });

        // Admin-configurable settings (edited in Admin → Settings → Referral).
        $now = now();
        $settings = [
            'referral_enabled' => '1',
            'referral_friend_discount' => '500',   // NPR off the friend's first course
            'referral_referrer_reward' => '500',   // NPR voucher for the referrer
            'referral_max_per_user' => '10',     // max rewarded referrals per user
        ];
        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value, 'created_at' => $now, 'updated_at' => $now]);
        }

        // Two internal voucher-offers the referral flow grants claims against.
        $friendOfferId = DB::table('offers')->insertGetId([
            'title' => 'Referral — Friend Welcome Discount',
            'description' => 'Discount for joining through a friend’s referral.',
            'badge' => 'Referral',
            'cta_text' => 'Enroll Now',
            'valid_date' => '2099-12-31',
            'claim_discount_amount' => 500,
            'status' => 'active',
            'applicable_type' => 'course',
            'is_referral' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $rewardOfferId = DB::table('offers')->insertGetId([
            'title' => 'Referral — Referrer Reward Voucher',
            'description' => 'Thank-you voucher for referring a friend who enrolled.',
            'badge' => 'Referral',
            'cta_text' => 'Use Voucher',
            'valid_date' => '2099-12-31',
            'claim_discount_amount' => 500,
            'status' => 'active',
            'applicable_type' => 'all',
            'is_referral' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('settings')->updateOrInsert(['key' => 'referral_friend_offer_id'], ['value' => (string) $friendOfferId, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('settings')->updateOrInsert(['key' => 'referral_reward_offer_id'], ['value' => (string) $rewardOfferId, 'created_at' => $now, 'updated_at' => $now]);
    }

    private static function uniqueCode(): string
    {
        do {
            $code = 'KTM'.strtoupper(Str::random(6));
        } while (DB::table('users')->where('referral_code', $code)->exists());

        return $code;
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('referral_code');
        });
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('is_referral');
        });
        DB::table('offers')->where('is_referral', true)->delete();
        DB::table('settings')->whereIn('key', [
            'referral_enabled', 'referral_friend_discount', 'referral_referrer_reward',
            'referral_max_per_user', 'referral_friend_offer_id', 'referral_reward_offer_id',
        ])->delete();
    }
};
