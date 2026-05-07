<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\Data\Category;
use App\Models\Data\ContactMethod;
use App\Models\Data\FollowupStatus;
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
use App\Models\User;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LargeTestDatasetSeeder extends Seeder
{
    private const PROPERTY_COUNT = 5000;
    private const INQUIRY_COUNT = 5000;
    private const FOLLOWUP_COUNT = 5000;
    private const FIELD_VISIT_COUNT = 5000;
    private const BLOG_COUNT = 5000;
    private const TEST_USER_COUNT = 200;

    public function run(): void
    {
        $faker = FakerFactory::create();
        $this->command?->info('Seeding 5,000+ records per core module for load testing.');

        DB::transaction(function () use ($faker) {
            $userIds = $this->ensureTestUsers($faker);

            $lookups = $this->loadLookupIds();
            $propertyIds = $this->seedProperties($faker, $userIds, $lookups);
            $inquiryIds = $this->seedInquiries($faker, $userIds, $propertyIds, $lookups);

            $this->seedFollowups($faker, $userIds, $inquiryIds, $lookups);
            $this->seedFieldVisits($faker, $userIds, $propertyIds);
            $this->seedBlogs($faker, $userIds, $lookups);
        });

        $this->command?->info('Large test dataset seeded successfully.');
    }

    private function ensureTestUsers($faker): array
    {
        $userIds = User::query()->pluck('id')->all();
        $baseCount = count($userIds);
        $userCodeColumn = $this->userCodeColumn();

        for ($i = 1; $i <= self::TEST_USER_COUNT; $i++) {
            $firstName = $faker->firstName();
            $lastName = $faker->lastName();
            $username = 'loaduser_' . ($baseCount + $i) . '_' . Str::lower(Str::random(4));

            $userData = [
                'first_name' => $firstName,
                'middle_name' => null,
                'last_name' => $lastName,
                'username' => $username,
                'email' => "{$username}@example.test",
                'phone' => '98' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                'password' => Hash::make('password'),
                'status' => 1,
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $userData[$userCodeColumn] = strtoupper(Str::random(10));

            $userId = DB::table('users')->insertGetId($userData);

            $user = User::query()->find($userId);
            if ($user) {
                $user->assignRole($i % 5 === 0 ? 'Manager' : 'Employee');
            }

            $userIds[] = $userId;
        }

        return $userIds;
    }

    private function loadLookupIds(): array
    {
        $lookups = [
            'listing_type_ids' => ListingType::query()->pluck('id')->all(),
            'property_category_ids' => PropertyCategory::query()->pluck('id')->all(),
            'property_face_ids' => PropertyFace::query()->pluck('id')->all(),
            'property_type_ids' => PropertyType::query()->pluck('id')->all(),
            'property_status_ids' => PropertyStatus::query()->pluck('id')->all(),
            'road_condition_ids' => RoadCondition::query()->pluck('id')->all(),
            'road_type_ids' => RoadType::query()->pluck('id')->all(),
            'sewage_type_ids' => SewageType::query()->pluck('id')->all(),
            'unit_ids' => Unit::query()->pluck('id')->all(),
            'water_source_ids' => WaterSource::query()->pluck('id')->all(),
            'contact_method_ids' => ContactMethod::query()->pluck('id')->all(),
            'followup_status_ids' => FollowupStatus::query()->pluck('id')->all(),
            'blog_category_ids' => Category::query()->pluck('id')->all(),
        ];

        foreach ($lookups as $key => $ids) {
            if (empty($ids)) {
                throw new \RuntimeException("Missing lookup data for {$key}. Run the base seeders first.");
            }
        }

        return $lookups;
    }

    private function seedProperties($faker, array $userIds, array $lookups): array
    {
        $propertyIds = [];

        for ($i = 1; $i <= self::PROPERTY_COUNT; $i++) {
            $createdBy = Arr::random($userIds);
            $title = $faker->streetName() . ' Property ' . $i;
            $slug = Str::slug($title) . '-' . $i;
            $propertyId = DB::table('properties')->insertGetId([
                'property_code' => 'LOAD-PROP-' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'title' => $title,
                'slug' => $slug,
                'tags' => implode(',', $faker->words(3)),
                'description' => $faker->paragraphs(3, true),
                'land_area' => (string) $faker->numberBetween(2, 20),
                'land_unit_id' => Arr::random($lookups['unit_ids']),
                'property_face_id' => Arr::random($lookups['property_face_ids']),
                'property_type_id' => Arr::random($lookups['property_type_ids']),
                'listing_type_id' => Arr::random($lookups['listing_type_ids']),
                'property_category_id' => Arr::random($lookups['property_category_ids']),
                'length' => (string) $faker->numberBetween(10, 80),
                'height' => (string) $faker->numberBetween(10, 80),
                'measure_unit_id' => Arr::random($lookups['unit_ids']),
                'is_road_accessible' => $faker->boolean(85),
                'road_type_id' => Arr::random($lookups['road_type_ids']),
                'road_condition_id' => Arr::random($lookups['road_condition_ids']),
                'road_width' => (string) $faker->numberBetween(8, 30),
                'base_price' => $faker->numberBetween(3000000, 50000000),
                'advertise_price' => $faker->numberBetween(3500000, 60000000),
                'currency' => 'NPR',
                'is_featured' => $faker->boolean(20),
                'is_negotiable' => $faker->boolean(50),
                'banking_available' => $faker->boolean(40),
                'has_electricity' => $faker->boolean(90),
                'water_source_id' => Arr::random($lookups['water_source_ids']),
                'sewage_type_id' => Arr::random($lookups['sewage_type_ids']),
                'views_count' => $faker->numberBetween(10, 5000),
                'likes_count' => $faker->numberBetween(0, 500),
                'seo_title' => $title,
                'seo_description' => Str::limit($faker->sentence(20), 180, ''),
                'property_status_id' => Arr::random($lookups['property_status_ids']),
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
                'deleted_by' => null,
                'verified_by' => Arr::random($userIds),
                'verified_at' => now()->subDays(random_int(0, 90)),
                'is_status' => 1,
                'status' => 1,
                'publishedat' => now()->subDays(random_int(0, 90)),
                'created_at' => now()->subDays(random_int(0, 180)),
                'updated_at' => now()->subDays(random_int(0, 30)),
                'deleted_at' => null,
            ]);

            DB::table('property_addresses')->insert([
                'property_id' => $propertyId,
                'province_id' => (string) random_int(1, 7),
                'district_id' => (string) random_int(1, 77),
                'municipality_id' => (string) random_int(1, 293),
                'ward_id' => (string) random_int(1, 32),
                'area' => $faker->streetName(),
                'postal_code' => (string) random_int(10000, 99999),
                'full_address' => $faker->address(),
                'latitude' => (string) $faker->latitude(26.0, 30.0),
                'longitude' => (string) $faker->longitude(80.0, 89.0),
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);

            DB::table('property_images')->insert([
                [
                    'property_id' => $propertyId,
                    'image_url' => "/storage/loadtest/property-{$propertyId}-1.jpg",
                    'image_type' => 'gallery',
                    'is_featured' => true,
                    'sort_order' => 1,
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                    'deleted_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ],
                [
                    'property_id' => $propertyId,
                    'image_url' => "/storage/loadtest/property-{$propertyId}-2.jpg",
                    'image_type' => 'gallery',
                    'is_featured' => false,
                    'sort_order' => 2,
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                    'deleted_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ],
            ]);

            $propertyIds[] = $propertyId;
        }

        return $propertyIds;
    }

    private function seedInquiries($faker, array $userIds, array $propertyIds, array $lookups): array
    {
        $inquiryIds = [];

        for ($i = 1; $i <= self::INQUIRY_COUNT; $i++) {
            $createdBy = Arr::random($userIds);

            $inquiryIds[] = DB::table('inquiries')->insertGetId([
                'property_id' => Arr::random($propertyIds),
                'from' => (string) random_int(1, 2),
                'inquiry_type_id' => random_int(1, 2),
                'property_type_id' => Arr::random($lookups['property_type_ids']),
                'name' => $faker->name(),
                'email' => $faker->safeEmail(),
                'phone' => '98' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                'location' => $faker->city(),
                'budget' => (string) $faker->numberBetween(1000000, 50000000),
                'message' => $faker->sentence(),
                'description' => $faker->paragraph(),
                'response_type_id' => (string) random_int(1, 3),
                'reason' => $faker->sentence(),
                'is_status' => 1,
                'status' => 1,
                'publishedat' => now()->subDays(random_int(0, 90)),
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
                'deleted_by' => null,
                'created_at' => now()->subDays(random_int(0, 120)),
                'updated_at' => now()->subDays(random_int(0, 15)),
                'deleted_at' => null,
            ]);
        }

        return $inquiryIds;
    }

    private function seedFollowups($faker, array $userIds, array $inquiryIds, array $lookups): void
    {
        for ($i = 1; $i <= self::FOLLOWUP_COUNT; $i++) {
            $createdBy = Arr::random($userIds);

            DB::table('inqury_followups')->insert([
                'inquiry_id' => Arr::random($inquiryIds),
                'admin_id' => Arr::random($userIds),
                'contact_method_id' => Arr::random($lookups['contact_method_ids']),
                'followup_status_id' => Arr::random($lookups['followup_status_ids']),
                'message' => Str::limit($faker->sentence(12), 255, ''),
                'next_followup_date' => now()->addDays(random_int(1, 30)),
                'is_status' => 1,
                'status' => 1,
                'publishedat' => now()->subDays(random_int(0, 45)),
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
                'deleted_by' => null,
                'created_at' => now()->subDays(random_int(0, 60)),
                'updated_at' => now()->subDays(random_int(0, 10)),
                'deleted_at' => null,
            ]);
        }
    }

    private function seedFieldVisits($faker, array $userIds, array $propertyIds): void
    {
        for ($i = 1; $i <= self::FIELD_VISIT_COUNT; $i++) {
            $createdBy = Arr::random($userIds);
            $visitDate = now()->addDays(random_int(-30, 30));

            DB::table('field_visits')->insert([
                'property_id' => Arr::random($propertyIds),
                'date' => $visitDate->toDateString(),
                'time' => $visitDate->format('H:i'),
                'name' => $faker->name(),
                'phone' => '98' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                'email' => $faker->safeEmail(),
                'message' => $faker->sentence(),
                'accept_term' => '1',
                'remarks' => $faker->optional()->sentence(),
                'is_status' => 1,
                'status' => 1,
                'publishedat' => now()->subDays(random_int(0, 30)),
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
                'deleted_by' => null,
                'created_at' => now()->subDays(random_int(0, 60)),
                'updated_at' => now()->subDays(random_int(0, 10)),
                'deleted_at' => null,
            ]);
        }
    }

    private function seedBlogs($faker, array $userIds, array $lookups): void
    {
        $hasTocStructure = Schema::hasColumn('blogs_posts', 'toc_structure');

        for ($i = 1; $i <= self::BLOG_COUNT; $i++) {
            $title = $faker->sentence(6);
            $createdBy = Arr::random($userIds);
            $blogData = [
                'title' => $title,
                'slug' => Str::slug($title) . '-' . $i . '-' . Str::lower(Str::random(4)),
                'author' => $faker->name(),
                'category_id' => Arr::random($lookups['blog_category_ids']),
                'content' => $faker->paragraphs(6, true),
                'publish_date' => now()->subDays(random_int(0, 90)),
                'tags' => json_encode($faker->words(4)),
                'thumbnail' => "/storage/loadtest/blog-{$i}.jpg",
                'media' => null,
                'status' => 1,
                'is_status' => 1,
                'scheduled_publish_date' => null,
                'view_count' => random_int(10, 5000),
                'like_count' => random_int(0, 500),
                'bookmark_count' => random_int(0, 200),
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
                'deleted_by' => null,
                'created_at' => now()->subDays(random_int(0, 120)),
                'updated_at' => now()->subDays(random_int(0, 15)),
                'deleted_at' => null,
            ];

            if ($hasTocStructure) {
                $blogData['toc_structure'] = json_encode([]);
            }

            DB::table('blogs_posts')->insert($blogData);
        }
    }

    private function userCodeColumn(): string
    {
        return Schema::hasColumn('users', 'userCode') ? 'userCode' : 'usercode';
    }
}
