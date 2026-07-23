<?php

namespace Database\Seeders;

use App\Models\ExamBooking;
use App\Models\ExamBookingEnrollment;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ExamBookingSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Seed exam booking plans ────────────────────────────────────────
        $plans = [
            ['exam_type' => 'IELTS', 'exam_name' => 'IELTS Academic',          'price' => 34000, 'discount' => 1000],
            ['exam_type' => 'IELTS', 'exam_name' => 'IELTS General Training',   'price' => 34000, 'discount' => 1000],
            ['exam_type' => 'PTE',   'exam_name' => 'PTE Academic',             'price' => 28000, 'discount' => 0],
            ['exam_type' => 'PTE',   'exam_name' => 'PTE Academic UKVI',        'price' => 32000, 'discount' => 0],
            ['exam_type' => 'TOEFL', 'exam_name' => 'TOEFL iBT',               'price' => 22000, 'discount' => 500],
            ['exam_type' => 'Duolingo', 'exam_name' => 'Duolingo English Test', 'price' => 7500,  'discount' => 0],
        ];

        $createdPlans = [];
        foreach ($plans as $planData) {
            $createdPlans[] = ExamBooking::updateOrCreate(
                ['exam_type' => $planData['exam_type'], 'exam_name' => $planData['exam_name']],
                ['price' => $planData['price'], 'discount' => $planData['discount']]
            );
        }

        $this->command->info('Seeded ' . count($createdPlans) . ' exam booking plan(s).');

        // ── 2. Seed enrollments for students ─────────────────────────────────
        $students = User::role('User')->get();

        if ($students->isEmpty()) {
            $this->command->warn('No students found — skipping exam booking enrollments.');
            return;
        }

        $centres = [
            'British Council Kathmandu',
            'IDP Kathmandu',
            'Pearson VUE Kathmandu',
            'British Council Pokhara',
            'IDP Lalitpur',
        ];

        $passportNames = [
            'AARAV SHARMA', 'BIBEK THAPA', 'CHITRA GURUNG', 'DEEPA POUDEL', 'ESHA MAHARJAN',
            'FAISAL ANSARI', 'GITA SHRESTHA', 'HARI ADHIKARI', 'ISHAAN KARKI', 'JYOTI RAI',
            'KAMAL BHATT', 'LAXMI TAMANG', 'MANISH GHIMIRE', 'NISHA BASNET', 'OMKAR DHAKAL',
        ];

        $passportNumbers = [
            'PA1234567', 'PB2345678', 'PC3456789', 'PD4567890', 'PE5678901',
            'PF6789012', 'PG7890123', 'PH8901234', 'PI9012345', 'PJ0123456',
            'PK1234568', 'PL2345679', 'PM3456780', 'PN4567891', 'PO5678902',
        ];

        $statuses       = ExamBookingEnrollment::STATUSES;
        $invoiceCounter = 2000;

        foreach ($students as $i => $student) {
            $plan   = $createdPlans[$i % count($createdPlans)];
            $status = $statuses[$i % count($statuses)];

            $net   = (float) $plan->price - (float) ($plan->discount ?? 0);
            $isPaid = in_array($status, ['booking_in_process', 'booked']);

            $invoiceDate = Carbon::now()->subDays(rand(5, 60));
            $dueDate     = $invoiceDate->copy()->addDays(7);
            $invoiceNo   = 'KTM-EXAM-' . ++$invoiceCounter;

            $enrollment = ExamBookingEnrollment::updateOrCreate(
                ['user_id' => $student->id, 'exam_booking_id' => $plan->id],
                [
                    'preferred_date'        => Carbon::now()->addDays(rand(15, 90))->toDateString(),
                    'preferred_time'        => ['09:00', '11:00', '14:00', '16:00'][$i % 4],
                    'test_location'         => $centres[$i % count($centres)],
                    'preferred_test_centre' => $centres[$i % count($centres)],
                    'passport_name'         => $passportNames[$i % count($passportNames)],
                    'passport_number'       => $passportNumbers[$i % count($passportNumbers)],
                    'date_of_birth'         => Carbon::now()->subYears(rand(18, 35))->subDays(rand(0, 365))->toDateString(),
                    'contact_number'        => $student->phone ?? '+9779801234567',
                    'phone'                 => $student->phone ?? '+9779801234567',
                    'email'                 => $student->email,
                    'special_message'       => $i % 4 === 0 ? 'Please arrange the earliest available slot.' : null,
                    'status'                => $status,
                    'available_slot_checked' => in_array($status, ['booking_in_process', 'booked']),
                    'admin_notes'           => $status === 'booked'
                        ? 'Booking confirmed. Reference: KTM-' . rand(1000, 9999)
                        : ($status === 'cancelled' ? 'Student requested cancellation.' : null),
                ]
            );

            Invoice::updateOrCreate(
                ['invoice_number' => $invoiceNo],
                [
                    'user_id'                    => $student->id,
                    'exam_booking_enrollment_id' => $enrollment->id,
                    'subtotal_npr'               => $plan->price,
                    'discount_npr'               => $plan->discount ?? 0,
                    'tax_npr'                    => 0,
                    'total_npr'                  => $net,
                    'status'                     => $isPaid ? Invoice::STATUS_PAID : Invoice::STATUS_UNPAID,
                    'payment_method'             => $isPaid ? ['bank_transfer', 'esewa', 'khalti'][$i % 3] : 'bank_qr',
                    'invoice_date'               => $invoiceDate->toDateString(),
                    'due_date'                   => $dueDate->toDateString(),
                    'verified_at'                => $isPaid ? $invoiceDate->copy()->addDays(1) : null,
                ]
            );
        }

        $this->command->info('Created exam booking enrollments and invoices for ' . $students->count() . ' student(s).');
    }
}
