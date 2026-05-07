<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoadTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the road types to be seeded
        $roadTypes = [
            'Blacktopped', 'Gravelled', 'Earthen', 'Concrete'
        ];

        // Loop through each road type and create a new one
        foreach ($roadTypes as $type) {
            \App\Models\Data\RoadType::updateOrCreate(
                ['label' => $type],   // check if exists
                ['slug' => \Illuminate\Support\Str::slug($type)],
                ['created_by' => 1]   // insert if not exists
            );
        }
    }
}
