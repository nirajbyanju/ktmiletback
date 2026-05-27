<?php

namespace Database\Seeders;

use App\Models\MockTestSubscription;
use Illuminate\Database\Seeder;

class MockTestSubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'subscriptions_name'     => 'IELTS Mock Test - Monthly',
                'subscriptions_type'     => 'Mock Test',
                'subscriptions_category' => 'IELTS',
                'company_name'           => 'KTM Consultancy',
                'country'                => 'Nepal',
                'price'                  => 1500.00,
                'discount'               => 0.00,
                'duration'               => 30,
                'duration_type'          => 'days',
            ],
            [
                'subscriptions_name'     => 'IELTS Mock Test - Quarterly',
                'subscriptions_type'     => 'Mock Test',
                'subscriptions_category' => 'IELTS',
                'company_name'           => 'KTM Consultancy',
                'country'                => 'Nepal',
                'price'                  => 3800.00,
                'discount'               => 100.00,
                'duration'               => 90,
                'duration_type'          => 'days',
            ],
            [
                'subscriptions_name'     => 'PTE Mock Test - Monthly',
                'subscriptions_type'     => 'Mock Test',
                'subscriptions_category' => 'PTE',
                'company_name'           => 'KTM Consultancy',
                'country'                => 'Nepal',
                'price'                  => 1200.00,
                'discount'               => 0.00,
                'duration'               => 30,
                'duration_type'          => 'days',
            ],
            [
                'subscriptions_name'     => 'PTE Mock Test - Full Bundle',
                'subscriptions_type'     => 'Mock Test',
                'subscriptions_category' => 'PTE',
                'company_name'           => 'KTM Consultancy',
                'country'                => 'Nepal',
                'price'                  => 4500.00,
                'discount'               => 500.00,
                'duration'               => 180,
                'duration_type'          => 'days',
            ],
            [
                'subscriptions_name'     => 'TOEFL Mock Test - Monthly',
                'subscriptions_type'     => 'Mock Test',
                'subscriptions_category' => 'TOEFL',
                'company_name'           => 'KTM Consultancy',
                'country'                => 'Nepal',
                'price'                  => 1800.00,
                'discount'               => 0.00,
                'duration'               => 30,
                'duration_type'          => 'days',
            ],
        ];

        foreach ($plans as $plan) {
            MockTestSubscription::updateOrCreate(
                ['subscriptions_name' => $plan['subscriptions_name']],
                $plan
            );
        }

        $this->command->info('Seeded ' . count($plans) . ' mock test subscription plan(s).');
    }
}
