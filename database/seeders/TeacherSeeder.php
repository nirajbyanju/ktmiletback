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
                'name'           => 'Sample 1',
                'course'         => 'IELTS/PTE',
                'phone'          => null,
                'email'          => 'bimal.ktmeducational@gmail.com',
                'available_time' => 'Morning/Evening',
                'status'         => 'Active',
                'notes'          => 'Replace with real teacher',
            ],
            [
                'teacher_id'     => 'T-002',
                'name'           => 'Teacher 2',
                'course'         => 'IELTS/PTE',
                'phone'          => null,
                'email'          => null,
                'available_time' => 'As required',
                'status'         => 'Backup',
                'notes'          => null,
            ],
        ];

        foreach ($teachers as $data) {
            Teacher::updateOrCreate(['teacher_id' => $data['teacher_id']], $data);
        }
    }
}
