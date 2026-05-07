<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AmenitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenities = [
         'Swimming pool',
         'Gym',
         'Garden',
         'Tennis court',
         'CCTV security',
         'Power backup',
         'Lift'
        ];

        foreach ($amenities as $amenity) {
            \App\Models\Data\Amenities::create([
                'label' => $amenity,
                'slug' => Str::slug($amenity)
            ]);
        }
    }
}
