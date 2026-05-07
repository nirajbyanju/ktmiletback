<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $content_method = [
            [
                'label' => 'Email',
                'slug' => 'email',
            ],
            [
                'label' => 'Phone',
                'slug' => 'phone',
            ],
            [
                'label' => 'Whatsapp',
                'slug' => 'whatsapp',
            ],
        ];

        foreach ($content_method as $content_method) {
            \App\Models\Data\ContentMethod::create($content_method);
        }
    }
}
