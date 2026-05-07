<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PropertyCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $propertyCategories = [
            'Residential',
            'Commercial',
            'Industrial',
            'Agricultural',
            'Mixed-Use',
            'semi-Residential',
            'semi-Commercial',
            
        ];

        foreach ($propertyCategories as $category) {
            \App\Models\Data\PropertyCategory::updateOrCreate(
                ['label' => $category],   // check if exists
                ['slug' => \Illuminate\Support\Str::slug($category)],
                ['created_by' => 1]   // insert if not exists
            );
        }
            
    }
}
