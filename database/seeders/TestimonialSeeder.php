<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'initials'    => 'SP',
                'name'        => 'Sita P.',
                'meta'        => 'Studying in Australia',
                'tag'         => 'IELTS Band 7.5',
                'country'     => 'Australia',
                'quote'       => 'The teachers explained every IELTS section so clearly. The mock tests felt exactly like the real exam — I walked in confident on test day and got my target band on the first attempt.',
                'cats'        => ['ielts'],
                'is_featured' => true,
                'sort_order'  => 1,
            ],
            [
                'initials'    => 'RK',
                'name'        => 'Ramesh K.',
                'meta'        => 'Currently in the UK',
                'tag'         => 'PTE 79',
                'country'     => 'UK',
                'quote'       => 'I joined the Global Flex Batch from London. Same Nepali teachers, time zone that worked for me, and weekly speaking practice that actually helped. Highly recommend for Nepalese students abroad.',
                'cats'        => ['pte', 'abroad'],
                'is_featured' => true,
                'sort_order'  => 2,
            ],
            [
                'initials'    => 'AS',
                'name'        => 'Anjali S.',
                'meta'        => 'Joined from Kathmandu',
                'tag'         => 'PTE 72',
                'country'     => 'Nepal',
                'quote'       => 'Affordable fees, honest teaching, and real computer-based practice. Their booking support saved me from a passport detail mistake too. I cleared PTE on my second try with their help.',
                'cats'        => ['pte'],
                'is_featured' => false,
                'sort_order'  => 3,
            ],
            [
                'initials'    => 'PB',
                'name'        => 'Prakash B.',
                'meta'        => 'Joined from Pokhara',
                'tag'         => 'IELTS Band 7.0',
                'country'     => 'Nepal',
                'quote'       => 'I work full-time so the weekend batch was perfect. Teachers were patient with my questions on WhatsApp even after class hours. Worth every rupee.',
                'cats'        => ['ielts'],
                'is_featured' => false,
                'sort_order'  => 4,
            ],
            [
                'initials'    => 'MT',
                'name'        => 'Manisha T.',
                'meta'        => 'Heading to Canada',
                'tag'         => 'PTE 76',
                'country'     => 'Canada',
                'quote'       => 'The teachers gave me PTE templates that worked. Score came in 2 days. The booking team also handled my Pearson registration which made my life so easy.',
                'cats'        => ['pte'],
                'is_featured' => true,
                'sort_order'  => 5,
            ],
            [
                'initials'    => 'DN',
                'name'        => 'Deepak N.',
                'meta'        => 'Currently in Qatar',
                'tag'         => 'IELTS Band 6.5',
                'country'     => 'Qatar',
                'quote'       => 'Joined from Doha and the time slots worked perfectly. The recorded classes for the days I missed were a lifesaver. Honest institute with real teaching.',
                'cats'        => ['ielts', 'abroad'],
                'is_featured' => false,
                'sort_order'  => 6,
            ],
            [
                'initials'    => 'SG',
                'name'        => 'Sneha G.',
                'meta'        => 'Joined from Sydney',
                'tag'         => 'IELTS Band 7.0',
                'country'     => 'Australia',
                'quote'       => 'I needed a quick band 7 for my PR application. The Fast-Track Private plan was intense but exactly what I needed. Got my result in 3 weeks of prep.',
                'cats'        => ['ielts', 'abroad'],
                'is_featured' => false,
                'sort_order'  => 7,
            ],
            [
                'initials'    => 'BK',
                'name'        => 'Bibek K.',
                'meta'        => 'Joined from Lalitpur',
                'tag'         => 'PTE 74',
                'country'     => 'Nepal',
                'quote'       => 'PTE felt scary at first but the speaking templates and re-tell lecture drills changed everything. Cleared on first attempt with a solid score.',
                'cats'        => ['pte'],
                'is_featured' => false,
                'sort_order'  => 8,
            ],
        ];

        foreach ($testimonials as $data) {
            Testimonial::updateOrCreate(
                ['name' => $data['name'], 'tag' => $data['tag']],
                $data
            );
        }

        $this->command->info('Seeded ' . count($testimonials) . ' testimonial(s).');
    }
}
