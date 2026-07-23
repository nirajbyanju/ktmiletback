<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value')->nullable();
            $table->timestamps();
        });

        // Billing & VAT defaults (per Bimal, 12 July 2026):
        // VAT 13%, applied to class bookings + mock tests; exam bookings
        // VAT-free for now (toggleable in admin for the future).
        $now = now();
        DB::table('settings')->insert([
            ['key' => 'vat_rate',               'value' => '13', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'vat_apply_course',       'value' => '1',  'created_at' => $now, 'updated_at' => $now],
            ['key' => 'vat_apply_mock_test',    'value' => '1',  'created_at' => $now, 'updated_at' => $now],
            ['key' => 'vat_apply_exam_booking', 'value' => '0',  'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
