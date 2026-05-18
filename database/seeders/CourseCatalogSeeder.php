<?php

namespace Database\Seeders;

use App\Models\AdditionalService;
use App\Models\Batch;
use App\Models\Course;
use App\Models\SkillModule;
use App\Models\SupportChannel;
use Illuminate\Database\Seeder;

class CourseCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $ieltsCourse = Course::updateOrCreate(
            ['name' => 'IELTS Preparation Course'],
            [
                'duration_weeks' => 6,
                'total_hours' => 30,
                'delivery_mode' => 'Live online Zoom classes',
                'instruction_lang' => 'English or Nepanglish',
                'skills' => 'Reading, Writing, Listening, Speaking',
            ]
        );

        $pteCourse = Course::updateOrCreate(
            ['name' => 'PTE Academic Preparation Course'],
            [
                'duration_weeks' => 6,
                'total_hours' => 30,
                'delivery_mode' => 'Live online Zoom classes',
                'instruction_lang' => 'English or Nepanglish',
                'skills' => 'Speaking and Writing, Reading, Listening, Mock Practice',
            ]
        );

        $batches = [
            [
                'batch_type' => 'Elite Private',
                'min_size' => 1,
                'max_size' => 1,
                'price_npr' => 30000,
                'is_price_variable' => false,
                'schedule_notes' => 'Special class time may be arranged',
            ],
            [
                'batch_type' => 'Premium Focus',
                'min_size' => 5,
                'max_size' => 11,
                'price_npr' => 5999,
                'is_price_variable' => false,
                'schedule_notes' => 'Higher interaction group',
            ],
            [
                'batch_type' => 'Smart Batch',
                'min_size' => 12,
                'max_size' => 20,
                'price_npr' => 2999,
                'is_price_variable' => false,
                'schedule_notes' => 'Main online batch model',
            ],
            [
                'batch_type' => 'Value Batch',
                'min_size' => 21,
                'max_size' => 30,
                'price_npr' => 2199,
                'is_price_variable' => false,
                'schedule_notes' => 'Volume model with controlled quality messaging',
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

        $supportChannels = [
            ['channel_type' => 'WhatsApp', 'contact_value' => '+977 9747469800'],
            ['channel_type' => 'Email', 'contact_value' => 'ktmtestprep@ktmeducational.edu.np'],
            ['channel_type' => 'Admin', 'contact_value' => 'KTM Test Prep admin support'],
            ['channel_type' => 'Teacher follow-up', 'contact_value' => 'Zoom and WhatsApp follow-up'],
        ];

        foreach ($supportChannels as $channel) {
            SupportChannel::updateOrCreate(
                ['channel_type' => $channel['channel_type']],
                $channel
            );
        }

        $skillModules = [
            [
                'skill_name' => 'Reading',
                'topics_covered' => 'Skimming, scanning, question types, timing, and computer-based navigation.',
                'feedback_included' => false,
            ],
            [
                'skill_name' => 'Writing',
                'topics_covered' => 'Task 1 and Task 2 planning, structure, grammar, coherence, and teacher feedback.',
                'feedback_included' => true,
            ],
            [
                'skill_name' => 'Listening',
                'topics_covered' => 'Computer-based listening practice, note completion, maps, and section timing.',
                'feedback_included' => false,
            ],
            [
                'skill_name' => 'Speaking',
                'topics_covered' => 'Part 1, 2, and 3 simulation with Zoom-based feedback and voice tasks.',
                'feedback_included' => true,
            ],
        ];

        foreach ($skillModules as $module) {
            SkillModule::updateOrCreate(
                ['skill_name' => $module['skill_name']],
                $module
            );
        }

        $services = [
            [
                'service_name' => 'Mock Support',
                'description' => 'Alfa IELTS mock-test practice can be added for exam-style rehearsal and follow-up.',
                'is_add_on' => true,
                'price_npr' => null,
            ],
            [
                'service_name' => 'Exam Booking Help',
                'description' => 'Admin support is available for IELTS Academic or IELTS General Training booking requests.',
                'is_add_on' => true,
                'price_npr' => null,
            ],
        ];

        foreach ($services as $service) {
            AdditionalService::updateOrCreate(
                ['service_name' => $service['service_name']],
                $service
            );
        }
    }
}
