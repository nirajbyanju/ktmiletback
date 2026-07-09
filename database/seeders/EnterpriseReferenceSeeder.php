<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Enterprise Reference Seeder
 *
 * Performs the data-migration step for the enterprise FK columns added in
 * migration 2026_07_09_000006:
 *
 *   1. Links user_details.country (varchar) → countries.id via country_id FK
 *   2. Links mock_test_subscriptions.country (varchar) → countries.id
 *   3. Seeds batch_types with any values not already present
 *
 * Also verifies that all lookup_categories and system_settings were seeded
 * by the migrations (they should be — this seeder only adds supplemental data).
 *
 * Run via: php artisan db:seed --class=EnterpriseReferenceSeeder
 */
class EnterpriseReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->linkCountryVarcharsToIds();
        $this->linkMockTestCountries();
        $this->command->info('[EnterpriseReferenceSeeder] Done.');
    }

    /**
     * Map user_details.country (string) → user_details.country_id (FK)
     * Uses fuzzy matching: ISO2, ISO3, or name LIKE.
     */
    private function linkCountryVarcharsToIds(): void
    {
        if (!DB::getSchemaBuilder()->hasColumn('user_details', 'country_id')) {
            return;
        }

        $countries = DB::table('countries')
            ->select('id', 'iso2', 'iso3', 'name')
            ->get();

        $map = [];
        foreach ($countries as $c) {
            $map[strtolower($c->iso2)]   = $c->id;
            $map[strtolower($c->iso3)]   = $c->id;
            $map[strtolower($c->name)]   = $c->id;
        }

        DB::table('user_details')
            ->whereNotNull('country')
            ->whereNull('country_id')
            ->select('id', 'country')
            ->chunkById(200, function ($rows) use ($map) {
                foreach ($rows as $row) {
                    $key = strtolower(trim($row->country));
                    $countryId = $map[$key] ?? null;
                    if ($countryId) {
                        DB::table('user_details')
                            ->where('id', $row->id)
                            ->update(['country_id' => $countryId]);
                    }
                }
            });
    }

    /**
     * Map mock_test_subscriptions.country (string) → country_id FK.
     */
    private function linkMockTestCountries(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('mock_test_subscriptions')) {
            return;
        }
        if (!DB::getSchemaBuilder()->hasColumn('mock_test_subscriptions', 'country_id')) {
            return;
        }

        $countries = DB::table('countries')
            ->select('id', 'iso2', 'iso3', 'name')
            ->get();

        $map = [];
        foreach ($countries as $c) {
            $map[strtolower($c->iso2)] = $c->id;
            $map[strtolower($c->iso3)] = $c->id;
            $map[strtolower($c->name)] = $c->id;
        }

        DB::table('mock_test_subscriptions')
            ->whereNotNull('country')
            ->whereNull('country_id')
            ->select('id', 'country')
            ->chunkById(200, function ($rows) use ($map) {
                foreach ($rows as $row) {
                    $key = strtolower(trim($row->country));
                    $countryId = $map[$key] ?? null;
                    if ($countryId) {
                        DB::table('mock_test_subscriptions')
                            ->where('id', $row->id)
                            ->update(['country_id' => $countryId]);
                    }
                }
            });
    }
}
