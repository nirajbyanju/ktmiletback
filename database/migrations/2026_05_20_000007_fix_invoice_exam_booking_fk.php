<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the stale exam_booking_id FK on invoices if it exists (was wrong column name)
        if (Schema::hasColumn('invoices', 'exam_booking_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                try { $table->dropForeign(['exam_booking_id']); } catch (\Throwable) {}
                $table->dropColumn('exam_booking_id');
            });
        }

        // Add exam_booking_enrollment_id column if not present
        if (!Schema::hasColumn('invoices', 'exam_booking_enrollment_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('exam_booking_enrollment_id')
                    ->nullable()
                    ->after('mock_test_subscription_id')
                    ->constrained('exam_bookings_enrollments')
                    ->nullOnDelete();
            });
            return;
        }

        // Column exists — just add FK constraint if not already there
        $exists = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'invoices'
               AND CONSTRAINT_NAME = 'invoices_exam_booking_enrollment_id_foreign'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        );

        if (empty($exists)) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreign('exam_booking_enrollment_id')
                    ->references('id')->on('exam_bookings_enrollments')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            try { $table->dropForeign(['exam_booking_enrollment_id']); } catch (\Throwable) {}
        });
    }
};
