<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContactMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            $contact_methods = [
                [
                    'label' => 'Phone',
                    'slug' => 'phone',
                ],
                [
                    'label' => 'Email',
                    'slug' => 'email',
                ],
                [
                    'label' => 'Whatsapp',
                    'slug' => 'whatsapp',
                ],
                [
                    'label' => 'Website',
                    'slug' => 'website',
                ],
                [
                    'label' => 'Office Visit',
                    'slug' => 'office_visit',
                ],
            ];

            foreach ($contact_methods as $contact_method) {
                \App\Models\Data\ContactMethod::create($contact_method);
            }
    }
}
