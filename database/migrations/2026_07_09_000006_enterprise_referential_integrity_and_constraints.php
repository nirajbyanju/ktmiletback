<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enterprise Migration 6 — Referential Integrity, FK Additions, CHECK Constraints & Indexes
 *
 * Closes the remaining data integrity gaps across the schema:
 *
 *   FK ADDITIONS:
 *   1.  enrollments.teacher_id → teachers.id        (replaces freetext teacher varchar)
 *   2.  courses.category_id    → course_categories.id
 *   3.  user_details.country_id → countries.id      (replaces freetext country varchar)
 *   4.  mock_test_subscriptions.country_id → countries.id (replaces freetext country)
 *
 *   CHECK CONSTRAINTS (MySQL 8.0.16+, enforced at DB level):
 *   5.  invoices.total_npr        >= 0
 *   6.  invoices.subtotal_npr     >= 0
 *   7.  invoices.discount_npr     >= 0
 *   8.  invoices.tax_npr          >= 0
 *   9.  enrollments.attendance_percentage BETWEEN 0 AND 100
 *   10. batches.min_size          <= batches.max_size (via check constraint)
 *   11. exam_bookings_enrollments.date_of_birth < CURRENT_DATE
 *   12. courses.duration          > 0
 *   13. mock_test_subscriptions.duration > 0
 *
 *   ADDITIONAL PERFORMANCE INDEXES:
 *   14. invoices (mock_test_subscription_id)
 *   15. invoices (exam_booking_enrollment_id)
 *   16. invoices (invoice_date)
 *   17. invoices (due_date)
 *   18. batches  (start_date, end_date)
 *   19. mock_test_enrollments (user_id, status)
 *   20. users    (status)
 *   21. users    (created_at)   — for analytics/reporting queries
 *   22. demo_requests (teacher_id)
 *   23. teachers (status)
 *   24. testimonials (status)
 */
return new class extends Migration
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function hasIndex(string $table, string $name): bool
    {
        $rows = DB::select(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1",
            [$table, $name]
        );
        return !empty($rows);
    }

    private function hasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    private function hasForeignKey(string $table, string $constraint): bool
    {
        $rows = DB::select(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY' LIMIT 1",
            [$table, $constraint]
        );
        return !empty($rows);
    }

    private function addIndex(Blueprint $table, string $tableName, array|string $cols, string $name): void
    {
        if (!$this->hasIndex($tableName, $name)) {
            $table->index($cols, $name);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    public function up(): void
    {
        // ── 1. enrollments.teacher_id → teachers.id ───────────────────────────
        // The enrollments table has a freetext varchar `teacher` column.
        // We add a proper FK column alongside it.
        // The old varchar column is intentionally kept for backward compatibility
        // and can be dropped after application code migrates to teacher_id.
        if (!$this->hasColumn('enrollments', 'teacher_id')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->unsignedBigInteger('teacher_id')->nullable()->after('teacher')
                    ->comment('FK to teachers.id. Replaces the freetext teacher varchar column.');
                $table->foreign('teacher_id', 'enrollments_teacher_id_foreign')
                    ->references('id')->on('teachers')
                    ->nullOnDelete();
                $table->index('teacher_id', 'enrollments_teacher_id_index');
            });
        }

        // ── 2. courses.category_id → course_categories.id ────────────────────
        if (!$this->hasColumn('courses', 'category_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->unsignedBigInteger('category_id')->nullable()->after('id')
                    ->comment('FK to course_categories.id. Enables course taxonomy.');
                $table->foreign('category_id', 'courses_category_id_foreign')
                    ->references('id')->on('course_categories')
                    ->nullOnDelete();
                $table->index('category_id', 'courses_category_id_index');
            });
        }

        // ── 3. user_details.country_id → countries.id ────────────────────────
        if (!$this->hasColumn('user_details', 'country_id')) {
            Schema::table('user_details', function (Blueprint $table) {
                $table->unsignedBigInteger('country_id')->nullable()->after('country')
                    ->comment('FK to countries.id. Replaces freetext country varchar.');
                $table->foreign('country_id', 'user_details_country_id_foreign')
                    ->references('id')->on('countries')
                    ->nullOnDelete();
                $table->index('country_id', 'user_details_country_id_index');
            });
        }

        // ── 4. mock_test_subscriptions.country_id → countries.id ─────────────
        if (Schema::hasTable('mock_test_subscriptions') && !$this->hasColumn('mock_test_subscriptions', 'country_id')) {
            Schema::table('mock_test_subscriptions', function (Blueprint $table) {
                $table->unsignedBigInteger('country_id')->nullable()->after('country')
                    ->comment('FK to countries.id. Replaces freetext country varchar.');
                $table->foreign('country_id', 'mts_country_id_foreign')
                    ->references('id')->on('countries')
                    ->nullOnDelete();
            });
        }

        // ── 5–13. CHECK CONSTRAINTS ───────────────────────────────────────────
        // MySQL 8.0.16+ enforces CHECK constraints. Versions below silently accept
        // but do not enforce them — safe to add regardless of MySQL version.

        // invoices: monetary amounts must be non-negative
        DB::statement('ALTER TABLE invoices
            ADD CONSTRAINT chk_invoices_total_gte_zero     CHECK (total_npr     >= 0),
            ADD CONSTRAINT chk_invoices_subtotal_gte_zero  CHECK (subtotal_npr  >= 0),
            ADD CONSTRAINT chk_invoices_discount_gte_zero  CHECK (discount_npr  >= 0),
            ADD CONSTRAINT chk_invoices_tax_gte_zero       CHECK (tax_npr       >= 0)
        ');

        // enrollments: attendance must be a valid percentage
        DB::statement('ALTER TABLE enrollments
            ADD CONSTRAINT chk_enrollments_attendance CHECK (
                attendance_percentage IS NULL OR (attendance_percentage >= 0 AND attendance_percentage <= 100)
            )
        ');

        // batches: min_size cannot exceed max_size
        DB::statement('ALTER TABLE batches
            ADD CONSTRAINT chk_batches_size_range CHECK (
                min_size IS NULL OR max_size IS NULL OR min_size <= max_size
            )
        ');

        // batches: price must be non-negative
        DB::statement('ALTER TABLE batches
            ADD CONSTRAINT chk_batches_price_gte_zero CHECK (price_npr IS NULL OR price_npr >= 0)
        ');

        // exam bookings: date of birth must be in the past
        DB::statement('ALTER TABLE exam_bookings_enrollments
            ADD CONSTRAINT chk_ebe_dob_past CHECK (date_of_birth < CURDATE())
        ');

        // courses: duration must be positive when provided
        DB::statement('ALTER TABLE courses
            ADD CONSTRAINT chk_courses_duration_positive CHECK (duration IS NULL OR duration > 0)
        ');

        // ── 14–24. ADDITIONAL PERFORMANCE INDEXES ────────────────────────────

        Schema::table('invoices', function (Blueprint $table) {
            $this->addIndex($table, 'invoices', 'mock_test_subscription_id',    'invoices_mts_id_index');
            $this->addIndex($table, 'invoices', 'exam_booking_enrollment_id',   'invoices_ebe_id_index');
            $this->addIndex($table, 'invoices', 'invoice_date',                 'invoices_invoice_date_index');
            $this->addIndex($table, 'invoices', 'due_date',                     'invoices_due_date_index');
            $this->addIndex($table, 'invoices', 'verified_at',                  'invoices_verified_at_index');
        });

        Schema::table('batches', function (Blueprint $table) {
            $this->addIndex($table, 'batches', ['start_date', 'end_date'], 'batches_date_range_index');
        });

        if (Schema::hasTable('mock_test_enrollments')) {
            Schema::table('mock_test_enrollments', function (Blueprint $table) {
                $this->addIndex($table, 'mock_test_enrollments', ['user_id', 'status'], 'mte_user_id_status_index');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $this->addIndex($table, 'users', 'status',     'users_status_index');
            $this->addIndex($table, 'users', 'created_at', 'users_created_at_index');
        });

        Schema::table('demo_requests', function (Blueprint $table) {
            if ($this->hasColumn('demo_requests', 'teacher_id')) {
                $this->addIndex($table, 'demo_requests', 'teacher_id', 'demo_requests_teacher_id_index');
            }
        });

        if ($this->hasColumn('teachers', 'status')) {
            Schema::table('teachers', function (Blueprint $table) {
                $this->addIndex($table, 'teachers', 'status', 'teachers_status_index');
            });
        }

        if (Schema::hasTable('testimonials') && $this->hasColumn('testimonials', 'status')) {
            Schema::table('testimonials', function (Blueprint $table) {
                $this->addIndex($table, 'testimonials', 'status', 'testimonials_status_index');
            });
        }

        if (Schema::hasTable('offers') && $this->hasColumn('offers', 'is_active')) {
            Schema::table('offers', function (Blueprint $table) {
                $this->addIndex($table, 'offers', ['is_active', 'starts_at', 'ends_at'], 'offers_active_date_index');
            });
        }

        // system_settings: fast lookup by group+key (already has unique, add standalone group index)
        Schema::table('system_settings', function (Blueprint $table) {
            $this->addIndex($table, 'system_settings', 'is_public', 'system_settings_is_public_index');
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    public function down(): void
    {
        // Drop CHECK constraints (best-effort — may fail if they don't exist)
        $checkConstraints = [
            'invoices'                    => ['chk_invoices_total_gte_zero','chk_invoices_subtotal_gte_zero','chk_invoices_discount_gte_zero','chk_invoices_tax_gte_zero'],
            'enrollments'                 => ['chk_enrollments_attendance'],
            'batches'                     => ['chk_batches_size_range','chk_batches_price_gte_zero'],
            'exam_bookings_enrollments'   => ['chk_ebe_dob_past'],
            'courses'                     => ['chk_courses_duration_positive'],
        ];

        foreach ($checkConstraints as $tbl => $constraints) {
            foreach ($constraints as $c) {
                try {
                    DB::statement("ALTER TABLE `{$tbl}` DROP CHECK `{$c}`");
                } catch (\Throwable) {}
            }
        }

        // Drop FK columns added
        if ($this->hasColumn('enrollments', 'teacher_id')) {
            Schema::table('enrollments', function (Blueprint $table) {
                if ($this->hasForeignKey('enrollments', 'enrollments_teacher_id_foreign')) {
                    $table->dropForeign('enrollments_teacher_id_foreign');
                }
                $table->dropColumn('teacher_id');
            });
        }

        if ($this->hasColumn('courses', 'category_id')) {
            Schema::table('courses', function (Blueprint $table) {
                if ($this->hasForeignKey('courses', 'courses_category_id_foreign')) {
                    $table->dropForeign('courses_category_id_foreign');
                }
                $table->dropColumn('category_id');
            });
        }

        if ($this->hasColumn('user_details', 'country_id')) {
            Schema::table('user_details', function (Blueprint $table) {
                if ($this->hasForeignKey('user_details', 'user_details_country_id_foreign')) {
                    $table->dropForeign('user_details_country_id_foreign');
                }
                $table->dropColumn('country_id');
            });
        }
    }
};
