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
            [
                'title'                 => 'Save NPR 2,000 on IELTS Exam Booking',
                'description'           => 'Book your IELTS Academic or General Training exam through KTM Constancy and get NPR 2,000 off the official registration fee. Limited slots available.',
                'start_date'            => Carbon::today()->toDateString(),
                'valid_date'            => Carbon::today()->addDays(30)->toDateString(),
                'claim_discount_amount' => 2000.00,
                'status'                => 'active',
                'badge'                 => 'IELTS Academic',
                'cta_text'              => 'Claim NPR 2,000 IELTS Discount',
                'cta_url'               => '/apply-exam-booking',
                'sort_order'            => 1,
            ],
            [
                'title'                 => 'NPR 1,500 Off on PTE Academic Registration',
                'description'           => 'Register for PTE Academic through our platform and save NPR 1,500. Offer valid for a limited time. Includes free preparation consultation.',
                'start_date'            => Carbon::today()->toDateString(),
                'valid_date'            => Carbon::today()->addDays(45)->toDateString(),
                'claim_discount_amount' => 1500.00,
                'status'                => 'active',
                'badge'                 => 'PTE Academic',
                'cta_text'              => 'Claim NPR 1,500 PTE Discount',
                'cta_url'               => '/apply-exam-booking',
                'sort_order'            => 2,
            ],
            [
                'title'                 => 'TOEFL iBT — NPR 500 Booking Discount',
                'description'           => 'Book your TOEFL iBT exam with KTM Constancy and save NPR 500. Our experts guide you through the registration process end-to-end.',
                'start_date'            => Carbon::today()->toDateString(),
                'valid_date'            => Carbon::today()->addDays(20)->toDateString(),
                'claim_discount_amount' => 500.00,
                'status'                => 'active',
                'badge'                 => 'TOEFL iBT',
                'cta_text'              => 'Claim NPR 500 TOEFL Discount',
                'cta_url'               => '/apply-exam-booking',
                'sort_order'            => 3,
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
