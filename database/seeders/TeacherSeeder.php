<?php

namespace Database\Seeders;

use App\Models\Teacher;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = [
            [
                'teacher_id'     => 'T-001',
                'name'           => 'Rajesh Adhikari',
                'course'         => 'IELTS',
                'phone'          => '+9779801234001',
                'email'          => 'rajesh.adhikari@ktmconsultancy.com',
                'available_time' => 'Morning (6:00 AM – 10:00 AM)',
                'status'         => 'Active',
                'notes'          => 'Lead IELTS instructor. 8+ years experience.',
            ],
            [
                'teacher_id'     => 'T-002',
                'name'           => 'Priya Shrestha',
                'course'         => 'IELTS / PTE',
                'phone'          => '+9779801234002',
                'email'          => 'priya.shrestha@ktmconsultancy.com',
                'available_time' => 'Evening (4:00 PM – 8:00 PM)',
                'status'         => 'Active',
                'notes'          => 'Specialist in IELTS Writing and PTE Speaking.',
            ],
            [
                'teacher_id'     => 'T-003',
                'name'           => 'Santosh Poudel',
                'course'         => 'PTE',
                'phone'          => '+9779801234003',
                'email'          => 'santosh.poudel@ktmconsultancy.com',
                'available_time' => 'Morning / Afternoon',
                'status'         => 'Active',
                'notes'          => 'PTE Academic specialist. Former Pearson assessor.',
            ],
            [
                'teacher_id'     => 'T-004',
                'name'           => 'Anita Gurung',
                'course'         => 'IELTS / TOEFL',
                'phone'          => '+9779801234004',
                'email'          => 'anita.gurung@ktmconsultancy.com',
                'available_time' => 'Afternoon (12:00 PM – 4:00 PM)',
                'status'         => 'Active',
                'notes'          => 'TOEFL iBT and IELTS Academic instructor.',
            ],
            [
                'teacher_id'     => 'T-005',
                'name'           => 'Bikash Tamang',
                'course'         => 'Duolingo / PTE',
                'phone'          => '+9779801234005',
                'email'          => 'bikash.tamang@ktmconsultancy.com',
                'available_time' => 'Flexible / As required',
                'status'         => 'Active',
                'notes'          => 'Duolingo English Test coach and PTE backup instructor.',
            ],
        ];

        foreach ($teachers as $data) {
            Teacher::updateOrCreate(['teacher_id' => $data['teacher_id']], $data);
        }

        $this->command->info('Seeded ' . count($teachers) . ' teacher(s).');
    }
}
