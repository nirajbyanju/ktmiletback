<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Let the referral friend-welcome discount apply to any purchase type
     * (course, mock test, or exam booking) instead of courses only.
     */
    public function up(): void
    {
        $id = DB::table('settings')->where('key', 'referral_friend_offer_id')->value('value');
        if ($id) {
            DB::table('offers')->where('id', (int) $id)->update(['applicable_type' => 'all']);
        }
    }

    public function down(): void
    {
        $id = DB::table('settings')->where('key', 'referral_friend_offer_id')->value('value');
        if ($id) {
            DB::table('offers')->where('id', (int) $id)->update(['applicable_type' => 'course']);
        }
    }
};
