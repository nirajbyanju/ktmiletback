<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate([
            'email' => 'nirajbyanju1234@gmail.com',
        ], [
            'userCode' => 'SM-2026-1',
            'first_name' => 'Niraj',
            'middle_name' => null,
            'last_name' => 'Byanju',
            'username' => 'nirajbyanju',
            'email_verified_at' => now(),
            'phone' => '+9779800000000',
            'password' => Hash::make('password'),
            'status' => 1,
            'remember_token' => Str::random(10),
        ]);
        $admin->syncRoles(['Admin']);

        $manager = User::updateOrCreate([
            'email' => 'manager@example.com',
        ], [
            'userCode' => 'SM-2026-2',
            'first_name' => 'Manager',
            'middle_name' => null,
            'last_name' => 'User',
            'username' => 'manageruser',
            'email_verified_at' => now(),
            'phone' => '+9779800000001',
            'password' => Hash::make('password'),
            'status' => 1,
            'remember_token' => Str::random(10),
        ]);
        $manager->syncRoles(['Manager']);

        $employee = User::updateOrCreate([
            'email' => 'employee@example.com',
        ], [
            'userCode' => 'SM-2026-3',
            'first_name' => 'Employee',
            'middle_name' => null,
            'last_name' => 'User',
            'username' => 'employeeuser',
            'email_verified_at' => now(),
            'phone' => '+9779800000002',
            'password' => Hash::make('password'),
            'status' => 1,
            'remember_token' => Str::random(10),
        ]);
        $employee->syncRoles(['Employee']);
    }
}
