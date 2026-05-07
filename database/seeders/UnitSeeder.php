<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the units to be seeded
        $units = [
            'Sq. ft',
            'Sq. m',
            'Acre',
            'Hectare',
            'Ropani',
            'Aana',
            'Paisa',
            'Daam',
        ];

        // Loop through each unit and create a new one
        foreach ($units as $unit) {
            \App\Models\Data\Unit::updateOrCreate(
                ['label' => $unit],   // check if exists
                ['slug' => \Illuminate\Support\Str::slug($unit)],
                ['created_by' => 1]   // insert if not exists
            );
        }
    }
}
