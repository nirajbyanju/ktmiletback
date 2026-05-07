<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PropertyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the property types to be seeded
        $propertyTypes = [
            'House',
            'Apartment',
            'land',
            'Commercial Building',
            'Industrial Building',
        ];

        // Loop through each property type and create a new one
        foreach ($propertyTypes as $type) {
            \App\Models\Data\PropertyType::updateOrCreate(
                ['label' => $type],   // check if exists
                ['slug' => \Illuminate\Support\Str::slug($type)],
                ['created_by' => 1]   // insert if not exists
            );
        }
    }
}
