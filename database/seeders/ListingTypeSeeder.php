<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ListingTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the listing types to be seeded
        $listingTypes = [
            'For Sale', 'For Rent', 'For Lease'
        ];

        // Loop through each listing type and create a new one
        foreach ($listingTypes as $type) {
            \App\Models\Data\ListingType::updateOrCreate(
                ['label' => $type],   // check if exists
                ['slug' => Str::slug($type)],
                ['created_by' => 1]   // insert if not exists
            );
        }
    }
}
