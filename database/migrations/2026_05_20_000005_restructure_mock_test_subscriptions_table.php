<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Replaces the old CRM-style columns with a clean plan-catalog schema.
// mock_test_subscriptions now holds the available plans/packages (admin-managed).
// Student enrollments live in mock_test_enrollments.
return new class extends Migration
{
    public function up(): void
    {
        // If user_id exists the table is still in old CRM shape — run the restructure.
        // If it doesn't exist the create migration already built the new schema; skip.
        if (!Schema::hasColumn('mock_test_subscriptions', 'user_id')) {
            return;
        }

        // 1. Drop FK on user_id before removing the column
        Schema::table('mock_test_subscriptions', function (Blueprint $table) {
            try { $table->dropForeign(['user_id']); } catch (\Throwable) {}
            try { $table->dropUnique(['subscription_id']); } catch (\Throwable) {}
        });

        // 2. Remove all old CRM / student-tracking columns (only those that exist)
        $toDrop = array_filter([
            'subscription_id', 'lead_id', 'user_id', 'student_name', 'phone',
            'course', 'package', 'payment_status', 'payment_screenshot_link',
            'login_email', 'login_sent', 'subscription_start', 'subscription_end',
            'platform', 'admin_notes',
        ], fn ($col) => Schema::hasColumn('mock_test_subscriptions', $col));

        if ($toDrop) {
            Schema::table('mock_test_subscriptions', function (Blueprint $table) use ($toDrop) {
                $table->dropColumn(array_values($toDrop));
            });
        }

        // 3. Add plan-catalog columns that are not already present
        Schema::table('mock_test_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('mock_test_subscriptions', 'subscriptions_name')) {
                $table->string('subscriptions_name')->after('id');
            }
            if (!Schema::hasColumn('mock_test_subscriptions', 'subscriptions_type')) {
                $table->string('subscriptions_type')->after('subscriptions_name');
            }
            if (!Schema::hasColumn('mock_test_subscriptions', 'subscriptions_category')) {
                $table->string('subscriptions_category')->after('subscriptions_type');
            }
            if (!Schema::hasColumn('mock_test_subscriptions', 'company_name')) {
                $table->string('company_name')->after('subscriptions_category');
            }
            if (!Schema::hasColumn('mock_test_subscriptions', 'country')) {
                $table->string('country')->after('company_name');
            }
            if (!Schema::hasColumn('mock_test_subscriptions', 'duration')) {
                $table->integer('duration')->after('discount');
            }
            if (!Schema::hasColumn('mock_test_subscriptions', 'duration_type')) {
                $table->string('duration_type')->after('duration');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mock_test_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'subscriptions_name', 'subscriptions_type', 'subscriptions_category',
                'company_name', 'country', 'duration', 'duration_type',
            ]);
        });

        Schema::table('mock_test_subscriptions', function (Blueprint $table) {
            $table->string('subscription_id', 30)->nullable()->unique()->after('id');
            $table->string('lead_id', 30)->nullable()->after('subscription_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->after('lead_id');
            $table->string('student_name')->after('user_id');
            $table->string('phone', 20)->nullable()->after('student_name');
            $table->string('course')->nullable()->after('phone');
            $table->string('package')->nullable()->after('course');
            $table->string('payment_status', 30)->default('not_paid')->after('discount');
            $table->string('payment_screenshot_link', 2048)->nullable()->after('payment_status');
            $table->string('login_email')->nullable()->after('payment_screenshot_link');
            $table->boolean('login_sent')->default(false)->after('login_email');
            $table->date('subscription_start')->nullable()->after('login_sent');
            $table->date('subscription_end')->nullable()->after('subscription_start');
            $table->string('platform', 100)->nullable()->after('subscription_end');
            $table->text('admin_notes')->nullable()->after('platform');
        });
    }
};
