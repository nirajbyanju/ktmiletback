<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Course;
use App\Models\DemoRequest;
use App\Models\Enrollment;
use App\Models\ExamBooking;
use App\Models\ExamBookingEnrollment;
use App\Models\Invoice;
use App\Models\MockTestEnrollment;
use App\Models\MockTestSubscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Comprehensive demo data for test@ktm.edu.np / password11
 *
 * Covers EVERY plan in the system with ALL 8 payment statuses so every
 * UI state in /my-account can be verified in a single login.
 *
 * ╔══════════════════════════════════════════════════════════════════════════╗
 * ║  COURSE ENROLLMENTS (10 batches — IELTS × 5 + PTE × 5)                 ║
 * ╠══════════════╦═══════════════════════╦════════════════════════════════╣
 * ║  Batch       ║  crm_payment_status   ║  invoice / enrollment status   ║
 * ╠══════════════╬═══════════════════════╬════════════════════════════════╣
 * ║ IELTS        ║                       ║                                ║
 * ║  Elite Private       action_required   unpaid  / payment_pending      ║
 * ║  Premium Focus       under_review      unpaid  / payment_pending      ║
 * ║  Value Batch         not_verified      unpaid  / payment_pending      ║
 * ║  Smart Batch         confirmed         paid    / active               ║
 * ║  Friends Private     confirmed         paid    / completed            ║
 * ║ PTE          ║                       ║                                ║
 * ║  Elite Private       action_required   unpaid  / payment_pending      ║
 * ║  Premium Focus       fee_waived        paid    / active               ║
 * ║  Value Batch         refund_under_review paid  / active               ║
 * ║  Smart Batch         refund_not_approved paid  / dropped              ║
 * ║  Friends Private     refund_completed  refunded/ dropped              ║
 * ╠══════════════════════════════════════════════════════════════════════════╣
 * ║  MOCK TEST SUBSCRIPTIONS (5 plans)                                      ║
 * ╠══════════════╦═══════════════════════╦════════════════════════════════╣
 * ║  IELTS Monthly       action_required   unpaid  / no enrollment        ║
 * ║  IELTS Quarterly     under_review      unpaid  / no enrollment        ║
 * ║  PTE Monthly         not_verified      unpaid  / no enrollment        ║
 * ║  PTE Full Bundle     confirmed         paid    / active enrollment    ║
 * ║  TOEFL Monthly       fee_waived        paid    / active enrollment    ║
 * ╠══════════════════════════════════════════════════════════════════════════╣
 * ║  EXAM BOOKING REQUESTS (6 plans)                                        ║
 * ╠══════════════╦═══════════════════════╦════════════════════════════════╣
 * ║  IELTS Academic      action_required   unpaid  / payment_pending      ║
 * ║  IELTS General       under_review      unpaid  / payment_pending      ║
 * ║  PTE Academic        not_verified      unpaid  / payment_pending      ║
 * ║  PTE Academic UKVI   confirmed         paid    / booking_in_process   ║
 * ║  TOEFL iBT           confirmed         paid    / booked               ║
 * ║  Duolingo            fee_waived        paid    / booked               ║
 * ╠══════════════════════════════════════════════════════════════════════════╣
 * ║  LIVE DEMO SESSIONS (3 requests)                                        ║
 * ╠══════════════╦═══════════════════════╦════════════════════════════════╣
 * ║  IELTS demo          pending                                           ║
 * ║  PTE demo            approved  (zoom link + scheduled time)           ║
 * ║  IELTS General demo  rejected  (admin note)                           ║
 * ╚══════════════════════════════════════════════════════════════════════════╝
 */
class TestStudentSeeder extends Seeder
{
    private int $invoiceCounter = 9000;

    public function run(): void
    {
        // ─────────────────────────────────────────────────────────────────────
        // 1. Test student account
        // ─────────────────────────────────────────────────────────────────────
        /** @var User $s */
        $s = User::updateOrCreate(
            ['email' => 'test@ktm.edu.np'],
            [
                'userCode' => 'KTM-TEST-001',
                'name' => 'Test Student',
                'first_name' => 'Test',
                'middle_name' => null,
                'last_name' => 'Student',
                'username' => 'teststudent001',
                'phone' => '+9779800001234',
                'password' => Hash::make('password11'),
                'has_password' => true,
                'email_verified_at' => now()->subDays(15),
                'status' => 1,
                'remember_token' => Str::random(10),
            ]
        );
        $s->syncRoles(['User']);
        $this->command->info('✓ test@ktm.edu.np / password11');

        // ─────────────────────────────────────────────────────────────────────
        // 2. Ensure all plans exist (safe — won't overwrite if already seeded)
        // ─────────────────────────────────────────────────────────────────────
        $this->ensureCoursePlans();
        $this->ensureMockTestPlans();
        $this->ensureExamBookingPlans();

        // ─────────────────────────────────────────────────────────────────────
        // 3. Course enrollments — all 10 batches
        // ─────────────────────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('── Course Enrollments (10 batches) ────────────');

        $courseScenarios = [
            // [course_name, batch_type, crm_payment_status, days_ago, enrollment_status, attendance, certificate]
            ['IELTS Preparation Course',      'Elite Private',        'action_required',      2,  'payment_pending', null,  false],
            ['IELTS Preparation Course',      'Premium Focus',        'under_review',         8,  'payment_pending', null,  false],
            ['IELTS Preparation Course',      'Value Batch',          'not_verified',         12, 'payment_pending', null,  false],
            ['IELTS Preparation Course',      'Smart Batch',          'confirmed',            20, 'active',          85.5,  false],
            ['IELTS Preparation Course',      'Friends Private Group', 'confirmed',            60, 'completed',       92.0,  true],
            ['PTE Academic Preparation Course', 'Elite Private',       'action_required',      3,  'payment_pending', null,  false],
            ['PTE Academic Preparation Course', 'Premium Focus',       'fee_waived',           15, 'active',          78.0,  false],
            ['PTE Academic Preparation Course', 'Value Batch',         'refund_under_review',  25, 'active',          45.0,  false],
            ['PTE Academic Preparation Course', 'Smart Batch',         'refund_not_approved',  40, 'dropped',         30.0,  false],
            ['PTE Academic Preparation Course', 'Friends Private Group', 'refund_completed',    50, 'dropped',         20.0,  false],
        ];

        $teachers = ['Rajesh Sir', "Priya Ma'am", 'Santosh Sir', "Anita Ma'am", 'Bikram Sir'];

        foreach ($courseScenarios as $i => [$courseName, $batchType, $crmStatus, $daysAgo, $enrollStatus, $attendance, $cert]) {
            $batch = Batch::whereHas('course', fn ($q) => $q->where('course_name', $courseName))
                ->where('batch_type', $batchType)
                ->first();

            if (! $batch) {
                $this->command->warn("  Batch not found: {$courseName} / {$batchType}");

                continue;
            }

            $date = Carbon::now()->subDays($daysAgo);
            $isPaid = in_array($crmStatus, ['confirmed', 'fee_waived', 'refund_under_review', 'refund_not_approved', 'refund_completed']);
            $isRefunded = $crmStatus === 'refund_completed';
            $invStatus = $isRefunded ? Invoice::STATUS_REFUNDED : ($isPaid ? Invoice::STATUS_PAID : Invoice::STATUS_UNPAID);
            $invoiceNo = 'KTM-DEMO-CRS-'.str_pad(++$this->invoiceCounter, 4, '0', STR_PAD_LEFT);
            $price = (float) $batch->price_npr;
            $discount = $crmStatus === 'fee_waived' ? $price : 0;   // waive = full discount
            $total = $price - $discount;

            $invoice = Invoice::updateOrCreate(
                ['invoice_number' => $invoiceNo],
                [
                    'user_id' => $s->id,
                    'batch_id' => $batch->id,
                    'subtotal_npr' => $price,
                    'discount_npr' => $discount,
                    'tax_npr' => 0,
                    'total_npr' => $total,
                    'status' => $invStatus,
                    'crm_payment_status' => $crmStatus,
                    'payment_method' => $isPaid ? 'bank_transfer' : 'bank_qr',
                    'invoice_date' => $date->toDateString(),
                    'due_date' => $date->copy()->addDays(7)->toDateString(),
                    'verified_at' => $isPaid ? $date->copy()->addDays(2) : null,
                    'screenshot_uploaded_at' => in_array($crmStatus, ['under_review', 'not_verified'])
                        ? $date->copy()->addDays(1) : null,
                    'refunded_amount_npr' => $isRefunded ? $total : null,
                    'refund_reason' => $isRefunded ? 'Student requested refund due to schedule conflict.' : null,
                    'refunded_at' => $isRefunded ? $date->copy()->addDays(5) : null,
                    'notes' => $crmStatus === 'not_verified'
                        ? 'Screenshot unclear — please resubmit a legible payment proof.'
                        : ($crmStatus === 'refund_not_approved' ? 'Refund not approved — attendance exceeded 30%.' : null),
                ]
            );

            Enrollment::updateOrCreate(
                ['user_id' => $s->id, 'batch_id' => $batch->id],
                [
                    'student_name' => $s->name,
                    'phone' => $s->phone,
                    'email' => $s->email,
                    'invoice_id' => $invoice->id,
                    'enrollment_date' => $date->toDateString(),
                    'amount_paid' => $isPaid ? $total : 0,
                    'status' => $enrollStatus,
                    'payment_status' => $crmStatus,
                    'crm_status' => match ($enrollStatus) {
                        'active' => 'active',
                        'completed' => 'completed',
                        'dropped' => 'dropped',
                        default => 'enrolled',
                    },
                    'teacher' => $isPaid ? $teachers[$i % count($teachers)] : null,
                    'attendance_percentage' => $attendance,
                    'certificate_eligible' => $cert,
                    'notes' => $isRefunded ? 'Refund processed. Student dropped.' : null,
                ]
            );

            $this->command->info(sprintf(
                '  ✓ %-12s %-22s → %s',
                str_contains($courseName, 'IELTS') ? 'IELTS' : 'PTE',
                $batchType,
                $crmStatus
            ));
        }

        // ─────────────────────────────────────────────────────────────────────
        // 4. Mock Test Subscriptions — all 5 plans
        // ─────────────────────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('── Mock Test Subscriptions (5 plans) ──────────');

        $mockScenarios = [
            // [plan_name, crm_status, days_ago, has_active_enrollment]
            ['IELTS Mock Test - Monthly',   'action_required', 4,  false],
            ['IELTS Mock Test - Quarterly', 'under_review',    9,  false],
            ['PTE Mock Test - Monthly',     'not_verified',    14, false],
            ['PTE Mock Test - Full Bundle', 'confirmed',       22, true],
            ['TOEFL Mock Test - Monthly',   'fee_waived',      30, true],
        ];

        foreach ($mockScenarios as [$planName, $crmStatus, $daysAgo, $makeEnrollment]) {
            $plan = MockTestSubscription::where('subscriptions_name', $planName)->first();
            if (! $plan) {
                $this->command->warn("  Plan not found: {$planName}");

                continue;
            }

            $date = Carbon::now()->subDays($daysAgo);
            $isPaid = in_array($crmStatus, ['confirmed', 'fee_waived']);
            $price = (float) $plan->price;
            $disc = $crmStatus === 'fee_waived' ? $price : (float) ($plan->discount ?? 0);
            $total = $price - $disc;
            $invNo = 'KTM-DEMO-MCK-'.str_pad(++$this->invoiceCounter, 4, '0', STR_PAD_LEFT);

            $invoice = Invoice::updateOrCreate(
                ['invoice_number' => $invNo],
                [
                    'user_id' => $s->id,
                    'mock_test_subscription_id' => $plan->id,
                    'subtotal_npr' => $price,
                    'discount_npr' => $disc,
                    'tax_npr' => 0,
                    'total_npr' => $total,
                    'status' => $isPaid ? Invoice::STATUS_PAID : Invoice::STATUS_UNPAID,
                    'crm_payment_status' => $crmStatus,
                    'payment_method' => $isPaid ? 'bank_transfer' : 'bank_qr',
                    'invoice_date' => $date->toDateString(),
                    'due_date' => $date->copy()->addDays(7)->toDateString(),
                    'verified_at' => $isPaid ? $date->copy()->addDays(2) : null,
                    'screenshot_uploaded_at' => in_array($crmStatus, ['under_review', 'not_verified'])
                        ? $date->copy()->addDays(1) : null,
                    'notes' => $crmStatus === 'not_verified'
                        ? 'Screenshot is blurry — please resubmit.' : null,
                ]
            );

            if ($makeEnrollment && $isPaid) {
                $start = $date->copy()->addDay();
                $end = $start->copy()->addDays((int) ($plan->duration ?? 30));
                MockTestEnrollment::updateOrCreate(
                    ['user_id' => $s->id, 'subscription_id' => $plan->id],
                    [
                        'invoice_id' => $invoice->id,
                        'enrollment_date' => $date->toDateString(),
                        'subscription_start' => $start->toDateString(),
                        'subscription_end' => $end->toDateString(),
                    ]
                );
            }

            $this->command->info(sprintf('  ✓ %-32s → %s', $planName, $crmStatus));
        }

        // ─────────────────────────────────────────────────────────────────────
        // 5. Exam Booking Requests — all 6 plans
        // ─────────────────────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('── Exam Booking Requests (6 plans) ────────────');

        $examScenarios = [
            // [type, name, centre, crm_status, enrollment_status, days_ago]
            ['IELTS', 'IELTS Academic',       'British Council Kathmandu', 'action_required', 'payment_pending',   3],
            ['IELTS', 'IELTS General Training', 'IDP Kathmandu',             'under_review',    'payment_pending',   9],
            ['PTE',   'PTE Academic',          'Pearson VUE Kathmandu',     'not_verified',    'payment_pending',   13],
            ['PTE',   'PTE Academic UKVI',     'Pearson VUE Kathmandu',     'confirmed',       'booking_in_process', 20],
            ['TOEFL', 'TOEFL iBT',             'IDP Kathmandu',             'confirmed',       'booked',            30],
            ['Duolingo', 'Duolingo English Test', 'Online',                   'fee_waived',      'booked',            35],
        ];

        $passportSuffix = 1000;
        foreach ($examScenarios as [$type, $name, $centre, $crmStatus, $enrollStatus, $daysAgo]) {
            $plan = ExamBooking::where('exam_type', $type)->where('exam_name', $name)->first();
            if (! $plan) {
                $this->command->warn("  Plan not found: {$name}");

                continue;
            }

            $date = Carbon::now()->subDays($daysAgo);
            $isPaid = in_array($crmStatus, ['confirmed', 'fee_waived']);
            $price = (float) $plan->price;
            $disc = $crmStatus === 'fee_waived' ? $price : (float) ($plan->discount ?? 0);
            $total = $price - $disc;
            $invNo = 'KTM-DEMO-EXM-'.str_pad(++$this->invoiceCounter, 4, '0', STR_PAD_LEFT);
            $passNum = 'PA'.++$passportSuffix;

            $enrollment = ExamBookingEnrollment::updateOrCreate(
                ['user_id' => $s->id, 'exam_booking_id' => $plan->id],
                [
                    'preferred_date' => Carbon::now()->addDays(rand(20, 60))->toDateString(),
                    'preferred_time' => ['09:00', '11:00', '14:00'][$daysAgo % 3],
                    'test_location' => $centre,
                    'preferred_test_centre' => $centre,
                    'passport_name' => 'TEST STUDENT',
                    'passport_number' => $passNum,
                    'date_of_birth' => '2000-01-15',
                    'contact_number' => '+9779800001234',
                    'phone' => '+9779800001234',
                    'email' => $s->email,
                    'special_message' => in_array($enrollStatus, ['booked', 'booking_in_process'])
                        ? null : 'Please arrange the earliest available slot.',
                    'status' => $enrollStatus,
                    'available_slot_checked' => in_array($enrollStatus, ['booking_in_process', 'booked']),
                    'admin_notes' => match ($enrollStatus) {
                        'booked' => 'Booking confirmed. Ref: KTM-EX-'.rand(1000, 9999),
                        'booking_in_process' => 'Slot secured. Awaiting final confirmation from test centre.',
                        default => null,
                    },
                ]
            );

            Invoice::updateOrCreate(
                ['invoice_number' => $invNo],
                [
                    'user_id' => $s->id,
                    'exam_booking_enrollment_id' => $enrollment->id,
                    'subtotal_npr' => $price,
                    'discount_npr' => $disc,
                    'tax_npr' => 0,
                    'total_npr' => $total,
                    'status' => $isPaid ? Invoice::STATUS_PAID : Invoice::STATUS_UNPAID,
                    'crm_payment_status' => $crmStatus,
                    'payment_method' => $isPaid ? 'bank_transfer' : 'bank_qr',
                    'invoice_date' => $date->toDateString(),
                    'due_date' => $date->copy()->addDays(7)->toDateString(),
                    'verified_at' => $isPaid ? $date->copy()->addDays(2) : null,
                    'screenshot_uploaded_at' => in_array($crmStatus, ['under_review', 'not_verified'])
                        ? $date->copy()->addDays(1) : null,
                    'notes' => $crmStatus === 'not_verified'
                        ? 'Payment proof unreadable. Please resubmit a clear screenshot.' : null,
                ]
            );

            $this->command->info(sprintf('  ✓ %-28s → %s', $name, $crmStatus));
        }

        // ─────────────────────────────────────────────────────────────────────
        // 6. Live Demo Sessions — pending / approved / rejected
        // ─────────────────────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('── Live Demo Sessions (3 requests) ────────────');

        $demoSessions = [
            [
                'course_name' => 'IELTS Preparation Course',
                'preferred_at' => 'Weekday morning (10 AM–12 PM)',
                'country' => 'Nepal',
                'status' => 'pending',
                'zoom_url' => null,
                'scheduled_at' => null,
                'admin_notes' => null,
                'read_at' => null,
                'days_ago' => 2,
            ],
            [
                'course_name' => 'PTE Academic Preparation Course',
                'preferred_at' => 'Weekend afternoon (2–4 PM)',
                'country' => 'Nepal',
                'status' => 'approved',
                'zoom_url' => 'https://zoom.us/j/98765432100?pwd=ktmtestprep',
                'scheduled_at' => Carbon::now()->addDays(3)->setTime(14, 0, 0),
                'admin_notes' => 'Session confirmed. Please join 5 minutes early. Zoom link sent to your email.',
                'read_at' => Carbon::now()->subDays(5),
                'days_ago' => 7,
            ],
            [
                'course_name' => 'IELTS General Training',
                'preferred_at' => 'Evening (6–8 PM)',
                'country' => 'Nepal',
                'status' => 'rejected',
                'zoom_url' => null,
                'scheduled_at' => null,
                'admin_notes' => 'No evening slots available this month. Please request again next month or choose a morning slot.',
                'read_at' => Carbon::now()->subDays(12),
                'days_ago' => 14,
            ],
        ];

        foreach ($demoSessions as $d) {
            DemoRequest::updateOrCreate(
                ['user_id' => $s->id, 'course_name' => $d['course_name']],
                [
                    'name' => $s->name,
                    'email' => $s->email,
                    'phone' => $s->phone,
                    'country' => $d['country'],
                    'preferred_at' => $d['preferred_at'],
                    'course_id' => null,
                    'status' => $d['status'],
                    'zoom_url' => $d['zoom_url'],
                    'scheduled_at' => $d['scheduled_at'],
                    'admin_notes' => $d['admin_notes'],
                    'read_at' => $d['read_at'],
                    'created_at' => Carbon::now()->subDays($d['days_ago']),
                    'updated_at' => Carbon::now()->subDays((int) ($d['days_ago'] / 2)),
                ]
            );
            $this->command->info(sprintf('  ✓ %-36s → %s', $d['course_name'], $d['status']));
        }

        // Summary
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════');
        $this->command->info('TestStudentSeeder complete.');
        $this->command->info('Login   : test@ktm.edu.np');
        $this->command->info('Password: password11');
        $this->command->info('Records : 10 courses + 5 mock tests + 6 exam bookings + 3 demo sessions');
        $this->command->info('═══════════════════════════════════════════════');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Guard methods — create plans only if they do not already exist
    // ─────────────────────────────────────────────────────────────────────────

    private function ensureCoursePlans(): void
    {
        $courses = [
            'IELTS Preparation Course' => 'IELTS',
            'PTE Academic Preparation Course' => 'PTE',
        ];

        $batches = [
            ['batch_type' => 'Elite Private',        'price_npr' => 30000, 'min_size' => 1,    'max_size' => 1,    'schedule_notes' => '4 weeks · 20 hrs · Mon–Fri'],
            ['batch_type' => 'Premium Focus',         'price_npr' => 5999,  'min_size' => 5,    'max_size' => 11,   'schedule_notes' => '6 weeks · 30 hrs · Mon–Fri'],
            ['batch_type' => 'Value Batch',           'price_npr' => 2199,  'min_size' => 21,   'max_size' => 30,   'schedule_notes' => '6 weeks · 30 hrs · Mon–Fri'],
            ['batch_type' => 'Smart Batch',           'price_npr' => 2999,  'min_size' => 12,   'max_size' => 20,   'schedule_notes' => '6 weeks · 30 hrs · Mon–Fri'],
            ['batch_type' => 'Friends Private Group', 'price_npr' => 45000, 'min_size' => null, 'max_size' => null, 'schedule_notes' => '6 weeks · 30 hrs · Mon–Fri'],
        ];

        foreach (array_keys($courses) as $courseName) {
            $course = Course::updateOrCreate(
                ['course_name' => $courseName],
                ['is_status' => 1, 'delivery' => 'online', 'duration' => 6, 'duration_type' => 'weeks']
            );
            foreach ($batches as $b) {
                Batch::updateOrCreate(
                    ['course_id' => $course->id, 'batch_type' => $b['batch_type']],
                    array_merge($b, ['course_id' => $course->id, 'is_active' => true, 'is_featured' => false])
                );
            }
        }
    }

    private function ensureMockTestPlans(): void
    {
        $plans = [
            ['subscriptions_name' => 'IELTS Mock Test - Monthly',   'subscriptions_category' => 'IELTS', 'price' => 1500, 'discount' => 0,   'duration' => 30,  'duration_type' => 'days'],
            ['subscriptions_name' => 'IELTS Mock Test - Quarterly',  'subscriptions_category' => 'IELTS', 'price' => 3800, 'discount' => 100, 'duration' => 90,  'duration_type' => 'days'],
            ['subscriptions_name' => 'PTE Mock Test - Monthly',      'subscriptions_category' => 'PTE',   'price' => 1200, 'discount' => 0,   'duration' => 30,  'duration_type' => 'days'],
            ['subscriptions_name' => 'PTE Mock Test - Full Bundle',  'subscriptions_category' => 'PTE',   'price' => 4500, 'discount' => 500, 'duration' => 180, 'duration_type' => 'days'],
            ['subscriptions_name' => 'TOEFL Mock Test - Monthly',    'subscriptions_category' => 'TOEFL', 'price' => 1800, 'discount' => 0,   'duration' => 30,  'duration_type' => 'days'],
        ];

        foreach ($plans as $p) {
            MockTestSubscription::updateOrCreate(
                ['subscriptions_name' => $p['subscriptions_name']],
                array_merge($p, ['subscriptions_type' => 'Mock Test', 'company_name' => 'KTM Consultancy', 'country' => 'Nepal'])
            );
        }
    }

    private function ensureExamBookingPlans(): void
    {
        $plans = [
            ['exam_type' => 'IELTS',     'exam_name' => 'IELTS Academic',          'price' => 34000, 'discount' => 1000],
            ['exam_type' => 'IELTS',     'exam_name' => 'IELTS General Training',   'price' => 34000, 'discount' => 1000],
            ['exam_type' => 'PTE',       'exam_name' => 'PTE Academic',             'price' => 28000, 'discount' => 0],
            ['exam_type' => 'PTE',       'exam_name' => 'PTE Academic UKVI',        'price' => 32000, 'discount' => 0],
            ['exam_type' => 'TOEFL',     'exam_name' => 'TOEFL iBT',               'price' => 22000, 'discount' => 500],
            ['exam_type' => 'Duolingo',  'exam_name' => 'Duolingo English Test',    'price' => 7500,  'discount' => 0],
        ];

        foreach ($plans as $p) {
            ExamBooking::updateOrCreate(
                ['exam_type' => $p['exam_type'], 'exam_name' => $p['exam_name']],
                ['price' => $p['price'], 'discount' => $p['discount']]
            );
        }
    }
}
