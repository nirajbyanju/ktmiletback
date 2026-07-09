<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enterprise Migration 2 — Universal Lookup / Dropdown System
 *
 * Replaces every hardcoded status, type, category, and dropdown list
 * in the application with database-driven lookup values.
 *
 * Design principles:
 *   • lookup_categories  — defines the namespace (e.g., "invoice_status")
 *   • lookup_values      — stores the actual values (e.g., "unpaid", "paid")
 *   • Application code references lookup_values.key (snake_case string)
 *   • Admin panel can add/edit/sort values without code changes
 *   • is_system = true marks values the application depends on (cannot be deleted)
 *
 * All existing hardcoded statuses and type lists across the codebase
 * are seeded here, making the system fully database-driven.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── lookup_categories ─────────────────────────────────────────────────
        Schema::create('lookup_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique()
                ->comment('Machine-readable code. Used as the reference in application code.');
            $table->string('label', 120)
                ->comment('Human-readable name shown in admin panel.');
            $table->text('description')->nullable();
            $table->string('module', 60)->nullable()->index()
                ->comment('Which application module owns this category.');
            $table->boolean('is_editable')->default(true)
                ->comment('If false, admin cannot add/remove values (system-owned category).');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });

        // ── lookup_values ─────────────────────────────────────────────────────
        Schema::create('lookup_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lookup_category_id')
                ->constrained('lookup_categories')
                ->cascadeOnDelete();
            $table->string('key', 60)
                ->comment('Stored in application DB columns. Must be snake_case.');
            $table->string('label', 120)
                ->comment('Displayed to users and admin.');
            $table->string('color', 30)->nullable()
                ->comment('Tailwind color token for status badges (e.g., emerald, amber, red).');
            $table->string('icon', 80)->nullable()
                ->comment('Icon name or emoji for UI display.');
            $table->text('description')->nullable()
                ->comment('Detailed description / help text for this value.');
            $table->json('meta')->nullable()
                ->comment('Arbitrary extra data (e.g., allowed next statuses, workflow rules).');
            $table->boolean('is_default')->default(false)
                ->comment('Pre-selected value in forms. Only one should be true per category.');
            $table->boolean('is_system')->default(false)
                ->comment('System values cannot be deleted — application code depends on them.');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Composite unique: each key is unique within its category
            $table->unique(['lookup_category_id', 'key'], 'lookup_values_category_key_unique');
            $table->index(['lookup_category_id', 'is_active'], 'lookup_values_cat_active_index');
            $table->index(['lookup_category_id', 'sort_order'], 'lookup_values_cat_sort_index');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // ── Seed all application lookup data ──────────────────────────────────
        $this->seedCategories();
    }

    private function seedCategories(): void
    {
        $now = now();

        // Helper: insert a category and return its id
        $cat = function (string $code, string $label, string $module, string $desc = '', bool $editable = true) use ($now): int {
            return DB::table('lookup_categories')->insertGetId([
                'code'        => $code,
                'label'       => $label,
                'module'      => $module,
                'description' => $desc,
                'is_editable' => $editable,
                'is_active'   => true,
                'sort_order'  => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        };

        // Helper: insert values for a category
        $vals = function (int $categoryId, array $values) use ($now): void {
            foreach ($values as $i => $v) {
                DB::table('lookup_values')->insert([
                    'lookup_category_id' => $categoryId,
                    'key'         => $v['key'],
                    'label'       => $v['label'],
                    'color'       => $v['color']       ?? null,
                    'icon'        => $v['icon']        ?? null,
                    'description' => $v['description'] ?? null,
                    'meta'        => isset($v['meta']) ? json_encode($v['meta']) : null,
                    'is_default'  => $v['default']     ?? false,
                    'is_system'   => $v['system']      ?? false,
                    'is_active'   => true,
                    'sort_order'  => $i + 1,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        };

        // ── 1. Invoice Status ─────────────────────────────────────────────────
        $id = $cat('invoice_status', 'Invoice Status', 'invoice', 'Lifecycle states of an invoice.', false);
        $vals($id, [
            ['key' => 'unpaid',    'label' => 'Unpaid',    'color' => 'amber',   'system' => true,  'default' => true, 'description' => 'Invoice created; payment not yet received.'],
            ['key' => 'paid',      'label' => 'Paid',      'color' => 'emerald', 'system' => true,  'description' => 'Full payment received and verified.'],
            ['key' => 'partial',   'label' => 'Partial',   'color' => 'blue',    'system' => true,  'description' => 'Partial payment received.'],
            ['key' => 'waived',    'label' => 'Waived',    'color' => 'teal',    'system' => true,  'description' => 'Fee fully waived by admin.'],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'color' => 'gray',    'system' => true,  'description' => 'Invoice cancelled; no payment expected.'],
            ['key' => 'refunded',  'label' => 'Refunded',  'color' => 'purple',  'system' => true,  'description' => 'Payment received and refunded to student.'],
        ]);

        // ── 2. Enrollment Status ──────────────────────────────────────────────
        $id = $cat('enrollment_status', 'Enrollment Status', 'enrollment', 'Administrative status of a student enrollment.', true);
        $vals($id, [
            ['key' => 'active',     'label' => 'Active',     'color' => 'emerald', 'system' => true,  'default' => true, 'description' => 'Student is currently enrolled and attending.'],
            ['key' => 'completed',  'label' => 'Completed',  'color' => 'blue',    'system' => true,  'description' => 'Student has completed the course.'],
            ['key' => 'dropped',    'label' => 'Dropped',    'color' => 'red',     'system' => true,  'description' => 'Student dropped the course.'],
            ['key' => 'on_hold',    'label' => 'On Hold',    'color' => 'amber',   'system' => false, 'description' => 'Enrollment temporarily paused.'],
            ['key' => 'inactive',   'label' => 'Inactive',   'color' => 'gray',    'system' => false, 'description' => 'Student no longer active; no formal drop.'],
            ['key' => 'waitlisted', 'label' => 'Waitlisted', 'color' => 'purple',  'system' => false, 'description' => 'Student on waitlist pending a spot.'],
        ]);

        // ── 3. CRM Enrollment Status ──────────────────────────────────────────
        $id = $cat('crm_enrollment_status', 'CRM Enrollment Status', 'enrollment', 'CRM-level status for internal tracking.', true);
        $vals($id, [
            ['key' => 'active',      'label' => 'Active',      'color' => 'emerald', 'system' => true, 'default' => true],
            ['key' => 'inactive',    'label' => 'Inactive',    'color' => 'gray',    'system' => false],
            ['key' => 'dropped',     'label' => 'Dropped',     'color' => 'red',     'system' => false],
            ['key' => 'on_hold',     'label' => 'On Hold',     'color' => 'amber',   'system' => false],
            ['key' => 'blacklisted', 'label' => 'Blacklisted', 'color' => 'rose',    'system' => false],
        ]);

        // ── 4. Class Status ───────────────────────────────────────────────────
        $id = $cat('class_status', 'Class / Batch Status', 'batch', 'Lifecycle state of a batch/class.', false);
        $vals($id, [
            ['key' => 'upcoming',    'label' => 'Upcoming',    'color' => 'blue',    'system' => true,  'description' => 'Class has not yet started (start_date in future).'],
            ['key' => 'in_progress', 'label' => 'In Progress', 'color' => 'emerald', 'system' => true,  'description' => 'Class is currently running.'],
            ['key' => 'completed',   'label' => 'Completed',   'color' => 'gray',    'system' => true,  'description' => 'Class has finished (end_date passed).'],
            ['key' => 'dropped',     'label' => 'Dropped',     'color' => 'red',     'system' => false, 'description' => 'Class was cancelled/dropped.'],
            ['key' => 'inactive',    'label' => 'Inactive',    'color' => 'slate',   'system' => false, 'description' => 'Class is inactive / on hold.'],
        ]);

        // ── 5. Demo Request Status ────────────────────────────────────────────
        $id = $cat('demo_request_status', 'Demo Request Status', 'demo', 'Status of a student live demo session request.', false);
        $vals($id, [
            ['key' => 'pending',  'label' => 'Pending',  'color' => 'amber',   'system' => true, 'default' => true],
            ['key' => 'approved', 'label' => 'Approved', 'color' => 'emerald', 'system' => true],
            ['key' => 'rejected', 'label' => 'Rejected', 'color' => 'red',     'system' => true],
        ]);

        // ── 6. Exam Booking Status ────────────────────────────────────────────
        $id = $cat('exam_booking_status', 'Exam Booking Status', 'exam_booking', 'Lifecycle states of an exam booking enrollment.', false);
        $vals($id, [
            ['key' => 'new_request',      'label' => 'New Request',      'color' => 'amber',   'system' => true, 'default' => true],
            ['key' => 'document_pending', 'label' => 'Document Pending', 'color' => 'blue',    'system' => true],
            ['key' => 'payment_pending',  'label' => 'Payment Pending',  'color' => 'orange',  'system' => true],
            ['key' => 'booked',           'label' => 'Booked',           'color' => 'emerald', 'system' => true],
            ['key' => 'cancelled',        'label' => 'Cancelled',        'color' => 'gray',    'system' => true],
            ['key' => 'completed',        'label' => 'Completed',        'color' => 'teal',    'system' => false],
        ]);

        // ── 7. Teacher Status ─────────────────────────────────────────────────
        $id = $cat('teacher_status', 'Teacher Status', 'teacher', 'Employment/availability status of a teacher.', true);
        $vals($id, [
            ['key' => 'active',    'label' => 'Active',    'color' => 'emerald', 'system' => true, 'default' => true],
            ['key' => 'inactive',  'label' => 'Inactive',  'color' => 'gray',    'system' => true],
            ['key' => 'on_leave',  'label' => 'On Leave',  'color' => 'amber',   'system' => false],
            ['key' => 'resigned',  'label' => 'Resigned',  'color' => 'red',     'system' => false],
        ]);

        // ── 8. Course Delivery Mode ───────────────────────────────────────────
        $id = $cat('course_delivery_mode', 'Course Delivery Mode', 'course', 'How the course is delivered to students.', false);
        $vals($id, [
            ['key' => 'online',  'label' => 'Online',  'color' => 'blue',    'system' => true, 'default' => true, 'description' => 'Fully online via Zoom or similar platform.'],
            ['key' => 'offline', 'label' => 'Offline', 'color' => 'amber',   'system' => true, 'description' => 'In-person at a physical location.'],
            ['key' => 'hybrid',  'label' => 'Hybrid',  'color' => 'purple',  'system' => true, 'description' => 'Combination of online and offline classes.'],
        ]);

        // ── 9. Duration Type ──────────────────────────────────────────────────
        $id = $cat('duration_type', 'Duration Unit', 'course', 'Unit used to express course or subscription duration.', false);
        $vals($id, [
            ['key' => 'hours',   'label' => 'Hours',   'system' => true],
            ['key' => 'days',    'label' => 'Days',    'system' => true],
            ['key' => 'weeks',   'label' => 'Weeks',   'system' => true, 'default' => true],
            ['key' => 'months',  'label' => 'Months',  'system' => true],
            ['key' => 'years',   'label' => 'Years',   'system' => false],
        ]);

        // ── 10. Gender ────────────────────────────────────────────────────────
        $id = $cat('gender', 'Gender', 'user', 'Gender options for user profiles.', true);
        $vals($id, [
            ['key' => 'male',              'label' => 'Male',              'system' => true],
            ['key' => 'female',            'label' => 'Female',            'system' => true],
            ['key' => 'other',             'label' => 'Other',             'system' => true],
            ['key' => 'prefer_not_to_say', 'label' => 'Prefer Not to Say','system' => true],
        ]);

        // ── 11. Mock Test Subscription Type ───────────────────────────────────
        $id = $cat('mock_test_type', 'Mock Test Type', 'mock_test', 'Classification of mock test subscription offerings.', true);
        $vals($id, [
            ['key' => 'official',    'label' => 'Official Practice Test', 'color' => 'emerald', 'system' => true, 'default' => true],
            ['key' => 'practice',    'label' => 'Practice Set',           'color' => 'blue',    'system' => true],
            ['key' => 'diagnostic',  'label' => 'Diagnostic Test',        'color' => 'amber',   'system' => false],
            ['key' => 'mock_exam',   'label' => 'Full Mock Exam',         'color' => 'purple',  'system' => false],
        ]);

        // ── 12. Subscription Duration Plan ───────────────────────────────────
        $id = $cat('subscription_plan', 'Subscription Plan', 'mock_test', 'Duration-based plan tiers for mock test subscriptions.', true);
        $vals($id, [
            ['key' => 'one_time',     'label' => 'One-Time Access',   'color' => 'gray',    'system' => true, 'default' => true],
            ['key' => 'monthly',      'label' => 'Monthly',           'color' => 'blue',    'system' => true],
            ['key' => 'quarterly',    'label' => 'Quarterly (3 mo.)', 'color' => 'purple',  'system' => true],
            ['key' => 'half_yearly',  'label' => 'Half-Yearly',       'color' => 'amber',   'system' => false],
            ['key' => 'yearly',       'label' => 'Yearly',            'color' => 'emerald', 'system' => true],
        ]);

        // ── 13. User / Account Status ─────────────────────────────────────────
        $id = $cat('user_status', 'User Account Status', 'user', 'Status of a user account in the system.', false);
        $vals($id, [
            ['key' => 'active',    'label' => 'Active',    'color' => 'emerald', 'system' => true, 'default' => true, 'description' => 'User can log in and use the system.'],
            ['key' => 'inactive',  'label' => 'Inactive',  'color' => 'gray',    'system' => true, 'description' => 'User account deactivated by admin.'],
            ['key' => 'suspended', 'label' => 'Suspended', 'color' => 'red',     'system' => false, 'description' => 'User temporarily suspended.'],
            ['key' => 'pending',   'label' => 'Pending',   'color' => 'amber',   'system' => false, 'description' => 'Account awaiting email verification.'],
        ]);

        // ── 14. Course Status ─────────────────────────────────────────────────
        $id = $cat('course_status', 'Course Status', 'course', 'Publication and availability status of a course.', false);
        $vals($id, [
            ['key' => 'active',   'label' => 'Active',   'color' => 'emerald', 'system' => true, 'default' => true, 'description' => 'Course is live and enrollments are open.'],
            ['key' => 'inactive', 'label' => 'Inactive', 'color' => 'gray',    'system' => true, 'description' => 'Course is hidden from public listing.'],
            ['key' => 'draft',    'label' => 'Draft',    'color' => 'amber',   'system' => false, 'description' => 'Course is being prepared; not yet published.'],
            ['key' => 'archived', 'label' => 'Archived', 'color' => 'slate',   'system' => false, 'description' => 'Course retired; historical record only.'],
        ]);

        // ── 15. Contact Message Status ────────────────────────────────────────
        $id = $cat('contact_status', 'Contact Message Status', 'contact', 'Processing status of an inbound contact message.', true);
        $vals($id, [
            ['key' => 'new',         'label' => 'New',         'color' => 'amber',   'system' => true, 'default' => true],
            ['key' => 'in_progress', 'label' => 'In Progress', 'color' => 'blue',    'system' => true],
            ['key' => 'resolved',    'label' => 'Resolved',    'color' => 'emerald', 'system' => true],
            ['key' => 'spam',        'label' => 'Spam',        'color' => 'gray',    'system' => false],
        ]);

        // ── 16. Exam Type ─────────────────────────────────────────────────────
        $id = $cat('exam_type', 'Exam / Test Type', 'exam_booking', 'Types of exams available for booking.', true);
        $vals($id, [
            ['key' => 'pte',    'label' => 'PTE Academic',      'color' => 'blue',    'system' => true, 'default' => true],
            ['key' => 'ielts',  'label' => 'IELTS',             'color' => 'emerald', 'system' => true],
            ['key' => 'toefl',  'label' => 'TOEFL iBT',         'color' => 'amber',   'system' => false],
            ['key' => 'oet',    'label' => 'OET',               'color' => 'purple',  'system' => false],
            ['key' => 'sat',    'label' => 'SAT',               'color' => 'teal',    'system' => false],
            ['key' => 'gre',    'label' => 'GRE',               'color' => 'rose',    'system' => false],
        ]);

        // ── 17. Offer / Discount Type ─────────────────────────────────────────
        $id = $cat('discount_type', 'Discount Type', 'offer', 'How a discount or offer value is applied.', false);
        $vals($id, [
            ['key' => 'percentage', 'label' => 'Percentage (%)',      'system' => true, 'default' => true],
            ['key' => 'fixed',      'label' => 'Fixed Amount (NPR)',  'system' => true],
        ]);

        // ── 18. Testimonial Status ────────────────────────────────────────────
        $id = $cat('testimonial_status', 'Testimonial Status', 'testimonial', 'Moderation status of a student testimonial.', false);
        $vals($id, [
            ['key' => 'pending',   'label' => 'Pending Review', 'color' => 'amber',   'system' => true, 'default' => true],
            ['key' => 'approved',  'label' => 'Approved',       'color' => 'emerald', 'system' => true],
            ['key' => 'rejected',  'label' => 'Rejected',       'color' => 'red',     'system' => true],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('lookup_values');
        Schema::dropIfExists('lookup_categories');
    }
};
