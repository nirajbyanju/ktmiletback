<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            MenuSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            StatusSeeder::class,
            CourseCatalogSeeder::class,
            StudentSeeder::class,
            EnrollmentInvoiceSeeder::class,
            ExamBookingSeeder::class,
            ContactMessageSeeder::class,
        ]);
    }
}
