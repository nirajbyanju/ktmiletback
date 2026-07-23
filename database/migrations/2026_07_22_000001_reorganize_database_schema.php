<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename userCode to user_code in users table
        if (Schema::hasColumn('users', 'userCode') && !Schema::hasColumn('users', 'user_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('userCode', 'user_code');
            });
        }

        // Helper to safely add indexes if they don't exist
        $addIndexSafely = function (string $table, string $column) {
            try {
                Schema::table($table, function (Blueprint $t) use ($column) {
                    $t->index($column);
                });
            } catch (\Throwable $e) {
                // Index already exists, ignore
            }
        };

        // 2. Add performance indexes safely
        $addIndexSafely('enrollments', 'user_id');
        $addIndexSafely('enrollments', 'batch_id');
        $addIndexSafely('enrollments', 'invoice_id');

        $addIndexSafely('invoices', 'user_id');
        $addIndexSafely('invoices', 'batch_id');

        $addIndexSafely('demo_requests', 'user_id');
        $addIndexSafely('demo_requests', 'course_id');
        $addIndexSafely('demo_requests', 'batch_id');

        $addIndexSafely('teachers', 'user_id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $dropIndexSafely = function (string $table, string $column) {
            try {
                Schema::table($table, function (Blueprint $t) use ($column) {
                    $t->dropIndex([$column]);
                });
            } catch (\Throwable $e) {
                // Ignore if index doesn't exist
            }
        };

        $dropIndexSafely('teachers', 'user_id');
        $dropIndexSafely('demo_requests', 'batch_id');
        $dropIndexSafely('demo_requests', 'course_id');
        $dropIndexSafely('demo_requests', 'user_id');
        $dropIndexSafely('invoices', 'batch_id');
        $dropIndexSafely('invoices', 'user_id');
        $dropIndexSafely('enrollments', 'invoice_id');
        $dropIndexSafely('enrollments', 'batch_id');
        $dropIndexSafely('enrollments', 'user_id');

        if (Schema::hasColumn('users', 'user_code') && !Schema::hasColumn('users', 'userCode')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('user_code', 'userCode');
            });
        }
    }
};
