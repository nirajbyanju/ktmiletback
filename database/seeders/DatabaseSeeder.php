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
            // ── Core setup ────────────────────────────────────────────────────
            MenuSeeder::class,
            RolePermissionSeeder::class,
            PaymentStatusSeeder::class,

            // ── Users ─────────────────────────────────────────────────────────
            UserSeeder::class,       // Super Admin (nirajbyanju1234@gmail.com)
            TeacherSeeder::class,    // 5 demo teachers
            StudentSeeder::class,    // 15 demo students

            // ── Course catalog ────────────────────────────────────────────────
            CourseCatalogSeeder::class,           // IELTS + PTE courses & batch types
            EnrollmentInvoiceSeeder::class,       // Course enrollments & invoices for demo students

            // ── Mock tests ────────────────────────────────────────────────────
            MockTestSubscriptionSeeder::class,    // 5 mock test plans
            MockTestEnrollmentSeeder::class,      // Enrollments & invoices for demo students

            // ── Exam bookings ─────────────────────────────────────────────────
            ExamBookingSeeder::class,             // 6 exam plans + enrollments for demo students

            // ── Offers & Claims ───────────────────────────────────────────────
            OfferSeeder::class,
            OfferClaimSeeder::class,

            // ── Misc ──────────────────────────────────────────────────────────
            ContactMessageSeeder::class,
            TestimonialSeeder::class,

            // ── Demo test student (last — depends on all plans above) ─────────
            TestStudentSeeder::class, // test@ktm.edu.np / password11
        ]);
    }
}
