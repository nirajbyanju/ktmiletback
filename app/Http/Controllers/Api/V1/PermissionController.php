<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesRbacRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends BaseController
{
    use AuthorizesRbacRequests;

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['view_permissions', 'manage_all'])) {
            return $response;
        }

        $permissions = Permission::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission) => $this->formatPermission($permission));

        $grouped = $permissions
            ->groupBy('resource')
            ->map(function ($items, $resource) {
                return [
                    'resource' => $resource,
                    'permissions' => $items->values(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $permissions,
            'grouped' => $grouped,
        ]);
    }

    private function formatPermission(Permission $permission): array
    {
        [$action, $resource] = $this->splitPermissionName($permission->name);

        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
            'action' => $action,
            'resource' => $resource,
        ];
    }

    private function splitPermissionName(string $name): array
    {
        $normalized = str_replace(' ', '_', trim($name));
        $parts = explode('_', $normalized, 2);

        return [
            $parts[0] ?? $normalized,
            $parts[1] ?? $normalized,
        ];
    }
}
