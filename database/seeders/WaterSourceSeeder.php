<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WaterSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the water sources to be seeded
        $waterSources = [
            'Municipal Supply', 'Industrial', 'Rainwater', 'Groundwater',
        ];

        // Loop through each water source and create a new one
        foreach ($waterSources as $source) {
            \App\Models\Data\WaterSource::updateOrCreate(
                ['label' => $source],   // check if exists
                ['slug' => \Illuminate\Support\Str::slug($source)],
                ['created_by' => 1]   // insert if not exists
            );
        }
    }
}
