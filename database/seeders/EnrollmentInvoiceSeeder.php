<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EnrollmentInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::role('User')->get();
        $batches  = Batch::with('course')->whereNotNull('price_npr')->get();

        if ($students->isEmpty() || $batches->isEmpty()) {
            $this->command->warn('No students or priced batches found — skipping EnrollmentInvoiceSeeder.');
            return;
        }

        $crmStatuses     = ['lead', 'contacted', 'enrolled', 'completed', 'dropped'];
        $paymentMethods  = ['bank_transfer', 'esewa', 'khalti', 'cash', 'whatsapp_receipt'];
        $teachers        = ['Rajesh Sir', 'Priya Ma\'am', 'Santosh Sir', 'Anita Ma\'am'];
        $invoiceCounter  = 1000;

        foreach ($students as $index => $student) {
            /** @var Batch $batch */
            $batch       = $batches[$index % $batches->count()];
            $price       = (float) $batch->price_npr;
            $discount    = $index % 4 === 0 ? round($price * 0.10, 2) : 0;
            $total       = $price - $discount;
            $isPaid      = $index % 3 !== 0;          // 2/3 are paid
            $invoiceDate = Carbon::now()->subDays(rand(10, 90));
            $dueDate     = $invoiceDate->copy()->addDays(7);
            $invoiceNo   = 'KTM-INV-' . ++$invoiceCounter;

            // Invoice
            $invoice = Invoice::updateOrCreate(
                ['invoice_number' => $invoiceNo],
                [
                    'user_id'        => $student->id,
                    'batch_id'       => $batch->id,
                    'subtotal_npr'   => $price,
                    'discount_npr'   => $discount,
                    'tax_npr'        => 0,
                    'total_npr'      => $total,
                    'status'         => $isPaid ? 'paid' : 'pending',
                    'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                    'invoice_date'   => $invoiceDate->toDateString(),
                    'due_date'       => $dueDate->toDateString(),
                    'verified_at'    => $isPaid ? $invoiceDate->copy()->addDays(1) : null,
                    'notes'          => $index % 5 === 0 ? 'Early-bird payment received.' : null,
                ]
            );

            // Enrollment
            Enrollment::updateOrCreate(
                ['user_id' => $student->id, 'batch_id' => $batch->id],
                [
                    'student_name'           => $student->display_name,
                    'phone'                  => $student->phone,
                    'email'                  => $student->email,
                    'invoice_id'             => $invoice->id,
                    'enrollment_date'        => $invoiceDate->toDateString(),
                    'amount_paid'            => $isPaid ? $total : 0,
                    'status'                 => $isPaid ? 'active' : 'pending',
                    'payment_status'         => $isPaid ? 'paid' : 'pending',
                    'crm_status'             => $crmStatuses[array_rand($crmStatuses)],
                    'teacher'                => $teachers[array_rand($teachers)],
                    'attendance_percentage'  => $isPaid ? rand(60, 100) : null,
                    'certificate_eligible'   => $isPaid && rand(0, 1),
                    'notes'                  => null,
                ]
            );
        }

        $this->command->info('Enrolled ' . $students->count() . ' students with invoices.');
    }
}
