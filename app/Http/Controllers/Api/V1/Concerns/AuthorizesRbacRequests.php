<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait AuthorizesRbacRequests
{
    protected function authorizeAnyAbility(
        Request $request,
        array $abilities,
        string $message = 'Unauthorized'
    ): ?JsonResponse {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Super Admin', 'Admin'])) {
            return null;
        }

        foreach ($abilities as $ability) {
            foreach ($this->permissionNameVariants($ability) as $variant) {
                if ($user->can($variant)) {
                    return null;
                }
            }
        }

        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    protected function permissionNameVariants(string $ability): array
    {
        $ability = trim($ability);

        return array_values(array_unique([
            $ability,
            str_replace(' ', '_', $ability),
            str_replace('_', ' ', $ability),
        ]));
    }
}
