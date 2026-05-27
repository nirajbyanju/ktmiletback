<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\MockTestEnrollment;
use App\Models\MockTestSubscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class MockTestEnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $students      = User::role('User')->get();
        $subscriptions = MockTestSubscription::all();

        if ($students->isEmpty() || $subscriptions->isEmpty()) {
            $this->command->warn('No students or mock test plans found — skipping MockTestEnrollmentSeeder.');
            return;
        }

        $paymentMethods  = ['bank_transfer', 'esewa', 'khalti', 'cash'];
        $invoiceCounter  = 3000;

        foreach ($students->take(10) as $i => $student) {
            $sub     = $subscriptions[$i % $subscriptions->count()];
            $price   = (float) $sub->price;
            $disc    = (float) ($sub->discount ?? 0);
            $net     = $price - $disc;
            $isPaid  = $i % 3 !== 0;   // 2/3 students are paid

            $invoiceDate = Carbon::now()->subDays(rand(5, 60));
            $dueDate     = $invoiceDate->copy()->addDays(7);
            $invoiceNo   = 'KTM-MOCK-' . ++$invoiceCounter;

            $invoice = Invoice::updateOrCreate(
                ['invoice_number' => $invoiceNo],
                [
                    'user_id'                  => $student->id,
                    'mock_test_subscription_id' => $sub->id,
                    'subtotal_npr'             => $price,
                    'discount_npr'             => $disc,
                    'tax_npr'                  => 0,
                    'total_npr'                => $net,
                    'status'                   => $isPaid ? Invoice::STATUS_PAID : Invoice::STATUS_UNPAID,
                    'payment_method'           => $isPaid ? $paymentMethods[$i % count($paymentMethods)] : 'bank_qr',
                    'invoice_date'             => $invoiceDate->toDateString(),
                    'due_date'                 => $dueDate->toDateString(),
                    'verified_at'              => $isPaid ? $invoiceDate->copy()->addDays(1) : null,
                ]
            );

            if ($isPaid) {
                $start = $invoiceDate->copy()->addDay();
                $end   = $start->copy()->addDays((int) ($sub->duration ?? 30));

                MockTestEnrollment::updateOrCreate(
                    ['user_id' => $student->id, 'subscription_id' => $sub->id],
                    [
                        'invoice_id'          => $invoice->id,
                        'enrollment_date'     => $invoiceDate->toDateString(),
                        'subscription_start'  => $start->toDateString(),
                        'subscription_end'    => $end->toDateString(),
                    ]
                );
            }
        }

        $this->command->info('Created mock test invoices and enrollments for students.');
    }
}
