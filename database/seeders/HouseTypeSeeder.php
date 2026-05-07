<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HouseTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the house types to be seeded
        $houseTypes = [
            'Apartment',
            'Villa',
            'Townhouse',
            'Duplex',
            'Penthouse',
            'Cottage',
            'Bungalow',
            'Mansion',
            'Studio',
            'Loft',
        ];

        // Loop through each house type and create a new one
        foreach ($houseTypes as $type) {
            \App\Models\Data\HouseType::updateOrCreate(
                ['label' => $type],   // check if exists
                ['slug' => Str::slug($type)],
                ['created_by' => 1]   // insert if not exists
            );
        }
    }
}
