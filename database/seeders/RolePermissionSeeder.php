<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    private const SYSTEM_PERMISSIONS = [
        'view_menus',
        'create_menus',
        'edit_menus',
        'delete_menus',
        'view_roles',
        'create_roles',
        'edit_roles',
        'delete_roles',
        'view_permissions',
        'edit_permissions',
        'view_employees',
        'edit_employees',
        'manage_all',
    ];

    private const MENU_ACTIONS = [
        'view',
        'create',
        'edit',
        'delete',
        'approve',
        'export',
        'upload',
        'manage',
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::SYSTEM_PERMISSIONS as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $employeeRole = Role::where('name', 'Employee')->where('guard_name', 'web')->first();
        $userRole = Role::where('name', 'User')->where('guard_name', 'web')->first();

        if ($employeeRole && !$userRole) {
            $employeeRole->update(['name' => 'User']);
        }

        foreach (['Super Admin', 'Admin', 'Manager', 'User'] as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        if ($employeeRole = Role::where('name', 'Employee')->where('guard_name', 'web')->first()) {
            User::role('Employee')->get()->each(function (User $user): void {
                $user->assignRole('User');
                $user->removeRole('Employee');
            });

            $employeeRole->delete();
        }

        $menuPermissionBases = Menu::query()
            ->whereNotNull('permission_name')
            ->pluck('permission_name')
            ->filter()
            ->unique()
            ->values();

        $menuPermissions = $menuPermissionBases
            ->flatMap(function ($permissionBase) {
                return collect(self::MENU_ACTIONS)
                    ->map(fn (string $action) => "{$action}_{$permissionBase}");
            })
            ->unique()
            ->values();

        foreach ($menuPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $allPermissionNames = collect(self::SYSTEM_PERMISSIONS)
            ->merge($menuPermissions)
            ->unique()
            ->values()
            ->all();

        Role::findByName('Super Admin')->syncPermissions(Permission::all());

        $adminRestrictedPermissions = [
            'view_employees',
            'create_employees',
            'edit_employees',
            'delete_employees',
            'view_roles',
            'create_roles',
            'edit_roles',
            'delete_roles',
            'view_permissions',
            'edit_permissions',
            'manage_all',
            'view_access_control',
            'create_access_control',
            'edit_access_control',
            'delete_access_control',
            'approve_access_control',
            'export_access_control',
            'upload_access_control',
            'manage_access_control',
            'view_user_management',
            'create_user_management',
            'edit_user_management',
            'delete_user_management',
            'approve_user_management',
            'export_user_management',
            'upload_user_management',
            'manage_user_management',
        ];

        Role::findByName('Admin')->syncPermissions(
            collect($allPermissionNames)
                ->reject(fn (string $permissionName) => in_array($permissionName, $adminRestrictedPermissions, true))
                ->values()
                ->all()
        );

        Role::findByName('Manager')->syncPermissions([
            'view_course_catalog', 'create_course_catalog', 'edit_course_catalog',
            'view_settings', 'edit_settings',
            'view_settings_menu',
            'view_settings_profile',
        ]);

        Role::findByName('User')->syncPermissions([
            'view_course_catalog',
            'view_settings_profile',
        ]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
