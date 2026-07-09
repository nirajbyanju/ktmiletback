<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enterprise Migration 5 — Status History Tables + Audit Log Enhancement
 *
 * STATUS HISTORY TABLES: Full immutable audit trail of every status transition.
 * These tables answer: "Who changed what, from what, to what, and when?"
 *
 *   enrollment_status_histories     — tracks enrollment status, payment_status, crm_status changes
 *   invoice_status_histories        — tracks invoice status and crm_payment_status changes
 *   demo_request_status_histories   — tracks demo request status + teacher assignment changes
 *   exam_booking_status_histories   — tracks exam booking enrollment status changes
 *
 * AUDIT LOG ENHANCEMENT: Adds operational context to the existing audit_logs table:
 *   + ip_address   — source IP of the request
 *   + user_agent   — browser/app identifier
 *   + module       — application module (enrollment, invoice, teacher, ...)
 *   + url          — request URL path for traceability
 *
 * Design decisions:
 *   • Status history rows are NEVER updated or deleted (immutable audit log)
 *   • `changed_by` is nullable — system-automated transitions have no user
 *   • `ip_address` supports IPv6 (45 chars)
 *   • Composite indexes on (entity_id, field) for fast history retrieval
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Enrollment Status History ──────────────────────────────────────
        Schema::create('enrollment_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enrollment_id')
                ->constrained('enrollments')
                ->cascadeOnDelete();

            // Which field changed: 'status', 'payment_status', or 'crm_status'
            $table->string('field', 50)->default('status')
                ->comment('Which enrollment field changed: status | payment_status | crm_status');

            $table->string('from_value', 60)->nullable()
                ->comment('Previous value. NULL if this is the first recorded transition.');

            $table->string('to_value', 60)
                ->comment('New value after the transition.');

            $table->text('note')->nullable()
                ->comment('Optional admin note explaining the reason for the change.');

            $table->foreignId('changed_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin or system user who triggered the change. NULL = automated.');

            $table->string('ip_address', 45)->nullable();
            $table->timestamp('changed_at')->useCurrent()
                ->comment('Exact moment the change was recorded. Immutable after insert.');

            $table->index(['enrollment_id', 'field'],  'esh_enrollment_field_index');
            $table->index('changed_at',                 'esh_changed_at_index');
            $table->index('changed_by',                 'esh_changed_by_index');
        });

        // ── 2. Invoice Status History ─────────────────────────────────────────
        Schema::create('invoice_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->cascadeOnDelete();

            // Which field changed: 'status' or 'crm_payment_status'
            $table->string('field', 50)->default('status')
                ->comment('Which invoice field changed: status | crm_payment_status');

            $table->string('from_value', 60)->nullable();
            $table->string('to_value', 60);

            $table->text('note')->nullable();

            $table->foreignId('changed_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('ip_address', 45)->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->index(['invoice_id', 'field'], 'ish_invoice_field_index');
            $table->index('changed_at',             'ish_changed_at_index');
        });

        // ── 3. Demo Request Status History ────────────────────────────────────
        Schema::create('demo_request_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('demo_request_id')
                ->constrained('demo_requests')
                ->cascadeOnDelete();

            // 'status' or 'teacher_id'
            $table->string('field', 50)->default('status')
                ->comment('Which field changed: status | teacher_id');

            $table->string('from_value', 100)->nullable()
                ->comment('Previous value (status key or teacher name snapshot).');

            $table->string('to_value', 100)
                ->comment('New value after the transition.');

            $table->text('note')->nullable();

            $table->foreignId('changed_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('ip_address', 45)->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->index(['demo_request_id', 'field'], 'drsh_request_field_index');
            $table->index('changed_at',                  'drsh_changed_at_index');
        });

        // ── 4. Exam Booking Status History ────────────────────────────────────
        Schema::create('exam_booking_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_booking_enrollment_id')
                ->constrained('exam_bookings_enrollments')
                ->cascadeOnDelete();

            $table->string('from_value', 60)->nullable();
            $table->string('to_value', 60);

            $table->text('note')->nullable();

            $table->foreignId('changed_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('ip_address', 45)->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->index('exam_booking_enrollment_id', 'ebsh_enrollment_index');
            $table->index('changed_at',                  'ebsh_changed_at_index');
        });

        // ── 5. Enhance audit_logs ─────────────────────────────────────────────
        // Add operational context columns if they don't already exist.
        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('user_id')
                    ->comment('Source IP address of the HTTP request (IPv4 or IPv6).');
            }
            if (!Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address')
                    ->comment('Browser or API client identifier string.');
            }
            if (!Schema::hasColumn('audit_logs', 'module')) {
                $table->string('module', 60)->nullable()->after('action')
                    ->comment('Application module (enrollment, invoice, teacher, batch, ...).');
            }
            if (!Schema::hasColumn('audit_logs', 'url')) {
                $table->string('url', 500)->nullable()->after('module')
                    ->comment('Request URL path. Useful for tracing which endpoint triggered the log.');
            }

            // Add composite index for fast module + table lookups (if not present)
            try {
                $table->index(['table_name', 'record_id'], 'audit_logs_table_record_index');
            } catch (\Throwable) {
                // Index may already exist
            }
            try {
                $table->index(['user_id', 'created_at'], 'audit_logs_user_date_index');
            } catch (\Throwable) {
                // Index may already exist
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_booking_status_histories');
        Schema::dropIfExists('demo_request_status_histories');
        Schema::dropIfExists('invoice_status_histories');
        Schema::dropIfExists('enrollment_status_histories');

        // Revert audit_logs enhancement (best effort)
        Schema::table('audit_logs', function (Blueprint $table) {
            foreach (['ip_address', 'user_agent', 'module', 'url'] as $col) {
                if (Schema::hasColumn('audit_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
            try { $table->dropIndex('audit_logs_table_record_index'); } catch (\Throwable) {}
            try { $table->dropIndex('audit_logs_user_date_index');    } catch (\Throwable) {}
        });
    }
};
