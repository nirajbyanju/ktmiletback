<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesRbacRequests;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends BaseController
{
    use AuthorizesRbacRequests;

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['view_roles', 'manage_all'])) {
            return $response;
        }

        $roles = Role::query()
            ->with('permissions')
            ->orderBy('name')
            ->paginate((int) $request->get('per_page', 15));

        $roles->getCollection()->transform(fn (Role $role) => $this->formatRole($role));

        return response()->json([
            'success' => true,
            'data' => $roles->items(),
            'pagination' => [
                'total' => $roles->total(),
                'per_page' => $roles->perPage(),
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['create_roles', 'manage_all'])) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::create([
            'name' => trim((string) $request->input('name')),
            'guard_name' => 'web',
        ]);

        if ($request->has('permissions')) {
            $permissions = $this->resolvePermissions($request->input('permissions', []));
            $role->syncPermissions($permissions->pluck('name')->all());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'data' => $this->formatRole($role->load('permissions')),
        ], 201);
    }

    public function show(Request $request, Role $role): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['view_roles', 'manage_all'])) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatRole($role->load('permissions')),
        ]);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['edit_roles', 'manage_all'])) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'sometimes|array',
            'permissions.*' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($role->name === 'Super Admin' && $request->filled('name') && $request->input('name') !== $role->name) {
            return response()->json([
                'success' => false,
                'message' => 'Super Admin role name cannot be changed.',
            ], 422);
        }

        if ($request->filled('name')) {
            $role->name = trim((string) $request->input('name'));
            $role->save();
        }

        if ($request->has('permissions')) {
            $permissions = $this->resolvePermissions($request->input('permissions', []));
            $role->syncPermissions($permissions->pluck('name')->all());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully.',
            'data' => $this->formatRole($role->load('permissions')),
        ]);
    }

    public function destroy(Request $request, Role $role): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['delete_roles', 'manage_all'])) {
            return $response;
        }

        if ($role->name === 'Super Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Super Admin role cannot be deleted.',
            ], 422);
        }

        if (User::role($role->name)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a role that is assigned to users.',
            ], 422);
        }

        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully.',
        ]);
    }

    private function resolvePermissions(array $permissionInputs): Collection
    {
        $permissions = collect($permissionInputs)
            ->map(function ($value) {
                return is_numeric($value)
                    ? Permission::query()->find((int) $value)
                    : Permission::query()->where('name', (string) $value)->first();
            })
            ->filter()
            ->values();

        if ($permissions->count() !== count($permissionInputs)) {
            throw ValidationException::withMessages([
                'permissions' => ['One or more permissions are invalid.'],
            ]);
        }

        return $permissions;
    }

    private function formatRole(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions
                ->pluck('name')
                ->sort()
                ->values(),
            'users_count' => User::role($role->name)->count(),
            'created_at' => $role->created_at?->toISOString(),
            'updated_at' => $role->updated_at?->toISOString(),
        ];
    }
}
