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
                'description' => 'Comprehensive IELTS Academic preparation with live Zoom classes, computer-based mock tests, and exam booking assistance.',
                'best_for' => 'Students planning to study, work, or migrate to English-speaking countries — especially those targeting UK, Australia, Canada, and New Zealand visa requirements.',
                'duration' => 6,
                'duration_type' => 'weeks',
                'delivery_mode' => 'Live online Zoom classes',
                'delivery' => 'online',
                'instruction' => [
                    'language' => 'English or Nepanglish',
                    'skills' => ['Reading', 'Writing', 'Listening', 'Speaking'],
                ],
                'support' => [
                    ['channel_type' => 'WhatsApp',           'contact_value' => '+977 9747469800'],
                    ['channel_type' => 'Email',              'contact_value' => 'ktmtestprep@ktmeducational.edu.np'],
                    ['channel_type' => 'Admin',              'contact_value' => 'KTM Test Prep admin support'],
                    ['channel_type' => 'Teacher follow-up',  'contact_value' => 'Zoom and WhatsApp follow-up'],
                ],
                'features' => [
                    'Mock Support — Alfa IELTS mock-test practice can be added for exam-style rehearsal.',
                    'Exam Booking Help — Admin support available for IELTS Academic or General Training.',
                ],
                'is_status' => 1,
            ]
        );

        $pteCourse = Course::updateOrCreate(
            ['course_name' => 'PTE Academic Preparation Course'],
            [
                'description' => 'Expert PTE Academic preparation with live Zoom sessions, AI-scored mock tests, and exam booking guidance tailored for Nepalese students.',
                'best_for' => 'Students who need PTE Academic for Australian, New Zealand, or UK visa applications, or for university admissions — especially those who prefer computer-based assessment.',
                'duration' => 6,
                'duration_type' => 'weeks',
                'delivery_mode' => 'Live online Zoom classes',
                'delivery' => 'online',
                'instruction' => [
                    'language' => 'English or Nepanglish',
                    'skills' => ['Speaking and Writing', 'Reading', 'Listening', 'Mock Practice'],
                ],
                'support' => [
                    ['channel_type' => 'WhatsApp',           'contact_value' => '+977 9747469800'],
                    ['channel_type' => 'Email',              'contact_value' => 'ktmtestprep@ktmeducational.edu.np'],
                    ['channel_type' => 'Admin',              'contact_value' => 'KTM Test Prep admin support'],
                    ['channel_type' => 'Teacher follow-up',  'contact_value' => 'Zoom and WhatsApp follow-up'],
                ],
                'features' => [
                    'Mock Support — PTE practice tests can be added for exam-style rehearsal.',
                    'Exam Booking Help — Admin support available for PTE Academic booking requests.',
                ],
                'is_status' => 1,
            ]
        );

        // Identical batch plan set for both IELTS and PTE
        $batches = [
            [
                'batch_type' => 'Elite Private',
                'size_label' => '1:1',
                'best_for' => 'Premium personalised coaching with flexible time and date',
                'min_size' => 1,
                'max_size' => 1,
                'price_npr' => 30000,

                'is_featured' => false,
                'schedule_notes' => '4 weeks · 20 hrs · Mon–Fri',
            ],
            [
                'batch_type' => 'Premium Focus',
                'size_label' => null,
                'best_for' => 'Students who want more interaction and support',
                'min_size' => 5,
                'max_size' => 11,
                'price_npr' => 5999,

                'is_featured' => false,
                'schedule_notes' => '6 weeks · 30 hrs · Mon–Fri',
            ],
            [
                'batch_type' => 'Value Batch',
                'size_label' => null,
                'best_for' => 'Affordable group learning for budget-conscious students',
                'min_size' => 21,
                'max_size' => 30,
                'price_npr' => 2199,

                'is_featured' => false,
                'schedule_notes' => '6 weeks · 30 hrs · Mon–Fri',
            ],
            [
                'batch_type' => 'Smart Batch',
                'size_label' => null,
                'best_for' => 'Best balance of quality and price',
                'min_size' => 12,
                'max_size' => 20,
                'price_npr' => 2999,

                'is_featured' => true,
                'schedule_notes' => '6 weeks · 30 hrs · Mon–Fri',
            ],
            [
                'batch_type' => 'Friends Private Group',
                'size_label' => 'Private group (your own friends / relatives)',
                'best_for' => 'Friends, classmates, or relatives who want to study together — no outside students',
                'min_size' => null,
                'max_size' => null,
                'price_npr' => 45000,

                'is_featured' => false,
                'schedule_notes' => '6 weeks · 30 hrs · Mon–Fri',
            ],
        ];

        foreach ([$ieltsCourse, $pteCourse] as $course) {
            foreach ($batches as $batch) {
                Batch::updateOrCreate(
                    [
                        'course_id' => $course->id,
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
