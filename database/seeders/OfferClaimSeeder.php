<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\OfferClaim;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class OfferClaimSeeder extends Seeder
{
    public function run(): void
    {
        $offers   = Offer::where('status', 'active')->get();
        $students = User::role('User')->get();

        if ($offers->isEmpty()) {
            $this->command->warn('No active offers found — skipping offer claims.');
            return;
        }

        if ($students->isEmpty()) {
            $this->command->warn('No students found — skipping offer claims.');
            return;
        }

        $claimCount = 0;

        foreach ($students as $i => $student) {
            // Each student claims one offer (staggered across offers)
            $offer = $offers[$i % $offers->count()];

            OfferClaim::updateOrCreate(
                [
                    'user_id'  => $student->id,
                    'offer_id' => $offer->id,
                ],
                [
                    'offer_claim_date' => Carbon::today()->subDays(rand(0, 10))->toDateString(),
                ]
            );

            $claimCount++;
        }

        $this->command->info("Created {$claimCount} offer claim(s).");
    }
}
