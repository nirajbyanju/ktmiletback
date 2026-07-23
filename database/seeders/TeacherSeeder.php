<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class TeacherSeeder extends Seeder
{
    // ─────────────────────────────────────────────────────────────────────────
    // Teacher definitions
    // Each entry = one User (with Teacher role) + one Teacher profile + courses
    // ─────────────────────────────────────────────────────────────────────────
    private array $teacherData = [
        [
            // ── User account ──────────────────────────────────────────────
            'first_name' => 'Rajesh',
            'last_name'  => 'Adhikari',
            'email'      => 'rajesh.adhikari@ktmconsultancy.com',
            'username'   => 'rajesh.adhikari',
            'phone'      => '+9779801234001',
            'password'   => 'Teacher@2026',

            // ── Teacher profile ───────────────────────────────────────────
            'teacher_id'       => 'T-001',
            'qualification'    => 'M.A. in English Literature, Tribhuvan University',
            'specialization'   => 'IELTS Academic',
            'experience_years' => 9,
            'bio'              => 'Rajesh is KTM lead IELTS instructor with 9+ years of experience preparing Nepali students for Band 7+ scores. He specialises in the Academic module and has helped over 2,000 students achieve their target band.',
            'available_days'   => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'],
            'available_from'   => '06:00',
            'available_to'     => '10:00',
            'available_time'   => 'Morning (6:00 AM - 10:00 AM)',
            'status'           => 'Active',
            'notes'            => 'Lead IELTS instructor. 9+ years experience. Handles Smart Batch and Elite Private batches.',

            // ── Course assignments (course_name from CourseCatalogSeeder) ──
            'course_names' => ['IELTS Preparation Course'],
            'course'       => 'IELTS',
        ],
        [
            'first_name' => 'Priya',
            'last_name'  => 'Shrestha',
            'email'      => 'priya.shrestha@ktmconsultancy.com',
            'username'   => 'priya.shrestha',
            'phone'      => '+9779801234002',
            'password'   => 'Teacher@2026',

            'teacher_id'       => 'T-002',
            'qualification'    => 'B.Ed. in English, Kathmandu University; IELTS Band 8.5',
            'specialization'   => 'IELTS Writing & PTE Speaking',
            'experience_years' => 6,
            'bio'              => 'Priya specialises in IELTS Writing Task 1 & 2 and PTE Speaking. She runs evening batches that are especially popular with working professionals.',
            'available_days'   => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'available_from'   => '16:00',
            'available_to'     => '20:00',
            'available_time'   => 'Evening (4:00 PM - 8:00 PM)',
            'status'           => 'Active',
            'notes'            => 'Specialist in IELTS Writing and PTE Speaking. Evening batch coordinator.',

            'course_names' => ['IELTS Preparation Course', 'PTE Academic Preparation Course'],
            'course'       => 'IELTS / PTE',
        ],
        [
            'first_name' => 'Santosh',
            'last_name'  => 'Poudel',
            'email'      => 'santosh.poudel@ktmconsultancy.com',
            'username'   => 'santosh.poudel',
            'phone'      => '+9779801234003',
            'password'   => 'Teacher@2026',

            'teacher_id'       => 'T-003',
            'qualification'    => 'B.Sc. Computer Science; Pearson Certified PTE Assessor',
            'specialization'   => 'PTE Academic',
            'experience_years' => 7,
            'bio'              => 'Santosh is a former Pearson assessor who brings insider knowledge of the AI-scored PTE format. His students consistently achieve 65+ overall in under 6 weeks.',
            'available_days'   => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'available_from'   => '09:00',
            'available_to'     => '14:00',
            'available_time'   => 'Morning / Afternoon',
            'status'           => 'Active',
            'notes'            => 'PTE Academic specialist. Former Pearson assessor. Morning and early-afternoon sessions.',

            'course_names' => ['PTE Academic Preparation Course'],
            'course'       => 'PTE',
        ],
        [
            'first_name' => 'Anita',
            'last_name'  => 'Gurung',
            'email'      => 'anita.gurung@ktmconsultancy.com',
            'username'   => 'anita.gurung',
            'phone'      => '+9779801234004',
            'password'   => 'Teacher@2026',

            'teacher_id'       => 'T-004',
            'qualification'    => 'M.A. TESOL, Nepal Open University; TOEFL iBT 118/120',
            'specialization'   => 'TOEFL iBT & IELTS Academic',
            'experience_years' => 5,
            'bio'              => 'Anita teaches both TOEFL iBT and IELTS Academic with equal expertise. She runs afternoon classes and is known for her structured approach to integrated writing and speaking tasks.',
            'available_days'   => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'],
            'available_from'   => '12:00',
            'available_to'     => '16:00',
            'available_time'   => 'Afternoon (12:00 PM - 4:00 PM)',
            'status'           => 'Active',
            'notes'            => 'TOEFL iBT and IELTS Academic instructor. Afternoon specialist.',

            'course_names' => ['IELTS Preparation Course'],
            'course'       => 'IELTS / TOEFL',
        ],
        [
            'first_name' => 'Bikash',
            'last_name'  => 'Tamang',
            'email'      => 'bikash.tamang@ktmconsultancy.com',
            'username'   => 'bikash.tamang',
            'phone'      => '+9779801234005',
            'password'   => 'Teacher@2026',

            'teacher_id'       => 'T-005',
            'qualification'    => 'B.A. English; Duolingo English Test Certified Coach',
            'specialization'   => 'Duolingo English Test & PTE',
            'experience_years' => 3,
            'bio'              => 'Bikash coaches students for the Duolingo English Test and provides backup PTE instruction. He is especially effective with students who prefer a flexible schedule or are preparing remotely.',
            'available_days'   => ['Monday', 'Wednesday', 'Friday', 'Saturday'],
            'available_from'   => '08:00',
            'available_to'     => '18:00',
            'available_time'   => 'Flexible / As required',
            'status'           => 'Active',
            'notes'            => 'Duolingo English Test coach and PTE backup instructor. Flexible hours.',

            'course_names' => ['PTE Academic Preparation Course'],
            'course'       => 'Duolingo / PTE',
        ],
    ];

    public function run(): void
    {
        // ── 1. Pre-load courses by name ───────────────────────────────────────
        $allCourseNames = collect($this->teacherData)
            ->pluck('course_names')
            ->flatten()
            ->unique()
            ->values()
            ->all();

        $courses = Course::whereIn('course_name', $allCourseNames)
            ->get()
            ->keyBy('course_name');

        if ($courses->isEmpty()) {
            $this->command->warn(
                'No matching courses found. ' .
                'Run CourseCatalogSeeder first, then re-run TeacherSeeder for course assignments.'
            );
        }

        // ── 2. Resolve or create the Teacher role ─────────────────────────────
        $teacherRole = Role::firstOrCreate(
            ['name' => 'Teacher', 'guard_name' => 'web']
        );

        $seededCount = 0;
        $linkedCount = 0;

        foreach ($this->teacherData as $data) {
            // ── 2a. Create / update user account ──────────────────────────────
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'userCode'          => $this->generateUserCode($data),
                    'name'              => trim($data['first_name'] . ' ' . $data['last_name']),
                    'first_name'        => $data['first_name'],
                    'middle_name'       => null,
                    'last_name'         => $data['last_name'],
                    'username'          => $data['username'],
                    'phone'             => $data['phone'],
                    'password'          => Hash::make($data['password']),
                    'has_password'      => true,
                    'status'            => 1,
                    'email_verified_at' => now(),
                    'remember_token'    => Str::random(10),
                ]
            );

            // ── 2b. Assign Teacher role ────────────────────────────────────────
            if (! $user->hasRole('Teacher')) {
                $user->assignRole($teacherRole);
            }

            // ── 2c. Create / update Teacher profile ───────────────────────────
            $teacher = Teacher::updateOrCreate(
                ['teacher_id' => $data['teacher_id']],
                [
                    'user_id'          => $user->id,
                    'name'             => trim($data['first_name'] . ' ' . $data['last_name']),
                    'email'            => $data['email'],
                    'phone'            => $data['phone'],
                    'course'           => $data['course'],
                    'available_time'   => $data['available_time'],
                    'qualification'    => $data['qualification'],
                    'specialization'   => $data['specialization'],
                    'experience_years' => $data['experience_years'],
                    'bio'              => $data['bio'],
                    'available_days'   => $data['available_days'],
                    'available_from'   => $data['available_from'],
                    'available_to'     => $data['available_to'],
                    'status'           => $data['status'],
                    'notes'            => $data['notes'],
                ]
            );

            // ── 2d. Sync course pivot ──────────────────────────────────────────
            $courseIds = collect($data['course_names'])
                ->map(fn (string $name) => $courses->get($name)?->id)
                ->filter()
                ->values()
                ->all();

            if (! empty($courseIds)) {
                $teacher->courses()->sync($courseIds);
                $linkedCount++;
            }

            $seededCount++;
        }

        // ── 3. Summary ────────────────────────────────────────────────────────
        $this->command->info("Seeded {$seededCount} teacher(s).");
        $this->command->info("Course assignments synced for {$linkedCount} teacher(s).");

        if ($courses->isNotEmpty()) {
            $this->command->info('Courses linked: ' . $courses->keys()->implode(', '));
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function generateUserCode(array $data): string
    {
        $year     = now()->year;
        $initials = strtoupper(substr($data['first_name'], 0, 1) . substr($data['last_name'], 0, 1));
        return 'TCH-' . $year . '-' . $initials . '-' . strtoupper(Str::random(4));
    }
}
