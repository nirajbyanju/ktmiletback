<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PropertyStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the property statuses to be seeded
        $propertyStatuses = [
            'Available', 'Sold', 'Leased', 'For Sale', 'For Rent', 'For Lease'
        ];

        // Loop through each property status and create a new one
        foreach ($propertyStatuses as $status) {
            \App\Models\Data\PropertyStatus::updateOrCreate(
                ['label' => $status],   // check if exists
                ['slug' => \Illuminate\Support\Str::slug($status)],
                ['created_by' => 1]   // insert if not exists
            );
        }
    }
}
