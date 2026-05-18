<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ContactMessageSeeder extends Seeder
{
    public function run(): void
    {
        $messages = [
            [
                'full_name'       => 'Priya Shrestha',
                'email'           => 'priya.shrestha@gmail.com',
                'whatsapp_number' => '+9779812345601',
                'subject'         => 'IELTS Course Inquiry',
                'message'         => 'Hello, I would like to know more about the IELTS Academic preparation course. What is the fee structure and when does the next batch start?',
                'status'          => 'replied',
                'admin_notes'     => 'Responded with batch schedule and fee details via email.',
            ],
            [
                'full_name'       => 'Rohan Adhikari',
                'email'           => 'rohan.adhikari@yahoo.com',
                'whatsapp_number' => '+9779812345602',
                'subject'         => 'PTE Preparation Classes',
                'message'         => 'I am planning to appear for PTE Academic in the next 2 months. Do you have any crash course or intensive program available?',
                'status'          => 'in_progress',
                'admin_notes'     => 'Referred to Smart Batch — waiting for confirmation.',
            ],
            [
                'full_name'       => 'Sunita Karmacharya',
                'email'           => 'sunita.k@outlook.com',
                'whatsapp_number' => '+9779812345603',
                'subject'         => 'Exam Booking Help',
                'message'         => 'I need help booking my IELTS exam slot at British Council. Can you assist me with the process? I have my passport ready.',
                'status'          => 'read',
                'admin_notes'     => null,
            ],
            [
                'full_name'       => 'Rajesh Pandey',
                'email'           => 'rajesh.pandey@gmail.com',
                'whatsapp_number' => '+9779812345604',
                'subject'         => 'Score Guarantee Query',
                'message'         => 'What band score can I expect after completing your IELTS course? Do you offer any guarantee or money-back policy?',
                'status'          => 'new',
                'admin_notes'     => null,
            ],
            [
                'full_name'       => 'Anita Tamang',
                'email'           => 'anita.tamang@gmail.com',
                'whatsapp_number' => '+9779812345605',
                'subject'         => 'Group Discount',
                'message'         => 'We are a group of 5 friends who want to enroll together. Is there any group discount available for the Smart Batch?',
                'status'          => 'replied',
                'admin_notes'     => 'Offered 10% group discount. All 5 enrolled in the next Smart Batch.',
            ],
            [
                'full_name'       => 'Dipesh Thapa',
                'email'           => 'dipesh.thapa@hotmail.com',
                'whatsapp_number' => '+9779812345606',
                'subject'         => 'Schedule & Timing',
                'message'         => 'I am working full time. Do you have evening or weekend batches available? What are the class timings?',
                'status'          => 'in_progress',
                'admin_notes'     => 'Shared evening batch details. Awaiting decision.',
            ],
            [
                'full_name'       => 'Sita Rai',
                'email'           => 'sita.rai@gmail.com',
                'whatsapp_number' => '+9779812345607',
                'subject'         => 'Online Class Details',
                'message'         => 'I am currently living in Japan. Can I join your online classes? What platform do you use and what are the timings?',
                'status'          => 'replied',
                'admin_notes'     => 'Enrolled in Global Flex Batch. Zoom link shared.',
            ],
            [
                'full_name'       => 'Bikash Gurung',
                'email'           => 'bikash.gurung@gmail.com',
                'whatsapp_number' => '+9779812345608',
                'subject'         => 'Mock Test Availability',
                'message'         => 'Do you provide full-length mock tests as part of the course? I have my exam in 3 weeks and need practice.',
                'status'          => 'new',
                'admin_notes'     => null,
            ],
            [
                'full_name'       => 'Kavita Basnet',
                'email'           => 'kavita.basnet@gmail.com',
                'whatsapp_number' => '+9779812345609',
                'subject'         => 'Writing & Speaking Feedback',
                'message'         => 'I want to improve specifically in Writing Task 2 and Speaking. Does your course include individual feedback sessions?',
                'status'          => 'read',
                'admin_notes'     => 'Forwarded to academic team for detailed reply.',
            ],
            [
                'full_name'       => 'Nabin Pokhrel',
                'email'           => 'nabin.pokhrel@gmail.com',
                'whatsapp_number' => '+9779812345610',
                'subject'         => 'Refund Policy',
                'message'         => 'I had enrolled last month but due to a medical emergency I could not attend. Is there any refund or course transfer option?',
                'status'          => 'in_progress',
                'admin_notes'     => 'Escalated to management for refund review.',
            ],
        ];

        foreach ($messages as $i => $msg) {
            ContactMessage::updateOrCreate(
                ['email' => $msg['email'], 'subject' => $msg['subject']],
                [
                    'full_name'       => $msg['full_name'],
                    'whatsapp_number' => $msg['whatsapp_number'],
                    'message'         => $msg['message'],
                    'status'          => $msg['status'],
                    'admin_notes'     => $msg['admin_notes'],
                    'ip_address'      => '192.168.1.' . ($i + 10),
                    'read_at'         => in_array($msg['status'], ['read', 'replied', 'in_progress'])
                        ? Carbon::now()->subDays(rand(1, 15))
                        : null,
                    'created_at'      => Carbon::now()->subDays(rand(1, 30)),
                ]
            );
        }

        $this->command->info('Seeded ' . count($messages) . ' contact messages.');
    }
}
