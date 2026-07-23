<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-editable FAQ (Website Content page). Seeded with the exact
        // questions previously hardcoded in the frontend FAQ page.
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('group_title');
            $table->string('question', 500);
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $faqs = [
            ['group_title' => 'Most Asked Questions', 'question' => 'Do you offer both online and physical classes?', 'answer' => 'Yes. Our main focus is online classes, but physical classes are also available at our Putalisadak centre in Kathmandu. Choose whichever suits you.', 'sort_order' => 1],
            ['group_title' => 'Most Asked Questions', 'question' => 'Can I join from outside Nepal?', 'answer' => 'Yes. Our Global Flex Batch is built for Nepalese students living abroad. We schedule classes to match your time zone in the UK, Australia, Canada, Japan, Korea, the Gulf, and other countries.', 'sort_order' => 2],
            ['group_title' => 'Most Asked Questions', 'question' => 'How much does the class cost?', 'answer' => 'Group plans start from NPR 2,199 per student. Our most popular Premium Focus plan is NPR 5,999. Private 1:1 coaching is NPR 30,000. See the full price list on the IELTS or PTE class pages.', 'sort_order' => 3],
            ['group_title' => 'Most Asked Questions', 'question' => 'Do you offer a free demo class?', 'answer' => 'Yes. You can watch our 2-hour recorded demo class for free, or book a 30-minute live demo with one of our teachers â no payment, no commitment.', 'sort_order' => 4],
            ['group_title' => 'Most Asked Questions', 'question' => 'Do you help with IELTS or PTE exam booking?', 'answer' => 'Yes. Our team books your IELTS or PTE Academic test for you, step by step. You also save on your booking through our current promotional offer (NPR 2,000 off IELTS Â· NPR 3,000 off PTE).', 'sort_order' => 5],
            ['group_title' => 'Most Asked Questions', 'question' => 'What is the difference between IELTS and PTE?', 'answer' => 'Both test your English for study or migration abroad. IELTS has a face-to-face speaking test and uses a 1â9 band scale. PTE is 100% computer-based with AI scoring on a 10â90 scale. See our IELTS vs PTE comparison guide for more details.', 'sort_order' => 6],
            ['group_title' => 'Most Asked Questions', 'question' => 'How long does it take to prepare for IELTS or PTE?', 'answer' => 'Most students need 4â8 weeks of focused preparation. The exact time depends on your current English level and target band/score. Our Smart Batch and Premium Focus plans both run for 6 weeks (30 teaching hours).', 'sort_order' => 7],
            ['group_title' => 'Most Asked Questions', 'question' => 'Do you guarantee a specific IELTS or PTE score?', 'answer' => 'No. No honest institute can guarantee a score â your final result depends on your effort, attendance, and exam performance. What we can promise is good teaching, regular practice, and full support.', 'sort_order' => 8],
            ['group_title' => 'About the Classes', 'question' => 'What is included in the IELTS / PTE class?', 'answer' => 'Module-wise lessons, practice tasks, mock tests, teacher guidance, written feedback, exam preparation strategies, and WhatsApp / email support.', 'sort_order' => 9],
            ['group_title' => 'About the Classes', 'question' => 'Do you teach all IELTS modules?', 'answer' => 'Yes â Listening, Reading, Writing, and Speaking.', 'sort_order' => 10],
            ['group_title' => 'About the Classes', 'question' => 'Do you teach all PTE modules?', 'answer' => 'Yes â Speaking & Writing, Reading, and Listening, including all 20 PTE task types.', 'sort_order' => 11],
            ['group_title' => 'About the Classes', 'question' => 'Can beginners join the class?', 'answer' => 'Yes. We welcome beginners. Our teachers guide each student based on their current level and target score.', 'sort_order' => 12],
            ['group_title' => 'About the Classes', 'question' => 'Do you provide study materials?', 'answer' => 'Yes. Practice materials, class notes, and exam-specific guidance are provided according to your chosen plan.', 'sort_order' => 13],
            ['group_title' => 'About the Classes', 'question' => 'Do you provide recorded classes?', 'answer' => 'Recorded class access may be available depending on the plan. As per our policy, students can access recordings for up to 3 missed classes during the course.', 'sort_order' => 14],
            ['group_title' => 'About the Classes', 'question' => 'What happens if I miss a class?', 'answer' => 'Inform us as early as possible. We may provide recorded access for up to 3 missed classes. Missed group classes are not automatically replaced unless we offer a make-up option.', 'sort_order' => 15],
            ['group_title' => 'About the Classes', 'question' => 'Can I change my class time after joining?', 'answer' => 'Class time changes depend on seat availability and admin approval. Contact our support team if you need a change.', 'sort_order' => 16],
            ['group_title' => 'About the Classes', 'question' => 'How can I enrol?', 'answer' => 'Choose your class type and plan, fill in the registration form, make payment, and you\'ll receive confirmation by email and WhatsApp.', 'sort_order' => 17],
            ['group_title' => 'Online Class', 'question' => 'How do online classes work?', 'answer' => 'Classes run on Zoom. You need a stable internet connection, a laptop or mobile, headphones, and a quiet place to study.', 'sort_order' => 18],
            ['group_title' => 'Online Class', 'question' => 'Can I join online class from mobile?', 'answer' => 'Yes, but a laptop or desktop is much better for writing practice, reading tasks, and mock tests.', 'sort_order' => 19],
            ['group_title' => 'Online Class', 'question' => 'Do I need headphones for online class?', 'answer' => 'Yes. Headphones give you clearer listening practice, better speaking practice, and reduce distractions.', 'sort_order' => 20],
            ['group_title' => 'Online Class', 'question' => 'Will I get teacher support in online class?', 'answer' => 'Yes â live teacher support during class, plus follow-up help via WhatsApp and email according to your plan.', 'sort_order' => 21],
            ['group_title' => 'Physical Class', 'question' => 'What facilities are available in physical class?', 'answer' => 'Individual computers with headphones, computer-based IELTS/PTE practice, smart projector teaching, mock test setup, and direct teacher support.', 'sort_order' => 22],
            ['group_title' => 'Physical Class', 'question' => 'Do I need to bring my own laptop?', 'answer' => 'No. Our centre provides computers and headphones for each student during the class.', 'sort_order' => 23],
            ['group_title' => 'Physical Class', 'question' => 'Where is the physical class located?', 'answer' => 'Putalisadak, Way to Dillibazar, Kathmandu, Nepal. Full address and directions are on our About Us page.', 'sort_order' => 24],
            ['group_title' => 'IELTS / PTE Exam Booking', 'question' => 'Do you help with IELTS and PTE exam booking?', 'answer' => 'Yes. We handle the full booking process â test selection, date, payment, and confirmation.', 'sort_order' => 25],
            ['group_title' => 'IELTS / PTE Exam Booking', 'question' => 'Is the exam fee included in the class fee?', 'answer' => 'No. The official exam fee is paid separately to the test provider (British Council, IDP, or Pearson), unless clearly stated in a special offer.', 'sort_order' => 26],
            ['group_title' => 'IELTS / PTE Exam Booking', 'question' => 'What details are required for exam booking?', 'answer' => 'Your full name (as on passport), date of birth, passport/ID details, test type, preferred date and centre, and payment confirmation.', 'sort_order' => 27],
            ['group_title' => 'IELTS / PTE Exam Booking', 'question' => 'Which ID is required for the IELTS exam?', 'answer' => 'Bring the same valid ID used during booking. IELTS staff check the ID on test day. Invalid or expired IDs may not be accepted.', 'sort_order' => 28],
            ['group_title' => 'IELTS / PTE Exam Booking', 'question' => 'Which ID is required for the PTE exam?', 'answer' => 'The ID you show on test day must match the ID selected during booking, and it must be a physical ID. If it doesn\'t meet requirements, you may not be allowed to sit the test.', 'sort_order' => 29],
            ['group_title' => 'IELTS / PTE Exam Booking', 'question' => 'Can I choose my IELTS Speaking test slot?', 'answer' => 'In Nepal, British Council allows students to choose their IELTS Speaking slot for both computer-based and paper-based IELTS, subject to availability.', 'sort_order' => 30],
            ['group_title' => 'IELTS / PTE Exam Booking', 'question' => 'Can I change my IELTS test date?', 'answer' => 'IELTS test transfer depends on the test centre\'s policy, availability, and timing of your request. Administration fees may apply. Always check the latest policy before booking.', 'sort_order' => 31],
            ['group_title' => 'IELTS / PTE Exam Booking', 'question' => 'Can I cancel my IELTS exam booking?', 'answer' => 'Cancellation and refund rules depend on when you cancel and the official test centre\'s policy. In Nepal, British Council has different refund conditions depending on timing and reason.', 'sort_order' => 32],
            ['group_title' => 'IELTS / PTE Exam Booking', 'question' => 'Can I reschedule or cancel my PTE test?', 'answer' => 'Yes. You can usually cancel or reschedule through your myPTE account. Free reschedule/cancellation is usually possible if there are at least 14 full calendar days before the test.', 'sort_order' => 33],
            ['group_title' => 'IELTS / PTE Exam Booking', 'question' => 'What is the PTE refund policy?', 'answer' => 'Pearson states: cancellation more than 14 days before the test may receive a 100% refund; cancellation 8â14 days before may receive a 50% refund; cancellation 7 days or fewer before the test may receive no refund.', 'sort_order' => 34],
            ['group_title' => 'IELTS / PTE Exam Booking', 'question' => 'Are IELTS / PTE booking fees refundable through KTM Test Prep?', 'answer' => 'Once we confirm your booking on your behalf, the booking fee is non-refundable and non-transferable, in line with the official test provider\'s policy.', 'sort_order' => 35],
            ['group_title' => 'IELTS / PTE Exam Booking', 'question' => 'How will I know my booking is confirmed?', 'answer' => 'Confirmation details will be sent by email and WhatsApp, and shown in your student dashboard.', 'sort_order' => 36],
            ['group_title' => 'Mock Test Practice', 'question' => 'What is a mock test subscription?', 'answer' => 'A monthly plan that gives you exam-style practice tests, module-wise practice, and progress tracking.', 'sort_order' => 37],
            ['group_title' => 'Mock Test Practice', 'question' => 'Is mock practice available for both IELTS and PTE?', 'answer' => 'Yes. Choose IELTS or PTE practice based on your selected plan.', 'sort_order' => 38],
            ['group_title' => 'Mock Test Practice', 'question' => 'Can I buy only mock test practice without joining the class?', 'answer' => 'Yes. The mock test subscription is a standalone service â no class enrolment needed.', 'sort_order' => 39],
            ['group_title' => 'Mock Test Practice', 'question' => 'How long is the subscription active?', 'answer' => 'Each subscription is monthly. Renew or upgrade anytime from your student dashboard.', 'sort_order' => 40],
            ['group_title' => 'Mock Test Practice', 'question' => 'Can I share my subscription with others?', 'answer' => 'No. The subscription is for the registered student only and cannot be shared.', 'sort_order' => 41],
            ['group_title' => 'Mock Test Practice', 'question' => 'Is AI Practice Support available?', 'answer' => 'AI Practice Support is planned for the future. When it launches, Premium subscribers will get it at no extra cost.', 'sort_order' => 42],
            ['group_title' => 'Payment & Support', 'question' => 'How can I make payment?', 'answer' => 'Through eSewa, Fonepay, bank transfer (NPR), or international card. Full options are shown in your student dashboard.', 'sort_order' => 43],
            ['group_title' => 'Payment & Support', 'question' => 'What should I do after payment?', 'answer' => 'Upload your payment screenshot in your dashboard. Our team will verify and send confirmation.', 'sort_order' => 44],
            ['group_title' => 'Payment & Support', 'question' => 'When will my service be activated?', 'answer' => 'Right after payment verification â usually within a few hours during working days.', 'sort_order' => 45],
            ['group_title' => 'Payment & Support', 'question' => 'How will I receive confirmation?', 'answer' => 'By email and WhatsApp. You can also check your dashboard for all updated details.', 'sort_order' => 46],
            ['group_title' => 'Payment & Support', 'question' => 'What if my payment fails?', 'answer' => 'Message our support team immediately with your payment details, screenshot, and transaction reference. We\'ll resolve it quickly.', 'sort_order' => 47],
            ['group_title' => 'Payment & Support', 'question' => 'Can I contact support before choosing a plan?', 'answer' => 'Absolutely. WhatsApp or email us â we\'ll help you choose the right class plan, exam booking option, or mock test subscription based on your goal.', 'sort_order' => 48],
            ['group_title' => 'Payment & Support', 'question' => 'Can I pay in instalments?', 'answer' => 'Currently full payment is required before class access. For special cases, contact our team and we\'ll see what we can do.', 'sort_order' => 49],
        ];
        foreach ($faqs as $f) {
            DB::table('faqs')->insert([...$f, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        // Site-content settings, seeded with the exact values currently
        // hardcoded in the frontend (data/ktm.ts + homepage hero) so nothing
        // changes visually until Bimal edits them.
        $content = [
            'announcement_enabled' => '0',
            'announcement_text' => '',
            'announcement_expires_at' => '',
            'contact_phone' => '+977 14526263',
            'contact_mobile' => '+977 9747469800',
            'contact_email' => 'ktmtestpreparation@ktmeducational.edu.np',
            'contact_address' => 'Putalisadak (Way to Dillibazar), Kathmandu, Nepal',
            'contact_hours' => '8:00 AM to 5:00 PM (Sunday-Friday)',
            'hero_badge' => 'Now enrolling — new batches start every week',
            'hero_title' => 'Online IELTS & PTE Classes for Nepalese Students - In Nepal and Abroad',
            'hero_subtitle' => 'Prepare for your IELTS or PTE Academic test with live online classes, expert teachers, real computer-based practice, mock tests, and full exam booking support. Join from Kathmandu, the UK, Australia, Canada, Japan, the Gulf, or anywhere in the world.',
            'demo_video_ielts' => '',
            'demo_video_pte' => '',
        ];
        foreach ($content as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        DB::table('settings')->whereIn('key', [
            'announcement_enabled', 'announcement_text', 'announcement_expires_at',
            'contact_phone', 'contact_mobile', 'contact_email', 'contact_address', 'contact_hours',
            'hero_badge', 'hero_title', 'hero_subtitle', 'demo_video_ielts', 'demo_video_pte',
        ])->delete();
    }
};
