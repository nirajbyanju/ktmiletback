<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\UpdateUserProfileRequest;
use App\Services\UserProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserProfileController extends BaseController
{
    public function __construct(
        private readonly UserProfileService $userProfileService,
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->userProfileService->getProfile($request->user()),
        ]);
    }

    public function update(UpdateUserProfileRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $this->userProfileService->updateProfile(
                $request->user(),
                $request->validated()
            ),
        ]);
    }

    public function setPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        $user = $request->user();

        $user->forceFill([
            'password'     => Hash::make($validated['password']),
            'has_password' => true,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Password set successfully.',
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password'      => 'required|string',
            'new_password'          => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The current password is incorrect.',
                'errors'  => ['current_password' => ['The current password is incorrect.']],
            ], 422);
        }

        $user->forceFill(['password' => Hash::make($validated['new_password'])])->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ]);
    }
}
