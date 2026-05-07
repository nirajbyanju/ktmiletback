<?php

namespace Database\Seeders;

use App\Models\Menu;
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

        foreach (['Super Admin', 'Admin', 'Manager', 'Employee'] as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
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

        Role::findByName('Admin')->syncPermissions($allPermissionNames);

        Role::findByName('Manager')->syncPermissions([
            'view_dashboard',
            'view_property', 'create_property', 'edit_property',
            'view_field_visit', 'create_field_visit', 'edit_field_visit',
            'view_property_inquiry', 'create_property_inquiry', 'edit_property_inquiry',
            'view_blog', 'create_blog', 'edit_blog',
            'view_settings', 'edit_settings',
            'view_settings_option',
        ]);

        Role::findByName('Employee')->syncPermissions([
            'view_dashboard',
            'view_property',
            'view_field_visit',
            'view_property_inquiry',
            'view_blog',
        ]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
