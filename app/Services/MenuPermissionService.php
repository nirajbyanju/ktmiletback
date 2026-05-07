<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MenuPermissionService
{
    private const SUPPORTED_ACTIONS = [
        'view',
        'create',
        'edit',
        'delete',
        'approve',
        'export',
        'upload',
        'manage',
    ];

    public function ensureForMenu(Menu $menu, ?string $oldPermissionBase = null): array
    {
        $newPermissionBase = $this->normalizePermissionBase(
            $menu->permission_name ?: $menu->name ?: "menu_{$menu->id}"
        );

        if ($oldPermissionBase && $oldPermissionBase !== $newPermissionBase) {
            $oldPermissionNames = $this->permissionNamesForBase($oldPermissionBase);
            $newPermissionNames = $this->permissionNamesForBase($newPermissionBase);

            foreach ($oldPermissionNames as $index => $oldPermissionName) {
                $permission = Permission::query()->where('name', $oldPermissionName)->first();
                if ($permission) {
                    $permission->update([
                        'name' => $newPermissionNames[$index],
                    ]);
                }
            }
        }

        if ($menu->permission_name !== $newPermissionBase) {
            $menu->permission_name = $newPermissionBase;
            $menu->save();
        }

        foreach ($this->permissionNamesForBase($newPermissionBase) as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $this->permissionNamesForBase($newPermissionBase);
    }

    public function syncRoles(Menu $menu, array $rolePermissions): array
    {
        $allPermissionNames = $this->ensureForMenu($menu);

        foreach ($rolePermissions as $assignment) {
            $role = Role::query()->findOrFail($assignment['role_id']);
            $actions = $this->normalizeActions($assignment['actions'] ?? ['view']);
            $requestedPermissionNames = $this->permissionNamesForBase($menu->permission_name, $actions);

            foreach ($allPermissionNames as $permissionName) {
                if (in_array($permissionName, $requestedPermissionNames, true)) {
                    $role->givePermissionTo($permissionName);
                } elseif ($role->hasPermissionTo($permissionName)) {
                    $role->revokePermissionTo($permissionName);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $allPermissionNames;
    }

    public function deleteForMenu(Menu $menu): void
    {
        if (empty($menu->permission_name)) {
            return;
        }

        Permission::query()
            ->whereIn('name', $this->permissionNamesForBase($menu->permission_name))
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function supportedActions(): array
    {
        return self::SUPPORTED_ACTIONS;
    }

    public function permissionNamesForBase(string $permissionBase, ?array $actions = null): array
    {
        $actions ??= self::SUPPORTED_ACTIONS;
        $permissionBase = $this->normalizePermissionBase($permissionBase);

        return collect($actions)
            ->map(fn (string $action) => "{$action}_{$permissionBase}")
            ->values()
            ->all();
    }

    private function normalizeActions(array $actions): array
    {
        return collect($actions)
            ->map(fn ($action) => Str::slug((string) $action, '_'))
            ->filter(fn (string $action) => in_array($action, self::SUPPORTED_ACTIONS, true))
            ->unique()
            ->values()
            ->all();
    }

    private function normalizePermissionBase(string $permissionBase): string
    {
        return Str::slug($permissionBase, '_');
    }
}
