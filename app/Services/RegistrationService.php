<?php

namespace App\Services;

use App\Mail\WelcomeMail;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegistrationService
{
    public function __construct(
        private readonly AdminNotificationService $adminNotificationService,
    ) {}

    public function registerUser(array $data): array
    {
        return $this->register($data, 'User');
    }

    public function registerAdmin(array $data): array
    {
        return $this->register($data, 'Admin');
    }

    protected function register(array $data, string $roleName): array
    {
        return DB::transaction(function () use ($data, $roleName) {
            $user = User::create($this->buildUserData($data));

            $user->syncRoles([$roleName]);
            $user->load('roles');

            // Refer-a-friend: give the new user their own code, and if they
            // signed up through a friend's code, grant the welcome discount.
            $referrals = app(ReferralService::class);
            $referrals->ensureCode($user);
            if ($roleName === 'User') {
                $referrals->applyReferralCode($user, $data['referral_code'] ?? null);
            }

            // Group bookings: link any member enrollments that were created
            // with this email before the account existed.
            Enrollment::whereNull('user_id')
                ->where('email', $user->email)
                ->update(['user_id' => $user->id]);

            DB::afterCommit(function () use ($user): void {
                $freshUser = $user->fresh('roles');

                if ($freshUser) {
                    $this->adminNotificationService->notifyNewRegistration($freshUser);

                    if ($freshUser->hasRole('User')) {
                        app(TemplateMailer::class)->sendToUser('welcome_account', $freshUser);
                    }

                    // Send welcome email (fail silently — never block registration)
                    try {
                        Mail::to($freshUser->email)->send(new WelcomeMail($freshUser));
                    } catch (\Exception) {
                        // log if needed
                    }
                }
            });

            return [
                'token' => $user->createToken('MyApp')->plainTextToken,
                'name' => $user->display_name,
                'roles' => $user->roles->pluck('name')->values()->all(),
            ];
        });
    }

    protected function buildUserData(array $data): array
    {
        [$firstName, $middleName, $lastName] = $this->splitName($data['name']);

        return [
            'userCode' => $this->generateUserCode(),
            'name' => $data['name'],
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'username' => $this->generateUsername($data['name']),
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'status' => 1,
        ];
    }

    protected function splitName(string $name): array
    {
        $nameParts = preg_split('/\s+/', trim($name)) ?: [];

        $firstName = $nameParts[0] ?? 'User';
        $lastName = count($nameParts) > 1 ? (string) end($nameParts) : $firstName;
        $middleName = count($nameParts) > 2
            ? implode(' ', array_slice($nameParts, 1, -1))
            : null;

        return [$firstName, $middleName, $lastName];
    }

    protected function generateUsername(string $name): string
    {
        $baseUsername = Str::lower(preg_replace('/[^A-Za-z0-9]+/', '', $name) ?: 'user');
        $username = $baseUsername;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $baseUsername.$counter;
            $counter++;
        }

        return $username;
    }

    protected function generateUserCode(): string
    {
        $currentYear = now()->year;
        $latestId = (User::max('id') ?? 0) + 1;

        return "Opsh-{$currentYear}-{$latestId}";
    }
}
