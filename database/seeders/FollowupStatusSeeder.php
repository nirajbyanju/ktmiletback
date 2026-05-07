<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FollowupStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            $followup_status = [
                [
                    'label' => 'Interested',
                    'slug' => 'interested',
                ],
                [
                    'label' => 'Not Interested',
                    'slug' => 'not_interested',
                ],
                [
                    'label' => 'Call Later',
                    'slug' => 'call_later',
                ],
                [
                    'label' => 'Visit Scheduled',
                    'slug' => 'visit_scheduled',
                ],
            ];

            foreach ($followup_status as $followup_status) {
                \App\Models\Data\FollowupStatus::create($followup_status);
            }
    }
}
