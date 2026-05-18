<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $students = [
            ['first' => 'Aarav',    'last' => 'Sharma',     'phone' => '+9779801111001'],
            ['first' => 'Bibek',    'last' => 'Thapa',      'phone' => '+9779801111002'],
            ['first' => 'Chitra',   'last' => 'Gurung',     'phone' => '+9779801111003'],
            ['first' => 'Deepa',    'last' => 'Poudel',     'phone' => '+9779801111004'],
            ['first' => 'Esha',     'last' => 'Maharjan',   'phone' => '+9779801111005'],
            ['first' => 'Faisal',   'last' => 'Ansari',     'phone' => '+9779801111006'],
            ['first' => 'Gita',     'last' => 'Shrestha',   'phone' => '+9779801111007'],
            ['first' => 'Hari',     'last' => 'Adhikari',   'phone' => '+9779801111008'],
            ['first' => 'Ishaan',   'last' => 'Karki',      'phone' => '+9779801111009'],
            ['first' => 'Jyoti',    'last' => 'Rai',        'phone' => '+9779801111010'],
            ['first' => 'Kamal',    'last' => 'Bhatt',      'phone' => '+9779801111011'],
            ['first' => 'Laxmi',    'last' => 'Tamang',     'phone' => '+9779801111012'],
            ['first' => 'Manish',   'last' => 'Ghimire',    'phone' => '+9779801111013'],
            ['first' => 'Nisha',    'last' => 'Basnet',     'phone' => '+9779801111014'],
            ['first' => 'Omkar',    'last' => 'Dhakal',     'phone' => '+9779801111015'],
        ];

        $counter = 10;

        foreach ($students as $s) {
            $counter++;
            $slug     = strtolower($s['first'] . $s['last']);
            $email    = $slug . '@student.test';
            $username = $slug . $counter;
            $code     = 'KTM-2026-' . $counter;
            $fullName = $s['first'] . ' ' . $s['last'];

            User::updateOrCreate(['email' => $email], [
                'userCode'           => $code,
                'name'               => $fullName,
                'first_name'         => $s['first'],
                'last_name'          => $s['last'],
                'username'           => $username,
                'phone'              => $s['phone'],
                'password'           => Hash::make('password'),
                'has_password'       => true,
                'email_verified_at'  => now()->subDays(rand(1, 60)),
                'status'             => 1,
            ])->syncRoles(['User']);
        }
    }
}
