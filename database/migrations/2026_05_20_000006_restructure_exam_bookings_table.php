<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop any FK constraints on the given column using INFORMATION_SCHEMA lookup.
     */
    private function dropFksOnColumn(string $table, string $column): void
    {
        $constraints = DB::select(
            "SELECT kcu.CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE kcu
             JOIN information_schema.TABLE_CONSTRAINTS tc
               ON kcu.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
              AND kcu.TABLE_SCHEMA    = tc.TABLE_SCHEMA
              AND kcu.TABLE_NAME      = tc.TABLE_NAME
             WHERE tc.CONSTRAINT_TYPE = 'FOREIGN KEY'
               AND kcu.TABLE_SCHEMA   = DATABASE()
               AND kcu.TABLE_NAME     = ?
               AND kcu.COLUMN_NAME    = ?",
            [$table, $column]
        );

        foreach ($constraints as $c) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$c->CONSTRAINT_NAME}`");
        }
    }

    public function up(): void
    {
        // ── 1. Restructure exam_bookings to a plan-catalog table ───────────────
        // Drop old CRM / student-level columns if they exist
        $crmColumns = [
            'lead_id', 'user_id', 'student_name',
            'preferred_date', 'preferred_time',
            'test_location', 'preferred_test_centre',
            'passport_name', 'passport_number', 'date_of_birth',
            'contact_number', 'phone', 'email',
            'passport_copy_path', 'passport_copy_original_name',
            'special_message', 'status', 'payment_status',
            'available_slot_checked', 'pte_login_details_received',
            'pte_username_email', 'pte_password_notes',
            'admin_notes',
            'created_by', 'updated_by', 'deleted_by',
            'deleted_at',
        ];

        foreach ($crmColumns as $col) {
            if (Schema::hasColumn('exam_bookings', $col)) {
                $this->dropFksOnColumn('exam_bookings', $col);
            }
        }

        Schema::table('exam_bookings', function (Blueprint $table) use ($crmColumns) {
            $toDrop = array_values(array_filter($crmColumns, fn ($c) => Schema::hasColumn('exam_bookings', $c)));
            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });

        // Add plan-catalog columns and timestamps if missing
        Schema::table('exam_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_bookings', 'exam_name')) {
                $table->string('exam_name')->nullable()->after('id');
            }

            if (Schema::hasColumn('exam_bookings', 'test_type') && !Schema::hasColumn('exam_bookings', 'exam_type')) {
                $table->renameColumn('test_type', 'exam_type');
            } elseif (!Schema::hasColumn('exam_bookings', 'exam_type')) {
                $table->string('exam_type')->after('exam_name');
            }

            if (!Schema::hasColumn('exam_bookings', 'created_at')) {
                $table->timestamps();
            }
        });

        // ── 2. Create exam_bookings_enrollments if it doesn't exist ─────────────
        if (!Schema::hasTable('exam_bookings_enrollments')) {
            Schema::create('exam_bookings_enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('exam_booking_id')->constrained('exam_bookings')->cascadeOnDelete();
                $table->date('preferred_date');
                $table->time('preferred_time')->nullable();
                $table->string('test_location')->nullable();
                $table->string('preferred_test_centre')->nullable();
                $table->string('passport_name');
                $table->string('passport_number');
                $table->date('date_of_birth');
                $table->string('contact_number');
                $table->string('phone', 20)->nullable();
                $table->string('email');
                $table->string('passport_copy_path')->nullable();
                $table->string('passport_copy_original_name')->nullable();
                $table->text('special_message')->nullable();
                $table->string('status', 30)->default('new_request');
                $table->boolean('available_slot_checked')->default(false);
                $table->text('admin_notes')->nullable();
                $table->userAuditable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_bookings_enrollments');
    }
};
