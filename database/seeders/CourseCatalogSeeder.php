<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $ieltsCourse = Course::updateOrCreate(
            ['course_name' => 'IELTS Preparation Course'],
            [
                'duration'      => 6,
                'duration_type' => 'weeks',
                'delivery_mode' => 'Live online Zoom classes',
                'delivery'      => 'online',
                'instruction'   => [
                    'language' => 'English or Nepanglish',
                    'skills'   => ['Reading', 'Writing', 'Listening', 'Speaking'],
                ],
                'support'       => [
                    ['channel_type' => 'WhatsApp',        'contact_value' => '+977 9747469800'],
                    ['channel_type' => 'Email',           'contact_value' => 'ktmtestprep@ktmeducational.edu.np'],
                    ['channel_type' => 'Admin',           'contact_value' => 'KTM Test Prep admin support'],
                    ['channel_type' => 'Teacher follow-up', 'contact_value' => 'Zoom and WhatsApp follow-up'],
                ],
                'features'      => [
                    'Mock Support — Alfa IELTS mock-test practice can be added for exam-style rehearsal.',
                    'Exam Booking Help — Admin support available for IELTS Academic or General Training.',
                ],
                'is_status'     => 1,
            ]
        );

        $pteCourse = Course::updateOrCreate(
            ['course_name' => 'PTE Academic Preparation Course'],
            [
                'duration'      => 6,
                'duration_type' => 'weeks',
                'delivery_mode' => 'Live online Zoom classes',
                'delivery'      => 'online',
                'instruction'   => [
                    'language' => 'English or Nepanglish',
                    'skills'   => ['Speaking and Writing', 'Reading', 'Listening', 'Mock Practice'],
                ],
                'support'       => [
                    ['channel_type' => 'WhatsApp',        'contact_value' => '+977 9747469800'],
                    ['channel_type' => 'Email',           'contact_value' => 'ktmtestprep@ktmeducational.edu.np'],
                    ['channel_type' => 'Admin',           'contact_value' => 'KTM Test Prep admin support'],
                    ['channel_type' => 'Teacher follow-up', 'contact_value' => 'Zoom and WhatsApp follow-up'],
                ],
                'features'      => [
                    'Mock Support — PTE practice tests can be added for exam-style rehearsal.',
                    'Exam Booking Help — Admin support available for PTE Academic booking requests.',
                ],
                'is_status'     => 1,
            ]
        );

        $batches = [
            [
                'batch_type'     => 'Elite Private',
                'min_size'       => 1,
                'max_size'       => 1,
                'price_npr'      => 30000,
                'is_price_variable' => false,
                'schedule_notes' => 'Special class time may be arranged',
            ],
            [
                'batch_type'     => 'Premium Focus',
                'min_size'       => 5,
                'max_size'       => 11,
                'price_npr'      => 5999,
                'is_price_variable' => false,
                'schedule_notes' => 'Higher interaction group',
            ],
            [
                'batch_type'     => 'Smart Batch',
                'min_size'       => 12,
                'max_size'       => 20,
                'price_npr'      => 2999,
                'is_price_variable' => false,
                'schedule_notes' => 'Main online batch model',
            ],
            [
                'batch_type'     => 'Value Batch',
                'min_size'       => 21,
                'max_size'       => 30,
                'price_npr'      => 2199,
                'is_price_variable' => false,
                'schedule_notes' => 'Volume model with controlled quality messaging',
            ],
        ];

        foreach ([$ieltsCourse, $pteCourse] as $course) {
            foreach ($batches as $batch) {
                Batch::updateOrCreate(
                    [
                        'course_id'  => $course->id,
                        'batch_type' => $batch['batch_type'],
                    ],
                    $batch + [
                        'course_id' => $course->id,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
