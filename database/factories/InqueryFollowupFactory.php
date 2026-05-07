<?php

namespace Database\Factories;

use App\Models\Data\ContactMethod;
use App\Models\Data\FollowupStatus;
use App\Models\Inquery;
use App\Models\InqueryFollowup;
use Database\Factories\Concerns\ResolvesFactoryRelations;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InqueryFollowup>
 */
class InqueryFollowupFactory extends Factory
{
    use ResolvesFactoryRelations;

    protected $model = InqueryFollowup::class;

    public function definition(): array
    {
        $adminId = $this->randomUserId();

        return [
            'inquiry_id' => $this->randomModelId(Inquery::class) ?? Inquery::factory(),
            'admin_id' => $adminId,
            'contact_method_id' => $this->randomModelId(ContactMethod::class, 1),
            'followup_status_id' => $this->randomModelId(FollowupStatus::class, 1),
            'message' => Str::limit(fake()->sentence(12), 255, ''),
            'next_followup_date' => now()->addDays(fake()->numberBetween(1, 30)),
            'status' => 1,
            'is_status' => 1,
            'publishedat' => now()->subDays(fake()->numberBetween(0, 30)),
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ];
    }
}
