<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FurnishingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            // Define the furnishing types to be seeded
            $furnishingTypes = [
                'Unfurnished',
                'Semi-Furnished',
                'Fully Furnished',
            ];
    
            // Loop through each furnishing type and create a new one
            foreach ($furnishingTypes as $type) {
                \App\Models\Data\Furnishing::updateOrCreate(
                    ['label' => $type],   // check if exists
                    ['slug' => Str::slug($type)],
                    ['created_by' => 1]   // insert if not exists
                );
            }
    }
}
