<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserProfileService
{
    public function getProfile(User $user): array
    {
        return $this->formatUser($user->loadMissing('roles'));
    }

    public function updateProfile(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {
            $payload = array_intersect_key($data, array_flip([
                'first_name',
                'middle_name',
                'last_name',
                'username',
                'email',
                'phone',
            ]));

            if ($payload !== []) {
                $user->update($payload);
            }

            return $this->formatUser($user->fresh()->load('roles'));
        });
    }

    protected function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'user_code' => $user->userCode,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'name' => $user->display_name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'roles' => $user->getRoleNames()->values()->all(),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
        ];
    }
}
