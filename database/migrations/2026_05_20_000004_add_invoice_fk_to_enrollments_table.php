<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Retroactively adds enrollments.invoice_id FK for databases that ran
// add_fks_to_invoices_table before the enrollment FK was included there.
// Fresh installs already get this FK via that migration; the guard prevents duplicates.
return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'enrollments'
              AND CONSTRAINT_NAME = 'enrollments_invoice_id_foreign'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");

        if (empty($exists)) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->foreign('invoice_id')
                    ->references('id')->on('invoices')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('enrollments', 'invoice_id')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropForeign(['invoice_id']);
            });
        }
    }
};
