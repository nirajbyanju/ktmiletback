<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegistrationService
{
    public function __construct(
        private readonly AdminNotificationService $adminNotificationService,
    ) {
    }

    public function registerUser(array $data): array
    {
        return $this->register($data, 'Employee');
    }

    public function registerAdmin(array $data): array
    {
        return $this->register($data, 'Admin');
    }

    protected function register(array $data, string $roleName): array
    {
        return DB::transaction(function () use ($data, $roleName) {
            $user = User::create($this->buildUserData($data));

            UserDetail::create([
                'user_id' => $user->id,
            ]);

            $user->syncRoles([$roleName]);
            $user->load('roles');

            DB::afterCommit(function () use ($user): void {
                $freshUser = $user->fresh('roles');

                if ($freshUser) {
                    $this->adminNotificationService->notifyNewRegistration($freshUser);
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
