<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentStatusSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['key' => 'action_required',     'label' => 'Action Required',      'color' => 'amber',   'sort_order' => 1],
            ['key' => 'under_review',        'label' => 'Payment Under Review', 'color' => 'blue',    'sort_order' => 2],
            ['key' => 'not_verified',        'label' => 'Payment Not Verified', 'color' => 'red',     'sort_order' => 3],
            ['key' => 'fee_waived',          'label' => 'Fee Waived',           'color' => 'teal',    'sort_order' => 4],
            ['key' => 'confirmed',           'label' => 'Payment Confirmed',    'color' => 'emerald', 'sort_order' => 5],
            ['key' => 'refund_under_review', 'label' => 'Refund Under Review',  'color' => 'purple',  'sort_order' => 6],
            ['key' => 'refund_not_approved', 'label' => 'Refund Not Approved',  'color' => 'rose',    'sort_order' => 7],
            ['key' => 'refund_completed',    'label' => 'Refund Completed',     'color' => 'slate',   'sort_order' => 8],
        ];

        $now = now();
        foreach ($rows as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }

        // Upsert: insert new rows, update label/color/sort_order if key already exists
        DB::table('payment_statuses')->upsert(
            $rows,
            ['key'],                           // unique key to match on
            ['label', 'color', 'sort_order', 'updated_at']  // columns to update on conflict
        );

        $this->command->info('PaymentStatusSeeder: ' . count($rows) . ' statuses upserted.');
    }
}
