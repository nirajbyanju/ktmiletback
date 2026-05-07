<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HouseMaterialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            $house_materials = [
                [
                    'label' => 'Wood',
                    'slug' => 'wood',
                ],
                [
                    'label' => 'Concrete',
                    'slug' => 'concrete',
                ],
                [
                    'label' => 'Steel',
                    'slug' => 'steel',
                ],
                [
                    'label' => 'Aluminium',
                    'slug' => 'aluminium',
                ],
                [
                    'label' => 'Glass',
                    'slug' => 'glass',
                ],
                [
                    'label' => 'Plastic',
                    'slug' => 'plastic',
                ],
                [
                    'label' => 'Metal',
                    'slug' => 'metal',
                ],
            ];

            foreach ($house_materials as $house_material) {
                \App\Models\Data\HouseMaterials::create($house_material);
            }
    }
}
