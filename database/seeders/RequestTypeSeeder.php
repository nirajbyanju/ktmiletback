<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RequestTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $request_type = [
            [
                'label' => 'Buy',
                'slug' => 'buy',
            ],
            [
                'label' => 'Sell',
                'slug' => 'sell',
            ],
            [
                'label' => 'Rent',
                'slug' => 'rent',
            ],
        ];

        foreach ($request_type as $request_type) {
            \App\Models\Data\RequestTypes::create($request_type);
        }
    }
}
