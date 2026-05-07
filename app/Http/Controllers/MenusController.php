<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\V1\BaseController;
use App\Services\UserMenuService;
use Illuminate\Http\Request;

class MenusController extends BaseController
{
    public function __construct(
        private readonly UserMenuService $userMenuService
    ) {
    }

    public function getMenu(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $this->userMenuService->getForUser($user),
            'message' => 'Menus retrieved successfully.',
        ]);
    }
}
