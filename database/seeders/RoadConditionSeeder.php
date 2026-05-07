<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoadConditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the road conditions to be seeded
        $roadConditions = [
            'Good',
            'Poor',
            'Fair',
            'Average',
            'Bad',
            'Very Bad',
        ];

        // Loop through each road condition and create a new one
        foreach ($roadConditions as $condition) {
            \App\Models\Data\RoadCondition::updateOrCreate(
                ['label' => $condition],   // check if exists
                ['slug' => \Illuminate\Support\Str::slug($condition)],
                ['created_by' => 1]   // insert if not exists
            );
        }
    }
}
