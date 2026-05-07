<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MeasureUnitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the measure units to be seeded
        $measureUnits = [
            'feet',
            'meters',
        ];

        // Loop through each measure unit and create a new one
        foreach ($measureUnits as $unit) {
            \App\Models\Data\MeasureUnit::updateOrCreate(
                ['label' => $unit],   // check if exists
                ['slug' => \Illuminate\Support\Str::slug($unit)],
                ['created_by' => 1]   // insert if not exists
            );
        }
    }
}
