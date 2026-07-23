<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            // Core setup
            MenuSeeder::class,
            RolePermissionSeeder::class,
            PaymentStatusSeeder::class,
            UserSeeder::class,

            // // Users
            // UserSeeder::class,
            TeacherSeeder::class,
            // StudentSeeder::class,

            // // Course catalog, enrollments & invoices
            CourseCatalogSeeder::class,
            // EnrollmentInvoiceSeeder::class,

            // // Mock test plans, enrollments & invoices
            MockTestSubscriptionSeeder::class,
            MockTestEnrollmentSeeder::class,

            // // Exam booking plans, enrollments & invoices
            ExamBookingSeeder::class,

            // // Offers & Claims
            OfferSeeder::class,
            OfferClaimSeeder::class,

            // // Misc
            ContactMessageSeeder::class,
            TestimonialSeeder::class,
            TestStudentSeeder::class,
        ]);
    }
}
