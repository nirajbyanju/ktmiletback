<?php

namespace Database\Factories;

use App\Models\Data\PropertyType;
use App\Models\Inquery;
use App\Models\Property;
use Database\Factories\Concerns\ResolvesFactoryRelations;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inquery>
 */
class InqueryFactory extends Factory
{
    use ResolvesFactoryRelations;

    protected $model = Inquery::class;

    public function definition(): array
    {
        $createdBy = $this->randomUserId();

        return [
            'property_id' => $this->randomModelId(Property::class) ?? Property::factory(),
            'from' => (string) fake()->randomElement([1, 2]),
            'inquiry_type_id' => fake()->randomElement([1, 2]),
            'property_type_id' => $this->randomModelId(PropertyType::class),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '98' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'location' => fake()->city(),
            'budget' => (string) fake()->numberBetween(1000000, 50000000),
            'message' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'response_type_id' => (string) fake()->randomElement([1, 2, 3]),
            'reason' => fake()->sentence(),
            'status' => 1,
            'is_status' => 1,
            'publishedat' => now()->subDays(fake()->numberBetween(0, 45)),
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ];
    }
}
