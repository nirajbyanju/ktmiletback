<?php

namespace Database\Seeders;

use App\Models\Offer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class OfferSeeder extends Seeder
{
    public function run(): void
    {
        $offers = [
            // 1. IELTS course enrollment offer  → shown on /ielts only
            [
                'title'                 => 'NPR 500 Off — IELTS Smart Batch',
                'description'           => 'Enroll in the IELTS Academic Smart Batch and get NPR 500 off your course fee. Claim now and the discount is applied automatically when you generate your invoice.',
                'start_date'            => Carbon::today()->toDateString(),
                'valid_date'            => Carbon::today()->addDays(30)->toDateString(),
                'claim_discount_amount' => 500.00,
                'status'                => 'active',
                'badge'                 => 'IELTS',
                'cta_text'              => 'Claim NPR 500 Off',
                'cta_url'               => '/ielts#plans',
                'sort_order'            => 1,
                'applicable_type'       => Offer::APPLICABLE_COURSE,
            ],

            // 2. PTE course enrollment offer  → shown on /pte only
            [
                'title'                 => 'NPR 500 Off — PTE Smart Batch',
                'description'           => 'Enroll in the PTE Academic Smart Batch and get NPR 500 off your course fee. Claim now and the discount is applied automatically when you generate your invoice.',
                'start_date'            => Carbon::today()->toDateString(),
                'valid_date'            => Carbon::today()->addDays(30)->toDateString(),
                'claim_discount_amount' => 500.00,
                'status'                => 'active',
                'badge'                 => 'PTE',
                'cta_text'              => 'Claim NPR 500 Off',
                'cta_url'               => '/pte#plans',
                'sort_order'            => 2,
                'applicable_type'       => Offer::APPLICABLE_COURSE,
            ],

            // 3. Mock test subscription offer  → shown on /mock-tests only
            [
                'title'                 => 'NPR 200 Off — Mock Test Subscription',
                'description'           => 'Subscribe to any Alfa IELTS or PTE mock test plan and save NPR 200. Claim before subscribing — applied automatically to your invoice.',
                'start_date'            => Carbon::today()->toDateString(),
                'valid_date'            => Carbon::today()->addDays(30)->toDateString(),
                'claim_discount_amount' => 200.00,
                'status'                => 'active',
                'badge'                 => 'Mock Test',
                'cta_text'              => 'Claim NPR 200 Off',
                'cta_url'               => '/mock-tests',
                'sort_order'            => 3,
                'applicable_type'       => Offer::APPLICABLE_MOCK_TEST,
            ],

            // 4. Exam booking offer  → shown on /exam-booking only
            [
                'title'                 => 'Save NPR 2,000 on Exam Booking',
                'description'           => 'Book your IELTS or PTE exam through KTM Test Prep and save NPR 2,000 off the official registration fee. Our team handles date, centre, passport details, payment, and confirmation.',
                'start_date'            => Carbon::today()->toDateString(),
                'valid_date'            => Carbon::today()->addDays(30)->toDateString(),
                'claim_discount_amount' => 2000.00,
                'status'                => 'active',
                'badge'                 => 'Exam Booking',
                'cta_text'              => 'Claim NPR 2,000 Off',
                'cta_url'               => '/exam-booking',
                'sort_order'            => 4,
                'applicable_type'       => Offer::APPLICABLE_BOOKING,
            ],
        ];

        foreach ($offers as $offerData) {
            Offer::updateOrCreate(
                ['title' => $offerData['title']],
                $offerData
            );
        }

        $this->command->info('Seeded ' . count($offers) . ' offer(s).');
    }
}
