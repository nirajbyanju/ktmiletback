<?php

namespace Database\Seeders;

use App\Models\ExamBooking;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ExamBookingSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::role('User')->get();

        $testTypes = ['IELTS', 'IELTS', 'PTE'];
        $centres   = [
            'British Council Kathmandu',
            'IDP Kathmandu',
            'Pearson VUE Kathmandu',
            'British Council Pokhara',
            'IDP Lalitpur',
        ];
        $statuses  = ['pending', 'slot_checking', 'slot_confirmed', 'payment_pending', 'booked', 'cancelled'];
        $payStatus = ['pending', 'paid'];

        $passportNames = [
            'AARAV SHARMA', 'BIBEK THAPA', 'CHITRA GURUNG', 'DEEPA POUDEL', 'ESHA MAHARJAN',
            'FAISAL ANSARI', 'GITA SHRESTHA', 'HARI ADHIKARI', 'ISHAAN KARKI', 'JYOTI RAI',
            'KAMAL BHATT', 'LAXMI TAMANG', 'MANISH GHIMIRE', 'NISHA BASNET', 'OMKAR DHAKAL',
        ];

        $passports = [
            'PA1234567', 'PB2345678', 'PC3456789', 'PD4567890', 'PE5678901',
            'PF6789012', 'PG7890123', 'PH8901234', 'PI9012345', 'PJ0123456',
            'PK1234568', 'PL2345679', 'PM3456780', 'PN4567891', 'PO5678902',
        ];

        foreach ($students as $i => $student) {
            $status  = $statuses[$i % count($statuses)];
            $isCancelled = $status === 'cancelled';

            ExamBooking::updateOrCreate(
                ['user_id' => $student->id, 'test_type' => $testTypes[$i % 3]],
                [
                    'student_name'          => $student->display_name,
                    'test_type'             => $testTypes[$i % 3],
                    'preferred_date'        => Carbon::now()->addDays(rand(10, 60))->toDateString(),
                    'preferred_time'        => ['09:00', '11:00', '14:00', '16:00'][$i % 4],
                    'preferred_test_centre' => $centres[$i % count($centres)],
                    'test_location'         => $centres[$i % count($centres)],
                    'passport_name'         => $passportNames[$i % count($passportNames)],
                    'passport_number'       => $passports[$i % count($passports)],
                    'date_of_birth'         => Carbon::now()->subYears(rand(18, 35))->subDays(rand(0, 365))->toDateString(),
                    'contact_number'        => $student->phone ?? '+9779801234567',
                    'phone'                 => $student->phone ?? '+9779801234567',
                    'email'                 => $student->email,
                    'special_message'       => $i % 3 === 0 ? 'Please arrange the earliest available slot.' : null,
                    'status'                => $status,
                    'payment_status'        => $isCancelled ? 'pending' : $payStatus[$i % 2],
                    'available_slot_checked'     => in_array($status, ['slot_confirmed', 'payment_pending', 'booked']),
                    'pte_login_details_received' => $status === 'booked',
                    'admin_notes'           => $status === 'booked'
                        ? 'Booking confirmed. Reference: KTM-' . rand(1000, 9999)
                        : ($isCancelled ? 'Student requested cancellation.' : null),
                ]
            );
        }

        $this->command->info('Created ' . $students->count() . ' exam booking(s).');
    }
}
