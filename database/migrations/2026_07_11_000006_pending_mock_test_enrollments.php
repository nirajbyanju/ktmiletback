<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mock test subscriptions now create a PENDING enrollment record at
     * invoice time (like course bookings do), so admins can see who is
     * subscribing before payment. Dates are set when payment is confirmed,
     * so they must be nullable.
     */
    public function up(): void
    {
        Schema::table('mock_test_enrollments', function (Blueprint $table) {
            $table->date('subscription_start')->nullable()->change();
            $table->date('subscription_end')->nullable()->change();
        });

        // Backfill: create pending enrollment records for existing unpaid
        // mock-test invoices that have none yet.
        $pending = DB::table('invoices')
            ->whereNotNull('mock_test_subscription_id')
            ->where('status', 'unpaid')
            ->whereNull('deleted_at')
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('mock_test_enrollments')
                    ->whereColumn('mock_test_enrollments.invoice_id', 'invoices.id');
            })
            ->get(['id', 'user_id', 'mock_test_subscription_id', 'invoice_date', 'created_at']);

        foreach ($pending as $inv) {
            DB::table('mock_test_enrollments')->insert([
                'subscription_id' => $inv->mock_test_subscription_id,
                'invoice_id' => $inv->id,
                'user_id' => $inv->user_id,
                'enrollment_date' => $inv->invoice_date ?? substr((string) $inv->created_at, 0, 10),
                'subscription_start' => null,
                'subscription_end' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Cannot cleanly reverse the backfill; keep records, restore NOT NULL is unsafe.
    }
};
