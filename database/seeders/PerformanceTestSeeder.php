<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PerformanceTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Running base seeders.');

        $this->call([
            DatabaseSeeder::class,
        ]);

        $this->command?->info('Running large dataset seeder.');

        $this->call([
            LargeTestDatasetSeeder::class,
        ]);
    }
}
