<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a lightweight CRM-display status field to invoices.
     *
     * Keeps the core `status` column (unpaid / paid / cancelled / refunded)
     * untouched, but lets admins set a human-readable payment progress label
     * that maps to the 8 standard statuses shown on both dashboards:
     *
     *   action_required | under_review | not_verified | fee_waived |
     *   confirmed       | refund_under_review | refund_not_approved | refund_completed
     *
     * When NULL the UI auto-derives the label from status + screenshot fields.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('crm_payment_status', 50)
                  ->nullable()
                  ->after('status')
                  ->comment('Admin-settable display status; null = auto-derive from status + screenshot fields');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('crm_payment_status');
        });
    }
};
