<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Comprehensive database optimization — July 2026
 *
 * Problems fixed:
 *
 * INDEXES (missing, causing full-table scans on hot paths):
 *   1.  enrollments  — composite (user_id, batch_id)   → duplicate-guard query + roster fetch
 *   2.  enrollments  — composite (user_id, payment_status) → student dashboard + CRM filter
 *   3.  enrollments  — (payment_status)                → admin stats counting
 *   4.  enrollments  — (crm_status)                    → admin CRM filter
 *   5.  invoices     — composite (user_id, status)     → "has paid invoice?" guard
 *   6.  invoices     — composite (batch_id, status)    → enrolment duplicate check
 *   7.  invoices     — (status)                        → status filter on admin invoice list
 *   8.  exam_bookings_enrollments — composite (user_id, status) → student exam history
 *   9.  demo_requests— (user_id, status)               → student demo-request list
 *   10. demo_requests— (course_id)                     → filter by course
 *   11. batches      — (is_active)                     → public batch listing (active only)
 *   12. batches      — (course_id, is_active)          → course page batch filter
 *   13. users        — (google_id)                     → OAuth login lookup
 *
 * UNIQUE CONSTRAINTS (data-integrity gaps):
 *   14. enrollments  — UNIQUE (invoice_id)             → one enrollment record per invoice
 *
 * FOREIGN KEY INTEGRITY:
 *   15. demo_requests.course_id → courses.id           → currently unconstrained (nullable FK)
 *
 * BATCH TYPE LINKAGE:
 *   16. batches      — add batch_type_id (FK → batch_types.id, nullable)
 *                      populate from batch_types.name match
 *                      existing varchar batch_type column left in place for rollout safety;
 *                      application code should migrate to batch_type_id gradually
 *
 * NOTE: invoice_number unique already exists (created in create_invoices_table).
 *       users.google_id column added by add_google_auth_to_users_table; just needs index.
 */
return new class extends Migration
{
    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /** Returns true when the named index already exists on the table. */
    private function hasIndex(string $table, string $indexName): bool
    {
        $rows = DB::select(
            "SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = ?
               AND INDEX_NAME   = ?
             LIMIT 1",
            [$table, $indexName]
        );
        return !empty($rows);
    }

    /** Returns true when the named FK constraint exists. */
    private function hasForeignKey(string $table, string $constraint): bool
    {
        $rows = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA    = DATABASE()
               AND TABLE_NAME      = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'
             LIMIT 1",
            [$table, $constraint]
        );
        return !empty($rows);
    }

    // ─────────────────────────────────────────────────────────────────────────
    public function up(): void
    {
        // ── 1–5. enrollments indexes ─────────────────────────────────────────
        Schema::table('enrollments', function (Blueprint $table) {
            // Composite: duplicate-guard queries always filter by both columns
            if (!$this->hasIndex('enrollments', 'enrollments_user_id_batch_id_index')) {
                $table->index(['user_id', 'batch_id'], 'enrollments_user_id_batch_id_index');
            }
            // Composite: student dashboard + payment-status CRM filters
            if (!$this->hasIndex('enrollments', 'enrollments_user_id_payment_status_index')) {
                $table->index(['user_id', 'payment_status'], 'enrollments_user_id_payment_status_index');
            }
            // Standalone: adminStats counting per status
            if (!$this->hasIndex('enrollments', 'enrollments_payment_status_index')) {
                $table->index('payment_status', 'enrollments_payment_status_index');
            }
            // Standalone: adminIndex CRM filter
            if (!$this->hasIndex('enrollments', 'enrollments_crm_status_index')) {
                $table->index('crm_status', 'enrollments_crm_status_index');
            }
            // Unique constraint: one Enrollment per Invoice (data integrity)
            // Skips rows where invoice_id IS NULL (MySQL unique allows multiple NULLs)
            if (!$this->hasIndex('enrollments', 'enrollments_invoice_id_unique')) {
                $table->unique('invoice_id', 'enrollments_invoice_id_unique');
            }
        });

        // ── 6–7. invoices indexes ────────────────────────────────────────────
        Schema::table('invoices', function (Blueprint $table) {
            // Composite: "does this user have an active/paid invoice for this batch?"
            if (!$this->hasIndex('invoices', 'invoices_user_id_status_index')) {
                $table->index(['user_id', 'status'], 'invoices_user_id_status_index');
            }
            // Composite: batch enrollment duplicate check
            if (!$this->hasIndex('invoices', 'invoices_batch_id_status_index')) {
                $table->index(['batch_id', 'status'], 'invoices_batch_id_status_index');
            }
            // Standalone: admin invoice list filtered by status
            if (!$this->hasIndex('invoices', 'invoices_status_index')) {
                $table->index('status', 'invoices_status_index');
            }
        });

        // ── 8. exam_bookings_enrollments indexes ─────────────────────────────
        Schema::table('exam_bookings_enrollments', function (Blueprint $table) {
            if (!$this->hasIndex('exam_bookings_enrollments', 'ebe_user_id_status_index')) {
                $table->index(['user_id', 'status'], 'ebe_user_id_status_index');
            }
        });

        // ── 9–10. demo_requests indexes + FK ────────────────────────────────
        Schema::table('demo_requests', function (Blueprint $table) {
            if (!$this->hasIndex('demo_requests', 'demo_requests_user_id_status_index')) {
                $table->index(['user_id', 'status'], 'demo_requests_user_id_status_index');
            }
            if (!$this->hasIndex('demo_requests', 'demo_requests_course_id_index')) {
                $table->index('course_id', 'demo_requests_course_id_index');
            }
            // FK integrity: course_id was an unconstrained unsignedBigInteger
            if (!$this->hasForeignKey('demo_requests', 'demo_requests_course_id_foreign')) {
                $table->foreign('course_id', 'demo_requests_course_id_foreign')
                    ->references('id')->on('courses')
                    ->nullOnDelete();
            }
        });

        // ── 11–12. batches indexes ───────────────────────────────────────────
        Schema::table('batches', function (Blueprint $table) {
            if (!$this->hasIndex('batches', 'batches_is_active_index')) {
                $table->index('is_active', 'batches_is_active_index');
            }
            if (!$this->hasIndex('batches', 'batches_course_id_is_active_index')) {
                $table->index(['course_id', 'is_active'], 'batches_course_id_is_active_index');
            }
        });

        // ── 13. users.google_id index ────────────────────────────────────────
        if (Schema::hasColumn('users', 'google_id')) {
            Schema::table('users', function (Blueprint $table) {
                if (!$this->hasIndex('users', 'users_google_id_index')) {
                    $table->index('google_id', 'users_google_id_index');
                }
            });
        }

        // ── 16. batches.batch_type_id → batch_types.id ──────────────────────
        //
        // Adds a proper FK column alongside the legacy varchar batch_type column.
        // Populates batch_type_id by matching batch_types.name = batches.batch_type.
        // If a batch_type value has no matching row in batch_types, a new row is
        // auto-inserted (preserves all existing data; admin can clean up later).
        // The varchar batch_type column is kept for rollout safety; application code
        // should switch to batch_type_id and the old column can be dropped next cycle.
        if (!Schema::hasColumn('batches', 'batch_type_id')) {
            Schema::table('batches', function (Blueprint $table) {
                $table->unsignedBigInteger('batch_type_id')->nullable()->after('batch_type');
                $table->foreign('batch_type_id', 'batches_batch_type_id_foreign')
                    ->references('id')->on('batch_types')
                    ->nullOnDelete();
                $table->index('batch_type_id', 'batches_batch_type_id_index');
            });

            // Seed any missing batch_type values into batch_types
            $existingTypes = DB::table('batch_types')->pluck('id', 'name');
            $batchTypeValues = DB::table('batches')
                ->whereNotNull('batch_type')
                ->distinct()
                ->pluck('batch_type');

            $nextSort = (int) DB::table('batch_types')->max('sort_order') + 1;

            foreach ($batchTypeValues as $typeName) {
                if (!isset($existingTypes[$typeName])) {
                    $id = DB::table('batch_types')->insertGetId([
                        'name'       => $typeName,
                        'sort_order' => $nextSort++,
                        'is_active'  => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $existingTypes[$typeName] = $id;
                }
            }

            // Populate batch_type_id from the map
            foreach ($existingTypes as $name => $id) {
                DB::table('batches')
                    ->where('batch_type', $name)
                    ->update(['batch_type_id' => $id]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    public function down(): void
    {
        // batch_type_id linkage
        if (Schema::hasColumn('batches', 'batch_type_id')) {
            Schema::table('batches', function (Blueprint $table) {
                if ($this->hasForeignKey('batches', 'batches_batch_type_id_foreign')) {
                    $table->dropForeign('batches_batch_type_id_foreign');
                }
                if ($this->hasIndex('batches', 'batches_batch_type_id_index')) {
                    $table->dropIndex('batches_batch_type_id_index');
                }
                $table->dropColumn('batch_type_id');
            });
        }

        // demo_requests FK
        if ($this->hasForeignKey('demo_requests', 'demo_requests_course_id_foreign')) {
            Schema::table('demo_requests', function (Blueprint $table) {
                $table->dropForeign('demo_requests_course_id_foreign');
            });
        }

        // Drop all added indexes (best-effort; wrapped in try/catch)
        $drops = [
            'enrollments' => [
                'enrollments_user_id_batch_id_index',
                'enrollments_user_id_payment_status_index',
                'enrollments_payment_status_index',
                'enrollments_crm_status_index',
                'enrollments_invoice_id_unique',
            ],
            'invoices' => [
                'invoices_user_id_status_index',
                'invoices_batch_id_status_index',
                'invoices_status_index',
            ],
            'exam_bookings_enrollments' => ['ebe_user_id_status_index'],
            'demo_requests'             => ['demo_requests_user_id_status_index', 'demo_requests_course_id_index'],
            'batches'                   => ['batches_is_active_index', 'batches_course_id_is_active_index'],
            'users'                     => ['users_google_id_index'],
        ];

        foreach ($drops as $tbl => $indexes) {
            foreach ($indexes as $idx) {
                try {
                    Schema::table($tbl, function (Blueprint $table) use ($idx) {
                        $table->dropIndex($idx);
                    });
                } catch (\Throwable) {
                    // Index may not exist — safe to ignore
                }
            }
        }
    }
};
