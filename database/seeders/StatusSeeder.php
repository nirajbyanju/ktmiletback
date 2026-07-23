<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        // Blog statuses are now managed as enum values in the application.
        // This seeder is intentionally left empty.
        $this->command->info('StatusSeeder: nothing to seed.');
    }
}
