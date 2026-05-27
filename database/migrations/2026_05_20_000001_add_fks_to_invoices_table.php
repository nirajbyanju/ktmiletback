<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Adds FK constraints deferred from the base invoices migration because
// mock_test_subscriptions and exam_bookings are created in later migrations.
return new class extends Migration
{
    private function fkExists(string $table, string $constraint): bool
    {
        return !empty(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $constraint]
        ));
    }

    public function up(): void
    {
        if (!$this->fkExists('invoices', 'invoices_mock_test_subscription_id_foreign')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreign('mock_test_subscription_id')
                    ->references('id')->on('mock_test_subscriptions')
                    ->nullOnDelete();
            });
        }

        if (!$this->fkExists('enrollments', 'enrollments_invoice_id_foreign')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->foreign('invoice_id')
                    ->references('id')->on('invoices')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['mock_test_subscription_id']);
        });
    }
};
