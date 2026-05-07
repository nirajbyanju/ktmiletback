<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Residential',
            'Commercial',
            'Industrial',
            'Agricultural',
            'Mixed-Use',
            'semi-Residential',
            'semi-Commercial',
            
        ];

        foreach ($categories as $category) {
            \App\Models\Data\Category::updateOrCreate(
                ['label' => $category],   // check if exists
                ['slug' => \Illuminate\Support\Str::slug($category)],
                ['created_by' => 1]   // insert if not exists
            );
        }
    }
}
