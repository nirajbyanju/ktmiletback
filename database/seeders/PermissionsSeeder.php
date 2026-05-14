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

        Permission::whereIn('name', [
            'view_dashboard',
            'view_reports',
            'export_reports',
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
        ])->delete();

        $permissions = [
            'view_employees',
            'create_employees',
            'edit_employees',
            'delete_employees',
            'view_menus',
            'create_menus',
            'edit_menus',
            'delete_menus',
            'view_settings',
            'edit_settings',
            'view_course_catalog',
            'create_course_catalog',
            'edit_course_catalog',
            'delete_course_catalog',
            'view_invoices',
            'create_invoices',
            'edit_invoices',
            'approve_invoices',
            'view_enrollments',
            'create_enrollments',
            'edit_enrollments',
            'manage_all'
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }
}
