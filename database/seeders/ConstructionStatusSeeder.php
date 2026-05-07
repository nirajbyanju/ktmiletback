<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Data\ConstructionStatus;
use Illuminate\Support\Str;

class ConstructionStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the construction statuses to be seeded
        $statuses = [
            'Not Started',
            'Foundation',
            'Framing',
            'Roofing',
            'Painting',
            'Flooring',
            'Electrical',
            'Plumbing',
            'Finishing',
            'Completed',
        ];

        // Loop through each status and create a new one
        foreach ($statuses as $status) {
            ConstructionStatus::updateOrCreate(
                ['label' => $status],   // check if exists
                ['slug' => Str::slug($status)],
                ['created_by' => 1]   // insert if not exists
            );
        }
    }
}
