<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Data\ParkingType;
use Illuminate\Support\Str;

class ParkingTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the parking types to be seeded
        $types = [
            'covered',
            'open',
            'underground',
            'multi-storey',
        ];

        // Loop through each type and create a new one
        foreach ($types as $type) {
            \App\Models\Data\ParkingType::updateOrCreate(
                ['label' => $type],   // check if exists
                ['slug' => Str::slug($type)],
                ['created_by' => 1]   // insert if not exists
            );
        }
    }
}
