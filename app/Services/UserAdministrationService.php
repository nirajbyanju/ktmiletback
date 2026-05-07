<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserAdministrationService
{
    public function __construct(
        private readonly AdminNotificationService $adminNotificationService,
    ) {
    }

    public function createUser(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor) {
            $roles = $this->resolveRoles($data['roles'] ?? []);
            $this->guardRestrictedRoles($roles, $actor);

            $user = User::create([
                'userCode' => $this->generateUserCode(),
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'status' => array_key_exists('status', $data) ? (int) (bool) $data['status'] : 1,
            ]);

            $user->forceFill([
                'email_verified_at' => ($data['email_verified'] ?? true) ? now() : null,
            ])->save();

            $user->userDetail()->create($this->extractDetailData($data));
            $user->syncRoles($roles->pluck('name')->all());

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            DB::afterCommit(function () use ($user): void {
                $freshUser = $user->fresh('roles');

                if ($freshUser) {
                    $this->adminNotificationService->notifyNewRegistration($freshUser);
                }
            });

            return $user->fresh(['roles', 'userDetail']);
        });
    }

    public function updateStatus(User $targetUser, bool $isActive, User $actor): User
    {
        $this->guardStatusChange($targetUser, $isActive, $actor);

        return DB::transaction(function () use ($targetUser, $isActive) {
            $targetUser->update([
                'status' => $isActive ? 1 : 0,
            ]);

            if (!$isActive) {
                $targetUser->tokens()->delete();
                RefreshToken::where('user_id', $targetUser->id)->delete();
            }

            return $targetUser->fresh(['roles', 'userDetail']);
        });
    }

    protected function extractDetailData(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'date_of_birth',
            'bio',
            'gender',
            'country',
            'state',
            'district',
            'local_bodies',
            'street_name',
        ]));
    }

    protected function resolveRoles(array $roleInputs): Collection
    {
        $roles = collect($roleInputs)
            ->map(function ($value) {
                return is_numeric($value)
                    ? Role::query()->find((int) $value)
                    : Role::query()->where('name', (string) $value)->first();
            })
            ->filter()
            ->values();

        if ($roles->count() !== count($roleInputs)) {
            throw ValidationException::withMessages([
                'roles' => ['One or more roles are invalid.'],
            ]);
        }

        return $roles;
    }

    protected function guardRestrictedRoles(Collection $roles, User $actor): void
    {
        if ($actor->hasRole('Super Admin')) {
            return;
        }

        if ($roles->contains(fn (Role $role) => $role->name === 'Super Admin')) {
            throw ValidationException::withMessages([
                'roles' => ['Only a Super Admin can assign the Super Admin role.'],
            ]);
        }
    }

    protected function guardStatusChange(User $targetUser, bool $isActive, User $actor): void
    {
        if (!$actor->hasRole('Super Admin') && $targetUser->hasRole('Super Admin')) {
            throw ValidationException::withMessages([
                'user' => ['Only a Super Admin can change the status of a Super Admin user.'],
            ]);
        }

        if ($isActive) {
            return;
        }

        if ($targetUser->id === $actor->id) {
            throw ValidationException::withMessages([
                'status' => ['You cannot deactivate your own account.'],
            ]);
        }

        if ($targetUser->hasRole('Super Admin') && (int) $targetUser->status === 1) {
            $activeSuperAdminCount = User::query()
                ->role('Super Admin')
                ->where('status', 1)
                ->count();

            if ($activeSuperAdminCount <= 1) {
                throw ValidationException::withMessages([
                    'status' => ['The last active Super Admin cannot be deactivated.'],
                ]);
            }
        }
    }

    protected function generateUserCode(): string
    {
        $currentYear = now()->year;
        $latestId = (User::max('id') ?? 0) + 1;

        return "Opsh-{$currentYear}-{$latestId}";
    }
}
