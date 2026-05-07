<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoofTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        //  Define the roof types to be seeded
        $roofTypes = [
            'Flat',
            'Gable',
            'Hip',
            'Mansard',
            'Shed',
            'Hipped',
            'Gabled',
            'Hipped',
            'Mansard',
            'Shed',
        ];

        // Loop through each roof type and create a new one
        foreach ($roofTypes as $type) {
            \App\Models\Data\RoofType::updateOrCreate(
                ['label' => $type],   // check if exists
                ['slug' => \Illuminate\Support\Str::slug($type)],
                ['created_by' => 1]   // insert if not exists
            );
        }
    }
}
