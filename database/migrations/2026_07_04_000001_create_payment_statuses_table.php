<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();       // used in DB as the stored value
            $table->string('label', 80);               // human-readable display text
            $table->string('color', 20)->default('gray'); // Tailwind colour name for badge
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('payment_statuses')->insert([
            ['key' => 'action_required',    'label' => 'Action Required',      'color' => 'amber',   'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'under_review',       'label' => 'Payment Under Review', 'color' => 'blue',    'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'not_verified',       'label' => 'Payment Not Verified', 'color' => 'red',     'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'fee_waived',         'label' => 'Fee Waived',           'color' => 'teal',    'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'confirmed',          'label' => 'Payment Confirmed',    'color' => 'emerald', 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'refund_under_review','label' => 'Refund Under Review',  'color' => 'purple',  'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'refund_not_approved','label' => 'Refund Not Approved',  'color' => 'rose',    'sort_order' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'refund_completed',   'label' => 'Refund Completed',     'color' => 'slate',   'sort_order' => 8, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_statuses');
    }
};
