<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SewageTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the sewage types to be seeded
        $sewageTypes = [
           'Public Sewer', 'Septic Tank', 'None', 'Cesspool','Soak Pit'
        ];

        // Loop through each sewage type and create a new one
        foreach ($sewageTypes as $type) {
            \App\Models\Data\SewageType::updateOrCreate(
                ['label' => $type],   // check if exists
                ['slug' => \Illuminate\Support\Str::slug($type)],
                ['created_by' => 1]   // insert if not exists
            );
        }
    }
}
