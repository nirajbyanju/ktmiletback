<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_dashboard',
            'view_employees',
            'create_employees',
            'edit_employees',
            'delete_employees',
            'view_menus',
            'create_menus',
            'edit_menus',
            'delete_menus',
            'view_reports',
            'export_reports',
            'view_settings',
            'edit_settings',
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'view_orders',
            'create_orders',
            'edit_orders',
            'approve_orders',
            'view_inventory',
            'edit_inventory',
            'upload_inventory',
            'manage_all'
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }
}
