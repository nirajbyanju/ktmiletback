<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            MenuSeeder::class,
            RolePermissionSeeder::class,
            // PermissionsSeeder::class,
            // PermissionMatrixSeeder::class,
            UserSeeder::class,


            ConstructionStatusSeeder::class,
            FurnishingSeeder::class,
            HouseDetailSeeder::class,
            HouseTypeSeeder::class,
            ListingTypeSeeder::class,
            NepalLocationSeeder::class,
            PropertyFaceSeeder::class,
            PropertyStatusSeeder::class,
            PropertyTypeSeeder::class,
            RoadConditionSeeder::class,
            RoadTypeSeeder::class,
            RoofTypeSeeder::class,
            SewageTypeSeeder::class,
            UnitSeeder::class,
            WaterSourceSeeder::class,
            MeasureUnitsSeeder::class,
            PropertyCategorySeeder::class,
            ParkingTypeSeeder::class,
            AmenitiesSeeder::class,
            ContactMethodSeeder::class,
            FollowupStatusSeeder::class,
            HouseMaterialsSeeder::class,
            ContentMethodSeeder::class,
            RequestTypeSeeder::class,
            StatusSeeder::class,
            CategorySeeder::class,
        ]);
    }
}
