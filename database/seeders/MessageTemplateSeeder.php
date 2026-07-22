<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds all communication templates (Bimal's 36-template document, content
 * corrected for the actual website, + the legacy WhatsApp quick templates).
 *
 * Corrections applied per Bimal (12 July 2026):
 *  - Payment method is Siddhartha Bank QR / bank transfer + screenshot only
 *  - No fixed discount amounts in promotional templates
 *  - Course-materials / certificate-download references removed (not built)
 *  - Contact email: ktmtestpreparation@ktmeducational.edu.np
 *  - Sign-offs/footers live in the branded layout, not in bodies
 */
class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $tpl) {
            MessageTemplate::updateOrCreate(
                ['key' => $tpl['key']],
                [
                    ...$tpl,
                    'placeholders' => $tpl['placeholders'] ?? [],
                    'default_subject' => $tpl['subject'] ?? null,
                    'default_body' => $tpl['body'],
                    'default_cta_text' => $tpl['cta_text'] ?? null,
                ]
            );
        }
    }

    private function templates(): array
    {
        $auto = fn (array $t) => [...$t, 'category' => 'email_auto', 'automation' => 'active', 'is_enabled' => true];
        $sched = fn (array $t) => [...$t, 'category' => 'email_auto', 'automation' => 'scheduler', 'is_enabled' => false];
        $manual = fn (array $t) => [...$t, 'category' => 'email_manual', 'automation' => 'manual', 'is_enabled' => true];
        $wa = fn (array $t) => [...$t, 'category' => 'whatsapp', 'automation' => 'manual', 'is_enabled' => true];

        return [
            // ═══════════ A. ACCOUNT & AUTHENTICATION ═══════════
            $auto([
                'key' => 'welcome_account', 'name' => 'Welcome (Account Created)', 'group_label' => 'Account',
                'trigger_label' => 'Sends automatically when a student signs up',
                'subject' => 'Welcome to KTM Test Preparation Centre, [FirstName]',
                'body' => "Dear [StudentName],\n\nThank you for creating your student account at KTM Test Preparation Centre. Your account is now active and you can log in anytime at our website.\n\nThrough your student dashboard, you can enrol in a class, subscribe to mock test practice, book your IELTS or PTE exam, upload payment screenshots, and track every status in real time.\n\nIf you have any questions, simply reply to this email.",
                'cta_text' => 'Go to Your Dashboard', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'FirstName'],
            ]),
            $auto([
                'key' => 'password_reset', 'name' => 'Password Reset Request', 'group_label' => 'Account',
                'trigger_label' => "Sends when a student clicks 'Forgot password?'",
                'subject' => 'Reset your KTM Test Prep password',
                'body' => "Dear [StudentName],\n\nWe received a request to reset the password for your KTM Test Preparation Centre account. Click the button below to set a new password.\n\nThis link will expire in 60 minutes for your security. If you did not request a password reset, you may safely ignore this email and your password will remain unchanged.\n\nIf the button does not work, copy and paste this link into your browser:\n[ResetLink]",
                'cta_text' => 'Reset My Password',
                'placeholders' => ['StudentName', 'ResetLink'],
            ]),
            $auto([
                'key' => 'password_changed', 'name' => 'Password Reset Confirmation', 'group_label' => 'Account',
                'trigger_label' => 'Sends after a password is successfully changed',
                'subject' => 'Your password has been changed',
                'body' => "Dear [StudentName],\n\nThis is to confirm that the password for your KTM Test Preparation Centre account was successfully changed on [PasswordChangeDate].\n\nIf you did not make this change, please contact our support team immediately by replying to this email.",
                'cta_text' => 'Log In to Dashboard', 'cta_path' => '/login',
                'placeholders' => ['StudentName', 'PasswordChangeDate'],
            ]),

            // ═══════════ B. ENROLMENT & PAYMENT ═══════════
            $auto([
                'key' => 'enrollment_received', 'name' => 'Enrolment Request Received', 'group_label' => 'Enrolment & Payment',
                'trigger_label' => 'Sends when a student generates a class enrolment invoice',
                'subject' => 'Your [CourseName] enrolment request has been received',
                'body' => "Dear [StudentName],\n\nThank you for choosing KTM Test Preparation Centre. Your enrolment request for the following class has been received and is now pending payment confirmation.\n\nCourse: [CourseName]\nPlan: [PlanName]\nInvoice: [InvoiceNumber]\nFee: [PaymentAmount]\n\nPlease complete your payment via Siddhartha Bank QR or bank transfer, then upload your payment screenshot in your dashboard to secure your seat. Once payment is verified by our team, you will receive a separate enrolment confirmation with your class schedule and Zoom details.",
                'cta_text' => 'Pay & Confirm My Seat', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'CourseName', 'PlanName', 'InvoiceNumber', 'PaymentAmount'],
            ]),
            $manual([
                'key' => 'payment_instructions', 'name' => 'Payment Instructions', 'group_label' => 'Enrolment & Payment',
                'when_to_use' => 'Send manually when a student asks how to pay again',
                'subject' => 'Payment instructions for your [CourseName] enrolment',
                'body' => "Dear [StudentName],\n\nPlease find below the payment instructions for your [CourseName] enrolment.\n\nAmount Due: [PaymentAmount]\nInvoice: [InvoiceNumber]\n\nPayment is accepted via Siddhartha Bank QR or bank transfer. The QR code and full account details are shown on your payment page in the student dashboard.\n\nAfter completing the payment, please upload your payment screenshot in your dashboard. Our team will verify your payment within a few working hours and confirm your enrolment by email.",
                'cta_text' => 'Pay & Upload Screenshot', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'CourseName', 'PaymentAmount', 'InvoiceNumber'],
            ]),
            $auto([
                'key' => 'screenshot_received', 'name' => 'Payment Screenshot Received', 'group_label' => 'Enrolment & Payment',
                'trigger_label' => 'Sends when a student uploads a payment screenshot',
                'subject' => 'We have received your payment — verification pending',
                'body' => "Dear [StudentName],\n\nThank you for submitting your payment of [PaymentAmount] for [ItemName].\n\nYour payment screenshot has been received and is now pending verification by our admin team. Verification typically takes a few working hours.\n\nYou will receive a confirmation email as soon as the payment is verified. You can also check the status anytime in your student dashboard.",
                'cta_text' => 'View Payment Status', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'PaymentAmount', 'ItemName'],
            ]),
            $auto([
                'key' => 'referral_reward_earned', 'name' => 'Referral Reward Earned', 'group_label' => 'Enrolment & Payment',
                'trigger_label' => 'Sends to the referrer when a friend they referred completes their first course payment',
                'subject' => 'You earned a [RewardAmount] reward — thank you for referring a friend!',
                'body' => "Dear [StudentName],\n\nGreat news! [FriendName], who joined KTM Test Preparation Centre using your referral code, has completed their first payment.\n\nAs a thank-you, we have added a [RewardAmount] discount voucher to your account. It will be applied automatically the next time you enrol in a class, book an exam, or subscribe to mock test practice.\n\nKeep sharing your referral code — there is no limit to how many friends you can help, and every friend who joins and pays earns you another reward.",
                'cta_text' => 'View My Rewards', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'FriendName', 'RewardAmount'],
            ]),
            $auto([
                'key' => 'group_member_added', 'name' => 'Added to a Group Booking', 'group_label' => 'Enrolment & Payment',
                'trigger_label' => 'Sends to each group member when a Friends Private Group booking is made with their email',
                'subject' => 'You have been added to a [CourseName] group class at KTM Test Preparation',
                'body' => "Dear [StudentName],\n\n[LeaderName] has added you as a member of their Friends Private Group booking for [CourseName] at KTM Test Preparation Centre. Your seat in this group class is covered by the group booking — there is nothing for you to pay.\n\nOnce the group's payment is verified, you will attend the same classes as your group. To access class details, learning materials, and other services in the future, please create a free student account using this same email address ([MemberEmail]). Your group enrolment will be linked to your account automatically.\n\nIf you were added by mistake or have any questions, simply reply to this email.",
                'cta_text' => 'Create My Student Account', 'cta_path' => '/register',
                'placeholders' => ['StudentName', 'FirstName', 'LeaderName', 'CourseName', 'MemberEmail'],
            ]),
            $auto([
                'key' => 'payment_verified_course', 'name' => 'Payment Verified — Enrolment Confirmed', 'group_label' => 'Enrolment & Payment',
                'trigger_label' => 'Sends when admin verifies a class payment',
                'subject' => 'Your enrolment is confirmed — welcome to [CourseName]',
                'body' => "Dear [StudentName],\n\nWe are pleased to confirm that your payment of [PaymentAmount] for [CourseName] has been verified. Your seat is now secured.\n\nCourse: [CourseName]\nPlan: [PlanName]\nStart Date: [StartDate]\nEnd Date: [EndDate]\nClass Days: [ClassDays]\nClass Time: [ClassTime]\nAssigned Teacher: [TeacherName]\nZoom Meeting ID: [ZoomMeetingID]\n\nBefore your first class, please have ready: a laptop or desktop computer, a stable internet connection, headphones with a working microphone, a webcam, a notebook and pen, and a quiet study space.\n\nPlease join your first class 5–10 minutes early. You can access your schedule and Zoom join link anytime through your student dashboard.\n\n— Payment Receipt —\nInvoice: [InvoiceNumber]\nInvoice Date: [InvoiceDate]\nItem: [ItemName]\nSubtotal: [Subtotal]\nDiscount: [Discount]\nVAT (included in total): [VatAmount]\nTotal Paid: [TotalPaid]\nPayment Method: [PaymentMethod]\n\nPlease keep this email as your official payment receipt.",
                'cta_text' => 'Go to My Class', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'PaymentAmount', 'CourseName', 'PlanName', 'StartDate', 'EndDate', 'ClassDays', 'ClassTime', 'TeacherName', 'ZoomMeetingID', 'InvoiceNumber', 'InvoiceDate', 'ItemName', 'Subtotal', 'Discount', 'VatAmount', 'TotalPaid', 'PaymentMethod'],
            ]),
            $auto([
                'key' => 'payment_not_verified', 'name' => 'Payment Could Not Be Verified', 'group_label' => 'Enrolment & Payment',
                'trigger_label' => 'Sends when admin marks a payment Not Verified',
                'subject' => 'Action required: we could not verify your payment',
                'body' => "Dear [StudentName],\n\nThank you for submitting your payment screenshot. Unfortunately, we were unable to verify your payment of [PaymentAmount] for [ItemName].\n\nPlease log in to your dashboard and re-upload a clearer payment screenshot that shows the amount, date, and transaction reference. If you believe this is an error, please reply to this email with your payment details, and we will assist you.\n\nPlease note: your seat is not booked until the payment is verified.",
                'cta_text' => 'Re-upload Screenshot', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'PaymentAmount', 'ItemName'],
            ]),

            // ═══════════ C. CLASSES & SCHEDULES ═══════════
            $manual([
                'key' => 'class_schedule', 'name' => 'Class Schedule Issued', 'group_label' => 'Classes',
                'when_to_use' => 'Send manually when you publish or change a batch schedule',
                'subject' => 'Your [CourseName] class schedule',
                'body' => "Dear [StudentName],\n\nPlease find below your official class schedule for [CourseName]. Kindly save this schedule for your reference.\n\nStart Date: [StartDate]\nEnd Date: [EndDate]\nClass Days: [ClassDays]\nClass Time: [ClassTime] (NPT)\nAssigned Teacher: [TeacherName]\nZoom Meeting ID: [ZoomMeetingID]\n\nThe full schedule and Zoom link are also available in your dashboard.",
                'cta_text' => 'View Full Schedule', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'CourseName', 'StartDate', 'EndDate', 'ClassDays', 'ClassTime', 'TeacherName', 'ZoomMeetingID'],
            ]),
            $sched([
                'key' => 'class_reminder_day', 'name' => 'Class Reminder — Day Before', 'group_label' => 'Classes',
                'trigger_label' => 'Scheduled: 24 hours before each class (needs server scheduler)',
                'subject' => 'Reminder: your [CourseName] class is tomorrow',
                'body' => "Dear [StudentName],\n\nThis is a friendly reminder that your next [CourseName] class is scheduled for tomorrow at [ClassTime] (NPT).\n\nTeacher: [TeacherName]\n\nPlease ensure your internet connection, camera, and microphone are working ahead of time. Headphones are strongly recommended for clearer audio.",
                'cta_text' => 'Join Zoom Class', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'CourseName', 'ClassTime', 'TeacherName'],
            ]),
            $sched([
                'key' => 'class_reminder_hour', 'name' => 'Class Reminder — 1 Hour Before', 'group_label' => 'Classes',
                'trigger_label' => 'Scheduled: 1 hour before the first class (needs server scheduler)',
                'subject' => 'Your [CourseName] class starts in 1 hour',
                'body' => "Dear [StudentName],\n\nYour [CourseName] class with [TeacherName] starts at [ClassTime] (NPT), in approximately one hour.\n\nPlease join the Zoom session 5 minutes before the scheduled start time to confirm your audio and video.\n\nZoom Meeting ID: [ZoomMeetingID]",
                'cta_text' => 'Join Zoom Now', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'CourseName', 'TeacherName', 'ClassTime', 'ZoomMeetingID'],
            ]),
            $manual([
                'key' => 'class_rescheduled', 'name' => 'Class Rescheduled', 'group_label' => 'Classes',
                'when_to_use' => 'Send manually when a session must be moved',
                'subject' => 'Schedule update: your [CourseName] class on [OriginalDate]',
                'body' => "Dear [StudentName],\n\nWe would like to inform you that your scheduled [CourseName] class on [OriginalDate] at [OriginalTime] (NPT) has been rescheduled.\n\nNew Date: [NewDate]\nNew Time: [NewTime] (NPT)\nReason: [RescheduleReason]\n\nWe apologise for any inconvenience caused. Please update your calendar accordingly.",
                'cta_text' => 'View Updated Schedule', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'CourseName', 'OriginalDate', 'OriginalTime', 'NewDate', 'NewTime', 'RescheduleReason'],
            ]),
            $manual([
                'key' => 'class_cancelled', 'name' => 'Class Cancelled', 'group_label' => 'Classes',
                'when_to_use' => 'Send manually when a session is cancelled',
                'subject' => 'Class cancellation notice: [CourseName] on [ClassDate]',
                'body' => "Dear [StudentName],\n\nWe regret to inform you that your scheduled [CourseName] class on [ClassDate] at [ClassTime] (NPT) has been cancelled due to [CancellationReason].\n\nThe session will be rescheduled to a suitable date. Details of the make-up class will be shared with you shortly.\n\nThank you for your patience and understanding.",
                'cta_text' => 'Go to My Dashboard', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'CourseName', 'ClassDate', 'ClassTime', 'CancellationReason'],
            ]),
            $auto([
                'key' => 'attendance_warning', 'name' => 'Attendance Warning', 'group_label' => 'Classes',
                'trigger_label' => 'Sends when admin records attendance below 80%',
                'subject' => 'Important: your attendance is below the required level',
                'body' => "Dear [StudentName],\n\nWe have noticed that your current attendance in [CourseName] is [AttendancePercent], which is below the 80% requirement for the completion certificate.\n\nAs per our policy, students must maintain at least 80% attendance throughout the course to be eligible for the completion certificate. We encourage you to attend the remaining sessions regularly.\n\nIf you are facing any difficulty attending class, please reply to this email so we can assist you.",
                'cta_text' => 'Go to My Dashboard', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'CourseName', 'AttendancePercent'],
            ]),
            $manual([
                'key' => 'new_material', 'name' => 'New Study Material Available', 'group_label' => 'Classes',
                'when_to_use' => 'For the future Materials feature — send manually for now',
                'subject' => 'New material added for your [CourseName] class',
                'body' => "Dear [StudentName],\n\nNew study material has been shared for your [CourseName] class:\n\nTitle: [MaterialTitle]\nType: [MaterialType]\n\nPlease check your class group or contact your teacher to access the file.",
                'cta_text' => 'Open My Dashboard', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'CourseName', 'MaterialTitle', 'MaterialType'],
            ]),
            $manual([
                'key' => 'course_completion', 'name' => 'Course Completion', 'group_label' => 'Classes',
                'when_to_use' => 'Send manually when a student completes their course',
                'subject' => 'Congratulations — you have completed [CourseName]',
                'body' => "Dear [StudentName],\n\nCongratulations on completing your [CourseName] course at KTM Test Preparation Centre. We are proud of your dedication throughout the programme.\n\nFinal Attendance: [AttendancePercent]\n\nPlease contact our team regarding your completion certificate. We wish you the very best for your IELTS or PTE exam and your study-abroad journey ahead.\n\nIf you would like to continue your preparation with our mock test practice or book your exam through us, please visit your dashboard.",
                'cta_text' => 'Go to My Dashboard', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'CourseName', 'AttendancePercent'],
            ]),
            // Completion certificate — sent 3 days after the course ends to students
            // with 80%+ attendance, with the certificate PDF attached. Enabled so it
            // works as soon as the server cron is running (see FOR-NIRAJ.md).
            [...$sched([
                'key' => 'certificate_ready', 'name' => 'Completion Certificate Issued', 'group_label' => 'Classes',
                'trigger_label' => 'Scheduled: 3 days after the course ends, for 80%+ attendance (needs server scheduler)',
                'subject' => 'Your [CourseName] certificate of attendance, [FirstName]',
                'body' => "Dear [StudentName],\n\nCongratulations on completing your [CourseName] course at KTM Test Preparation Centre! You maintained an attendance of [AttendancePercent], meeting our requirement for the certificate of attendance.\n\nYour certificate is attached to this email as a PDF. You can also download it anytime from your student dashboard.\n\nWe are proud of your dedication and wish you the very best for your exam and your study-abroad journey ahead.",
                'cta_text' => 'Download from Dashboard', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'FirstName', 'CourseName', 'AttendancePercent'],
            ]), 'is_enabled' => true],

            // ═══════════ D. LIVE DEMO ═══════════
            $auto([
                'key' => 'demo_confirmed', 'name' => 'Live Demo Confirmed', 'group_label' => 'Live Demo',
                'trigger_label' => 'Sends when admin approves a demo request',
                'subject' => 'Your free live demo is confirmed — [DemoDate]',
                'body' => "Dear [StudentName],\n\nWe are pleased to confirm your free live demo session at KTM Test Preparation Centre.\n\nTest Interest: [TestName]\nDate: [DemoDate]\nTime: [DemoTime] (NPT)\nTeacher: [TeacherName]\nMode: Online · Zoom\nZoom Meeting ID: [ZoomMeetingID]\n\nThe Zoom join link is available on your dashboard and becomes active shortly before your slot.[AdminNotes]",
                'cta_text' => 'View Demo Details', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'TestName', 'DemoDate', 'DemoTime', 'TeacherName', 'ZoomMeetingID', 'AdminNotes'],
            ]),
            $sched([
                'key' => 'demo_reminder', 'name' => 'Live Demo Reminder', 'group_label' => 'Live Demo',
                'trigger_label' => 'Scheduled: 1 hour before the demo (needs server scheduler)',
                'subject' => 'Reminder: your free live demo starts in 1 hour',
                'body' => "Dear [StudentName],\n\nThis is a reminder that your free live demo session is scheduled for today at [DemoTime] (NPT), in approximately one hour.\n\nTeacher: [TeacherName]\nZoom Meeting ID: [ZoomMeetingID]\n\nPlease join the session 5 minutes early to test your audio and video. Have any questions ready that you would like to ask the teacher.",
                'cta_text' => 'Join Live Demo', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'DemoTime', 'TeacherName', 'ZoomMeetingID'],
            ]),
            // Demo follow-ups — sent 1 hour after the demo by the scheduler. Enabled so
            // they work as soon as the server cron is running (see FOR-NIRAJ.md).
            [...$sched([
                'key' => 'demo_attended', 'name' => 'Live Demo — Attended', 'group_label' => 'Live Demo',
                'trigger_label' => 'Scheduled: 1 hour after the demo, when marked Present (needs server scheduler)',
                'subject' => 'Great to see you at your [TestName] demo, [StudentName]!',
                'body' => "Dear [StudentName],\n\nThank you for attending your free live demo for [TestName]. We hope you enjoyed the session and got a real feel for how our classes work.\n\nReady to keep going? Enrol in a class and continue your preparation with our teachers — just tap the button below.\n\nSee you in class!",
                'cta_text' => 'Enrol in a Class', 'cta_path' => '/batches',
                'placeholders' => ['StudentName', 'TestName'],
            ]), 'is_enabled' => true],
            [...$sched([
                'key' => 'demo_missed', 'name' => 'Live Demo — Missed', 'group_label' => 'Live Demo',
                'trigger_label' => 'Scheduled: 1 hour after the demo, when marked Absent (needs server scheduler)',
                'subject' => 'Sorry we missed you at your [TestName] demo',
                'body' => "Dear [StudentName],\n\nWe noticed you couldn't make it to your free live demo for [TestName]. No problem at all — here's how you can still get started:\n\n• Watch a recorded demo class on our website: [RecordedDemoUrl]\n• Reschedule a new live demo, or ask us anything — simply reply to this email or contact our office.\n• Ready to join? Enrol in a class using the button below.\n\nWe'd love to help you reach your target score.",
                'cta_text' => 'Enrol in a Class', 'cta_path' => '/batches',
                'placeholders' => ['StudentName', 'TestName', 'RecordedDemoUrl'],
            ]), 'is_enabled' => true],
            [...$sched([
                'key' => 'demo_followup', 'name' => 'Live Demo — Follow-up', 'group_label' => 'Live Demo',
                'trigger_label' => 'Scheduled: 1 hour after the demo, when attendance was not marked (needs server scheduler)',
                'subject' => 'How was your [TestName] demo, [StudentName]?',
                'body' => "Dear [StudentName],\n\nWe hope your free live demo for [TestName] went well! We'd love to help you take the next step:\n\n• Enrol in a class to start your preparation (button below).\n• Want another look? Watch a recorded demo on our website: [RecordedDemoUrl]\n• Any questions? Just reply to this email or contact our office.\n\nWe're here to help you succeed.",
                'cta_text' => 'Enrol in a Class', 'cta_path' => '/batches',
                'placeholders' => ['StudentName', 'TestName', 'RecordedDemoUrl'],
            ]), 'is_enabled' => true],

            // ═══════════ E. MOCK TESTS ═══════════
            $auto([
                'key' => 'mock_subscription_confirmed', 'name' => 'Mock Test Subscription Confirmed', 'group_label' => 'Mock Tests',
                'trigger_label' => 'Sends when admin verifies a mock test payment',
                'subject' => 'Your [PlanName] mock test subscription is active',
                'body' => "Dear [StudentName],\n\nThank you for subscribing to our mock test practice. Your subscription is now active.\n\nPlan: [PlanName]\nAmount Paid: [PaymentAmount]\nStart Date: [SubscriptionStart]\nExpiry Date: [SubscriptionEnd]\n\nYour mock test access details will be shared with you by our team. You can check your subscription status anytime from your dashboard.\n\n— Payment Receipt —\nInvoice: [InvoiceNumber]\nInvoice Date: [InvoiceDate]\nItem: [ItemName]\nSubtotal: [Subtotal]\nDiscount: [Discount]\nVAT (included in total): [VatAmount]\nTotal Paid: [TotalPaid]\nPayment Method: [PaymentMethod]\n\nPlease keep this email as your official payment receipt.",
                'cta_text' => 'View My Subscription', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'PlanName', 'PaymentAmount', 'SubscriptionStart', 'SubscriptionEnd', 'InvoiceNumber', 'InvoiceDate', 'ItemName', 'Subtotal', 'Discount', 'VatAmount', 'TotalPaid', 'PaymentMethod'],
            ]),
            $sched([
                'key' => 'mock_renewal_reminder', 'name' => 'Mock Test Renewal Reminder', 'group_label' => 'Mock Tests',
                'trigger_label' => 'Scheduled: 3 days before expiry (needs server scheduler)',
                'subject' => 'Your mock test subscription expires in 3 days',
                'body' => "Dear [StudentName],\n\nYour [PlanName] mock test subscription is set to expire on [SubscriptionEnd]. To avoid any interruption in your practice, please renew your subscription before the expiry date.\n\nYou may also upgrade to a higher plan for more practice tests and detailed feedback.",
                'cta_text' => 'Renew Subscription', 'cta_path' => '/mock-tests',
                'placeholders' => ['StudentName', 'PlanName', 'SubscriptionEnd'],
            ]),
            $sched([
                'key' => 'mock_expired', 'name' => 'Mock Test Subscription Expired', 'group_label' => 'Mock Tests',
                'trigger_label' => 'Scheduled: on expiry day (needs server scheduler)',
                'subject' => 'Your mock test subscription has expired',
                'body' => "Dear [StudentName],\n\nYour [PlanName] mock test subscription expired on [SubscriptionEnd]. Access to new mock tests has been paused.\n\nRenew your subscription anytime from your dashboard to continue practising.",
                'cta_text' => 'Renew Now', 'cta_path' => '/mock-tests',
                'placeholders' => ['StudentName', 'PlanName', 'SubscriptionEnd'],
            ]),

            // ═══════════ F. EXAM BOOKING ═══════════
            $auto([
                'key' => 'exam_booking_received', 'name' => 'Exam Booking Request Received', 'group_label' => 'Exam Booking',
                'trigger_label' => 'Sends when a student submits an exam booking request',
                'subject' => 'Your [TestName] exam booking request has been received',
                'body' => "Dear [StudentName],\n\nThank you for submitting your [TestName] exam booking request through KTM Test Preparation Centre. Our team will review your request and reserve your seat at the official test centre.\n\nTest: [TestName]\nExam Format: [ExamFormat]\nPreferred Date: [ExamDate]\nCentre: [ExamCentre]\n\nYou will receive a confirmation email once the booking is secured at the official test centre. Please ensure your passport details on file are correct and current.",
                'cta_text' => 'View Booking Status', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'TestName', 'ExamFormat', 'ExamDate', 'ExamCentre'],
            ]),
            $auto([
                'key' => 'exam_payment_confirmed', 'name' => 'Exam Booking Payment Confirmed', 'group_label' => 'Exam Booking',
                'trigger_label' => 'Sends when admin verifies an exam booking payment',
                'subject' => 'Payment confirmed for your [TestName] exam booking',
                'body' => "Dear [StudentName],\n\nWe are pleased to confirm that your payment for the [TestName] exam booking support has been verified. Our team will now proceed with your booking and keep you updated.\n\n— Payment Receipt —\nInvoice: [InvoiceNumber]\nInvoice Date: [InvoiceDate]\nItem: [ItemName]\nSubtotal: [Subtotal]\nDiscount: [Discount]\nVAT (included in total): [VatAmount]\nTotal Paid: [TotalPaid]\nPayment Method: [PaymentMethod]\n\nPlease keep this email as your official payment receipt.",
                'cta_text' => 'View My Booking', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'TestName', 'InvoiceNumber', 'InvoiceDate', 'ItemName', 'Subtotal', 'Discount', 'VatAmount', 'TotalPaid', 'PaymentMethod'],
            ]),
            $auto([
                'key' => 'exam_booking_confirmed', 'name' => 'Exam Booking Confirmed', 'group_label' => 'Exam Booking',
                'trigger_label' => 'Sends when admin marks a booking as Booked',
                'subject' => 'Confirmed: your [TestName] exam is booked',
                'body' => "Dear [StudentName],\n\nWe are pleased to confirm that your [TestName] exam booking has been successfully secured.\n\nTest: [TestName]\nPreferred Date: [ExamDate]\nCentre: [ExamCentre]\n\nImportant: the official test provider has sent you an email. Please verify your exam schedule details in that email or through your official test provider login. Carry the same valid ID used during booking on the exam day. Once confirmed, the booking fee is non-refundable and non-transferable as per the official test provider's policy.\n\nWe wish you the very best for your exam.",
                'cta_text' => 'View My Booking', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'TestName', 'ExamDate', 'ExamCentre'],
            ]),

            // ═══════════ G. SUPPORT ═══════════
            $auto([
                'key' => 'support_received', 'name' => 'Support Request Received', 'group_label' => 'Support',
                'trigger_label' => 'Sends when a support/contact message is submitted',
                'subject' => 'Support request [TicketID] received',
                'body' => "Dear [StudentName],\n\nThank you for contacting KTM Test Preparation Centre support. We have received your request and a member of our team will respond within a reasonable time.\n\nReference: [TicketID]\nSubject: [TicketSubject]\n\nYou can track the status of your request anytime from your dashboard. Please keep this reference for your records.",
                'cta_text' => 'View My Requests', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'TicketID', 'TicketSubject'],
            ]),
            $auto([
                'key' => 'support_reply', 'name' => 'Support Reply', 'group_label' => 'Support',
                'trigger_label' => 'Sends when admin replies to a support request',
                'subject' => 'New reply on your support request [TicketID]',
                'body' => "Dear [StudentName],\n\nOur support team has replied to your request [TicketID].\n\nSubject: [TicketSubject]\nReply: [ReplyPreview]\n\nPlease log in to your dashboard to view the full reply, or simply respond to this email if you have further questions.",
                'cta_text' => 'View Reply', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'TicketID', 'TicketSubject', 'ReplyPreview'],
            ]),
            $auto([
                'key' => 'support_resolved', 'name' => 'Support Request Resolved', 'group_label' => 'Support',
                'trigger_label' => 'Sends when admin marks a request Resolved',
                'subject' => 'Your support request [TicketID] has been resolved',
                'body' => "Dear [StudentName],\n\nYour support request [TicketID] regarding '[TicketSubject]' has been marked as resolved by our team.\n\nIf your concern is fully addressed, no further action is needed. If you feel the issue is not resolved, simply reply to this email and we will look into it again.\n\nThank you for choosing KTM Test Preparation Centre.",
                'cta_text' => 'Go to My Dashboard', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'TicketID', 'TicketSubject'],
            ]),

            // ═══════════ H. REFUNDS ═══════════
            $manual([
                'key' => 'refund_received', 'name' => 'Refund Request Received', 'group_label' => 'Refunds',
                'when_to_use' => 'Send manually when a student requests a refund',
                'subject' => 'Your refund request has been received',
                'body' => "Dear [StudentName],\n\nWe have received your refund request and our team will review it in line with our Refund Policy.\n\nCourse / Service: [ItemName]\nAmount Paid: [PaymentAmount]\n\nYou will receive an update once the review is complete, typically within 5 to 7 working days. Please note that exam booking fees are non-refundable once confirmed, as per the official test provider's policy.",
                'cta_text' => 'Go to My Dashboard', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'ItemName', 'PaymentAmount'],
            ]),
            $auto([
                'key' => 'refund_approved', 'name' => 'Refund Approved', 'group_label' => 'Refunds',
                'trigger_label' => 'Sends when admin processes a refund',
                'subject' => 'Your refund has been approved',
                'body' => "Dear [StudentName],\n\nYour refund request for [ItemName] has been approved.\n\nApproved Refund Amount: [RefundAmount]\nReason: [RefundReason]\nExpected Processing Time: 5–7 working days\n\nThe amount will be returned to your original payment source. For international refunds, the amount may be reduced by bank charges as per our Refund Policy.",
                'cta_text' => 'View Refund Details', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'ItemName', 'RefundAmount', 'RefundReason'],
            ]),
            $auto([
                'key' => 'refund_declined', 'name' => 'Refund Request Declined', 'group_label' => 'Refunds',
                'trigger_label' => 'Sends when admin sets Refund Not Approved',
                'subject' => 'Update on your refund request',
                'body' => "Dear [StudentName],\n\nThank you for your patience. After reviewing your refund request for [ItemName], we regret to inform you that we are unable to process the refund.\n\nThis decision is in line with our published Refund Policy, available on our website. If you believe this decision should be reconsidered, please reply to this email with any supporting details.",
                'cta_text' => 'Read Refund Policy', 'cta_path' => '/refund-policy',
                'placeholders' => ['StudentName', 'ItemName'],
            ]),

            // ═══════════ I. NOTICES ═══════════
            $manual([
                'key' => 'important_notice', 'name' => 'Important Notice (Generic)', 'group_label' => 'Notices',
                'when_to_use' => 'Copy and send for any general announcement',
                'subject' => 'Important notice from KTM Test Preparation Centre',
                'body' => "Dear [StudentName],\n\nWe would like to bring the following important update to your attention:\n\n[NoticeTitle]\n\n[NoticeBody]\n\nIf you have any questions or concerns, please reply to this email or raise a request through your dashboard.",
                'cta_text' => 'Open Dashboard', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'NoticeTitle', 'NoticeBody'],
            ]),
            $manual([
                'key' => 'schedule_lock_reminder', 'name' => 'Schedule Lock Reminder', 'group_label' => 'Notices',
                'when_to_use' => 'Send manually before confirming a class/exam/demo schedule',
                'subject' => 'Please confirm your preferred date and time carefully',
                'body' => "Dear [StudentName],\n\nThis is a friendly reminder regarding your recent request for [RequestType].\n\nOnce the admin confirms your class, exam, or demo class schedule, it cannot be changed. Please review your preferred date and time carefully before submitting your request.\n\nIf any of the details need correction, please update your request before our team confirms it.",
                'cta_text' => 'Review My Request', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'RequestType'],
            ]),

            // ═══════════ J. PROMOTIONAL (manual — needs unsubscribe/marketing tool) ═══════════
            $manual([
                'key' => 'promo_exam_booking', 'name' => 'Promo — Exam Booking Discount', 'group_label' => 'Marketing',
                'when_to_use' => 'Marketing campaign for exam booking discounts',
                'subject' => 'Save on your IELTS or PTE exam booking',
                'body' => "Dear [StudentName],\n\nFor a limited time, KTM Test Preparation Centre is offering a promotional discount on IELTS and PTE Academic exam bookings made through our team.\n\nOffer Valid Until: [OfferExpiry]\n\nWe will handle your full booking process — date selection, passport details, payment, and seat confirmation — at no extra service fee. The current promotional discount is applied automatically when you book through us.",
                'cta_text' => 'Book My Exam Now', 'cta_path' => '/exam-booking',
                'placeholders' => ['StudentName', 'OfferExpiry'],
            ]),
            $manual([
                'key' => 'promo_class_plan', 'name' => 'Promo — Class Plan Offer', 'group_label' => 'Marketing',
                'when_to_use' => 'Marketing campaign for class enrolment offers',
                'subject' => 'Limited-time offer on our [CourseName] class plans',
                'body' => "Dear [StudentName],\n\nWe are pleased to announce a limited-time offer on our [CourseName] class plans for new students enrolling in the upcoming batch.\n\nOffer: [OfferDetails]\nValid Until: [OfferExpiry]\n\nOur plans include live teacher support, real computer-based practice, full-length mock tests, and step-by-step exam booking support — for students in Nepal and Nepalese students living abroad.",
                'cta_text' => 'View Class Plans', 'cta_path' => '/batches',
                'placeholders' => ['StudentName', 'CourseName', 'OfferDetails', 'OfferExpiry'],
            ]),
            $manual([
                'key' => 'newsletter_tips', 'name' => 'Monthly Tips Newsletter', 'group_label' => 'Marketing',
                'when_to_use' => 'Monthly IELTS/PTE tips (opt-in list)',
                'subject' => '[Month] tips to boost your IELTS / PTE score',
                'body' => "Dear [StudentName],\n\nHere is your monthly digest of IELTS and PTE preparation tips from KTM Test Preparation Centre.\n\n1. [TipOneTitle] — [TipOneSummary]\n2. [TipTwoTitle] — [TipTwoSummary]\n3. [TipThreeTitle] — [TipThreeSummary]\n\nRead the full articles and download the practice worksheets on our website.",
                'cta_text' => 'Read Full Newsletter', 'cta_path' => '/',
                'placeholders' => ['StudentName', 'Month', 'TipOneTitle', 'TipOneSummary', 'TipTwoTitle', 'TipTwoSummary', 'TipThreeTitle', 'TipThreeSummary'],
            ]),
            $sched([
                'key' => 'reengagement', 'name' => 'Re-engagement (Dormant Student)', 'group_label' => 'Marketing',
                'trigger_label' => 'Scheduled: 30+ days inactive (needs server scheduler)',
                'subject' => 'We miss you at KTM Test Preparation Centre, [FirstName]',
                'body' => "Dear [StudentName],\n\nWe noticed you have not visited your KTM Test Preparation Centre dashboard for a while. Your IELTS / PTE goal is still within reach — and we are here to help you get there.\n\nWhether you want to join a class, practise with mock tests, or book your exam, everything is ready in your dashboard.\n\nIf you have any questions, simply reply to this email.",
                'cta_text' => 'Continue My Preparation', 'cta_path' => '/my-account',
                'placeholders' => ['StudentName', 'FirstName'],
            ]),

            // ═══════════ WHATSAPP QUICK TEMPLATES (migrated from the old page) ═══════════
            $wa(['key' => 'wa_welcome_lead', 'name' => 'Welcome Lead', 'group_label' => 'WhatsApp', 'when_to_use' => 'New IELTS/PTE enquiry',
                'body' => 'Namaste! KTM Test Preparation Class ma welcome. IELTS/PTE ko barema help garnu parcha?']),
            $wa(['key' => 'wa_ask_details', 'name' => 'Ask Missing Details', 'group_label' => 'WhatsApp', 'when_to_use' => 'Interested student',
                'body' => 'Please send your full name, course, plan, preferred class time, and target score.']),
            $wa(['key' => 'wa_payment_qr', 'name' => 'Payment QR', 'group_label' => 'WhatsApp', 'when_to_use' => 'Student wants to pay',
                'body' => 'Sure. Please send your full name, course, plan, and preferred time. Ma official QR/payment details pathaidinchhu. Payment garepachi screenshot pathaidinu hola.']),
            $wa(['key' => 'wa_screenshot_received', 'name' => 'Screenshot Received', 'group_label' => 'WhatsApp', 'when_to_use' => 'Student sends screenshot',
                'body' => 'Thank you. I will forward this to admin for verification. Admin le verify गरेपछि seat confirm huncha.']),
            $wa(['key' => 'wa_payment_verified', 'name' => 'Payment Verified', 'group_label' => 'WhatsApp', 'when_to_use' => 'Admin verifies payment',
                'body' => 'Your payment is verified and seat is confirmed. We will send your class details shortly.']),
            $wa(['key' => 'wa_teacher_notification', 'name' => 'Teacher Notification', 'group_label' => 'WhatsApp', 'when_to_use' => 'Payment verified/enrolled',
                'body' => 'New student enrolled. Please check CRM for name, course, plan, time, and contact details.']),
            $wa(['key' => 'wa_reminder', 'name' => 'Reminder', 'group_label' => 'WhatsApp', 'when_to_use' => 'Pending docs/payment/info',
                'body' => 'Gentle reminder: your document/payment/information is still pending. Please send it when possible.']),
            $wa(['key' => 'wa_reminder_stop', 'name' => 'Reminder Stop (Internal)', 'group_label' => 'WhatsApp', 'when_to_use' => 'No response after 3 reminders',
                'body' => 'No response after 3 reminders. Follow-up stopped in CRM.']),
            $wa(['key' => 'wa_exam_booking', 'name' => 'Exam Booking Details', 'group_label' => 'WhatsApp', 'when_to_use' => 'Exam booking enquiry',
                'body' => 'For IELTS/PTE exam booking, please send passport copy, test type, passport name/number, DOB, email, preferred date/time/centre. For PTE, send login details if available.']),
            $wa(['key' => 'wa_mock_subscription', 'name' => 'Mock Subscription', 'group_label' => 'WhatsApp', 'when_to_use' => 'Mock test package enquiry',
                'body' => 'Mock test practice packages start from NPR 1,200 per month. After payment verification, access details will be sent to your email.']),
            $wa(['key' => 'wa_abroad_enquiry', 'name' => 'Abroad Study Enquiry', 'group_label' => 'WhatsApp', 'when_to_use' => 'Abroad study question',
                'body' => 'For abroad study enquiries, please contact KTM Educational Consultancy office or call +977 14526263.']),
        ];
    }
}
