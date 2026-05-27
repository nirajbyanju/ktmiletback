<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserProfileService
{
    public function getProfile(User $user): array
    {
        return $this->formatUser($user->loadMissing(['roles', 'userDetail']));
    }

    public function updateProfile(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {
            $userPayload = array_intersect_key($data, array_flip([
                'first_name',
                'middle_name',
                'last_name',
                'username',
                'email',
                'phone',
            ]));

            if ($userPayload !== []) {
                $user->update($userPayload);
            }

            $detailPayload = array_intersect_key($data, array_flip([
                'date_of_birth',
                'bio',
                'gender',
                'country',
                'state',
                'district',
                'local_bodies',
                'street_name',
            ]));

            if ($detailPayload !== []) {
                UserDetail::updateOrCreate(
                    ['user_id' => $user->id],
                    $detailPayload
                );
            }

            return $this->formatUser($user->fresh()->load(['roles', 'userDetail']));
        });
    }

    public function uploadProfilePicture(User $user, UploadedFile $file): array
    {
        $detail = UserDetail::firstOrCreate(['user_id' => $user->id]);

        if ($detail->profile_picture && !str_starts_with($detail->profile_picture, 'http')) {
            Storage::disk('public')->delete($detail->profile_picture);
        }

        $path = $file->store('profile-pictures', 'public');
        $detail->update(['profile_picture' => $path]);

        return $this->formatUser($user->fresh()->load(['roles', 'userDetail']));
    }

    public function deleteProfilePicture(User $user): array
    {
        $detail = $user->userDetail;

        if ($detail && $detail->profile_picture && !str_starts_with($detail->profile_picture, 'http')) {
            Storage::disk('public')->delete($detail->profile_picture);
        }

        if ($detail) {
            $detail->update(['profile_picture' => null]);
        }

        return $this->formatUser($user->fresh()->load(['roles', 'userDetail']));
    }

    protected function formatUser(User $user): array
    {
        $detail = $user->userDetail;

        return [
            'id'           => $user->id,
            'user_code'    => $user->userCode,
            'first_name'   => $user->first_name,
            'middle_name'  => $user->middle_name,
            'last_name'    => $user->last_name,
            'name'         => $user->display_name,
            'username'     => $user->username,
            'email'        => $user->email,
            'phone'        => $user->phone,
            'status'       => $user->status,
            'has_password' => $user->has_password,
            'roles'        => $user->getRoleNames()->values()->all(),
            'userdetail'   => $detail ? [
                'date_of_birth'       => $detail->date_of_birth?->toDateString(),
                'bio'                 => $detail->bio,
                'gender'              => $detail->gender,
                'country'             => $detail->country,
                'state'               => $detail->state,
                'district'            => $detail->district,
                'local_bodies'        => $detail->local_bodies,
                'street_name'         => $detail->street_name,
                'profilePicture'      => $detail->profile_picture_url,
                'profile_picture_url' => $detail->profile_picture_url,
            ] : null,
            'profile_picture_url' => $detail?->profile_picture_url,
            'created_at'   => $user->created_at?->toISOString(),
            'updated_at'   => $user->updated_at?->toISOString(),
        ];
    }
}
