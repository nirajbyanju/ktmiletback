<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Normalises enrollments.payment_status to snake_case keys that match the
 * payment_statuses lookup table.
 *
 * Maps interim label-string values that may exist in the DB to their
 * canonical snake_case keys. Old DB values (pending, partial, paid, waived,
 * refunded) have been removed from the system; use action_required, confirmed,
 * etc. directly everywhere.
 */
return new class extends Migration
{
    private const MAP = [
        'Action Required'      => 'action_required',
        'Payment Under Review' => 'under_review',
        'Payment Not Verified' => 'not_verified',
        'Fee Waived'           => 'fee_waived',
        'Payment Confirmed'    => 'confirmed',
        'Refund Under Review'  => 'refund_under_review',
        'Refund Not Approved'  => 'refund_not_approved',
        'Refund Completed'     => 'refund_completed',
    ];

    public function up(): void
    {
        foreach (self::MAP as $old => $new) {
            DB::table('enrollments')->where('payment_status', $old)->update(['payment_status' => $new]);
        }

        // Ensure column default is the canonical key
        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('payment_status', 40)->default('action_required')->change();
        });
    }

    public function down(): void
    {
        // Reverse label-string normalisation (best effort)
        foreach (self::MAP as $old => $new) {
            DB::table('enrollments')->where('payment_status', $new)->update(['payment_status' => $old]);
        }

        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('payment_status', 40)->default('action_required')->change();
        });
    }
};
