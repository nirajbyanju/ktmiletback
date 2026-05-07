<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyAddress;
use Database\Factories\Concerns\ResolvesFactoryRelations;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PropertyAddress>
 */
class PropertyAddressFactory extends Factory
{
    use ResolvesFactoryRelations;

    protected $model = PropertyAddress::class;

    public function definition(): array
    {
        $createdBy = $this->randomUserId();

        return [
            'property_id' => Property::factory(),
            'province_id' => (string) fake()->numberBetween(1, 7),
            'district_id' => (string) fake()->numberBetween(1, 77),
            'municipality_id' => (string) fake()->numberBetween(1, 293),
            'ward_id' => (string) fake()->numberBetween(1, 32),
            'area' => fake()->streetName(),
            'postal_code' => fake()->postcode(),
            'full_address' => fake()->address(),
            'latitude' => (string) fake()->latitude(26.0, 30.0),
            'longitude' => (string) fake()->longitude(80.0, 89.0),
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ];
    }
}
