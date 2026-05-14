<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\UpdateUserProfileRequest;
use App\Services\UserProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
