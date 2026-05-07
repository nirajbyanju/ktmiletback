<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserProfileService
{
    public function getProfile(User $user): array
    {
        return $this->formatUser($user->loadMissing('userDetail', 'roles'));
    }

    public function updateProfile(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {
            $userFields = [
                'first_name',
                'middle_name',
                'last_name',
                'username',
                'email',
                'phone',
            ];

            $detailFields = [
                'date_of_birth',
                'bio',
                'gender',
                'country',
                'state',
                'district',
                'local_bodies',
                'street_name',
            ];

            $userPayload = array_intersect_key($data, array_flip($userFields));
            $detailPayload = array_intersect_key($data, array_flip($detailFields));

            if ($userPayload !== []) {
                $user->update($userPayload);
            }

            if ($detailPayload !== []) {
                $user->userDetail()->updateOrCreate(
                    ['user_id' => $user->id],
                    $detailPayload
                );
            }

            return $this->formatUser($user->fresh()->load('userDetail', 'roles'));
        });
    }

    public function updateProfilePicture(User $user, UploadedFile $file): array
    {
        $storedPath = $file->store('profile-pictures', 'public');
        $previousPath = $this->normalizePublicPath(optional($user->userDetail)->profile_picture);

        try {
            DB::transaction(function () use ($user, $storedPath) {
                $user->userDetail()->updateOrCreate(
                    ['user_id' => $user->id],
                    ['profile_picture' => $storedPath]
                );
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storedPath);
            throw $exception;
        }

        if ($previousPath && $previousPath !== $storedPath) {
            Storage::disk('public')->delete($previousPath);
        }

        return $this->formatUser($user->fresh()->load('userDetail', 'roles'));
    }

    public function deleteProfilePicture(User $user): array
    {
        $detail = $user->userDetail;

        if (!$detail) {
            return $this->formatUser($user->loadMissing('userDetail', 'roles'));
        }

        $previousPath = $this->normalizePublicPath($detail->profile_picture);

        DB::transaction(function () use ($detail) {
            $detail->update([
                'profile_picture' => null,
            ]);
        });

        if ($previousPath) {
            Storage::disk('public')->delete($previousPath);
        }

        return $this->formatUser($user->fresh()->load('userDetail', 'roles'));
    }

    protected function formatUser(User $user): array
    {
        $detail = $user->userDetail ?: new UserDetail([
            'user_id' => $user->id,
        ]);

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
            'detail' => [
                'user_id' => $detail->user_id ?? $user->id,
                'date_of_birth' => optional($detail->date_of_birth)->toDateString(),
                'bio' => $detail->bio,
                'profile_picture' => $detail->profile_picture,
                'profile_picture_url' => $detail->profile_picture_url,
                'gender' => $detail->gender,
                'country' => $detail->country,
                'state' => $detail->state,
                'district' => $detail->district,
                'local_bodies' => $detail->local_bodies,
                'street_name' => $detail->street_name,
            ],
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
        ];
    }

    protected function normalizePublicPath(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            $path = parse_url($value, PHP_URL_PATH);

            if (is_string($path) && Str::startsWith($path, '/storage/')) {
                return ltrim(Str::after($path, '/storage/'), '/');
            }

            return null;
        }

        if (Str::startsWith($value, 'storage/')) {
            return Str::after($value, 'storage/');
        }

        return ltrim($value, '/');
    }
}
