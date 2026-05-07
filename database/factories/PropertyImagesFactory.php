<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyImages;
use Database\Factories\Concerns\ResolvesFactoryRelations;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PropertyImages>
 */
class PropertyImagesFactory extends Factory
{
    use ResolvesFactoryRelations;

    protected $model = PropertyImages::class;

    public function definition(): array
    {
        $createdBy = $this->randomUserId();

        return [
            'property_id' => $this->randomModelId(Property::class) ?? Property::factory(),
            'image_url' => 'loadtest/' . fake()->uuid() . '.jpg',
            'image_type' => fake()->randomElement(['gallery', 'cover', 'floorplan']),
            'is_featured' => fake()->boolean(20),
            'sort_order' => fake()->numberBetween(1, 10),
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ];
    }
}
