<?php

namespace Database\Factories;

use App\Models\BlogPost;
use App\Models\Data\Category;
use Database\Factories\Concerns\ResolvesFactoryRelations;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlogPost>
 */
class BlogPostFactory extends Factory
{
    use ResolvesFactoryRelations;

    protected $model = BlogPost::class;

    public function definition(): array
    {
        $title = fake()->sentence(6);
        $createdBy = $this->randomUserId();

        return [
            'title' => $title,
            'slug' => Str::slug($title . '-' . fake()->unique()->numberBetween(1000, 999999)),
            'author' => fake()->name(),
            'category_id' => $this->randomModelId(Category::class, 1),
            'content' => '<h1>' . fake()->sentence(4) . '</h1><p>' . fake()->paragraph() . '</p><h2>' . fake()->sentence(3) . '</h2><p>' . fake()->paragraph() . '</p>',
            'publish_date' => now()->subDays(fake()->numberBetween(0, 90)),
            'tags' => fake()->words(4),
            'thumbnail' => 'loadtest/' . fake()->uuid() . '.jpg',
            'media' => null,
            'status' => 1,
            'is_status' => 1,
            'scheduled_publish_date' => null,
            'view_count' => fake()->numberBetween(0, 5000),
            'like_count' => fake()->numberBetween(0, 500),
            'bookmark_count' => fake()->numberBetween(0, 200),
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ];
    }
}
