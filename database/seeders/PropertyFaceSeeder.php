<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PropertyFaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the property faces to be seeded
        $propertyFaces = [
            'North',
            'South',
            'East',
            'West',
            'Northeast',
            'Northwest',
            'Southeast',
            'Southwest',
        ];

        // Loop through each property face and create a new one
        foreach ($propertyFaces as $face) {
            \App\Models\Data\PropertyFace::updateOrCreate(
                ['label' => $face],   // check if exists
                ['slug' => \Illuminate\Support\Str::slug($face)],
                ['created_by' => 1]   // insert if not exists
            );
        }
    }
}
