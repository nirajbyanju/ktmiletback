<?php

namespace Database\Factories;

use App\Models\Data\ListingType;
use App\Models\Data\PropertyCategory;
use App\Models\Data\PropertyFace;
use App\Models\Data\PropertyStatus;
use App\Models\Data\PropertyType;
use App\Models\Data\RoadCondition;
use App\Models\Data\RoadType;
use App\Models\Data\SewageType;
use App\Models\Data\Unit;
use App\Models\Data\WaterSource;
use App\Models\Property;
use Database\Factories\Concerns\ResolvesFactoryRelations;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    use ResolvesFactoryRelations;

    protected $model = Property::class;

    public function definition(): array
    {
        $title = fake()->streetName() . ' Property';
        $basePrice = fake()->numberBetween(3000000, 25000000);
        $createdBy = $this->randomUserId();
        $verifiedBy = fake()->boolean(70) ? ($this->randomUserId() ?? $createdBy) : null;

        return [
            'property_code' => fake()->unique()->numerify('PROP-TEST-######'),
            'title' => $title,
            'slug' => Str::slug($title . '-' . fake()->unique()->numberBetween(1000, 999999)),
            'tags' => implode(',', fake()->words(3)),
            'description' => fake()->paragraphs(3, true),
            'land_area' => (string) fake()->numberBetween(2, 20),
            'land_unit_id' => $this->randomModelId(Unit::class),
            'property_face_id' => $this->randomModelId(PropertyFace::class),
            'property_type_id' => $this->randomModelId(PropertyType::class),
            'listing_type_id' => $this->randomModelId(ListingType::class),
            'property_category_id' => $this->randomModelId(PropertyCategory::class),
            'video_url' => fake()->optional()->url(),
            'nearby_places' => json_encode([]),
            'length' => (string) fake()->numberBetween(10, 60),
            'height' => (string) fake()->numberBetween(10, 60),
            'measure_unit_id' => $this->randomModelId(Unit::class),
            'is_road_accessible' => fake()->boolean(85),
            'road_type_id' => $this->randomModelId(RoadType::class),
            'road_condition_id' => $this->randomModelId(RoadCondition::class),
            'road_width' => (string) fake()->numberBetween(8, 30),
            'base_price' => $basePrice,
            'advertise_price' => $basePrice + fake()->numberBetween(250000, 5000000),
            'currency' => 'NPR',
            'is_featured' => fake()->boolean(20),
            'is_negotiable' => fake()->boolean(),
            'banking_available' => fake()->boolean(),
            'has_electricity' => fake()->boolean(90),
            'water_source_id' => $this->randomModelId(WaterSource::class),
            'sewage_type_id' => $this->randomModelId(SewageType::class),
            'views_count' => fake()->numberBetween(0, 5000),
            'likes_count' => fake()->numberBetween(0, 500),
            'seo_title' => $title,
            'seo_description' => Str::limit(fake()->sentence(18), 180, ''),
            'property_status_id' => $this->randomModelId(PropertyStatus::class, 0),
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
            'verified_by' => $verifiedBy,
            'verified_at' => $verifiedBy ? now()->subDays(fake()->numberBetween(0, 60)) : null,
            'status' => 1,
            'is_status' => 1,
            'publishedat' => now()->subDays(fake()->numberBetween(0, 60)),
        ];
    }
}
