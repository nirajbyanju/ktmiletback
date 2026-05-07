<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $status = [
            [
                'label' => 'pending',
                'slug' => 'pending',
            ],
            [
                'label' => 'approved',
                'slug' => 'approved',
            ],
            [
                'label' => 'rejected',
                'slug' => 'rejected',
            ],
        ];

        foreach ($status as $status) {
            \App\Models\Data\BlogStatus::create($status);
        }
    }
}
