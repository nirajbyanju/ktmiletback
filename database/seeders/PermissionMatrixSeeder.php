<?php
// database/seeders/PermissionMatrixSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\PermissionMatrix;
use Illuminate\Support\Str;

class PermissionMatrixSeeder extends Seeder
{
    public function run()
    {
        // Define feature permissions for each role
        $rolePermissions = [
            'Admin' => [
                'dashboard' => ['view' => true],
                'employees' => ['view'=>true,'create'=>true,'edit'=>true,'delete'=>true],
                'menus' => ['view'=>true,'create'=>true,'edit'=>true,'delete'=>true],
                'reports' => ['view'=>true,'export'=>true],
                'settings' => ['view'=>true,'edit'=>true],
                'products' => ['view'=>true,'create'=>true,'edit'=>true,'delete'=>true],
                'orders' => ['view'=>true,'create'=>true,'edit'=>true,'approve'=>true],
                'inventory' => ['view'=>true,'edit'=>true,'upload'=>true],
            ],
            'Manager' => [
                'dashboard'=>['view'=>true],
                'employees'=>['view'=>true],
                'reports'=>['view'=>true,'export'=>true],
                'products'=>['view'=>true,'create'=>true,'edit'=>true],
                'orders'=>['view'=>true,'create'=>true,'edit'=>true],
                'inventory'=>['view'=>true,'edit'=>true],
            ],
            'Employee' => [
                'dashboard'=>['view'=>true],
                'products'=>['view'=>true],
                'orders'=>['view'=>true,'create'=>true],
                'inventory'=>['view'=>true],
            ]
        ];

        foreach ($rolePermissions as $roleName => $features) {
            $role = Role::where('name', $roleName)->first();

            foreach ($features as $feature => $perms) {
                $matrix = PermissionMatrix::create([
                    'feature_name' => $feature,
                    'permission_key' => $feature.'_'.$role->id,
                    'can_view' => $perms['view'] ?? false,
                    'can_create' => $perms['create'] ?? false,
                    'can_edit' => $perms['edit'] ?? false,
                    'can_delete' => $perms['delete'] ?? false,
                    'can_approve' => $perms['approve'] ?? false,
                    'can_export' => $perms['export'] ?? false,
                    'can_upload' => $perms['upload'] ?? false,
                    'can_all' => $perms['all'] ?? false,
                    'role_id' => $role->id,
                ]);

                // Assign Spatie permissions to role
                $this->createSpatiePermissions($matrix, $role);
            }
        }
    }

    private function createSpatiePermissions($matrix, $role)
    {
        $feature = Str::slug($matrix->feature_name, '_');

        $map = [
            'can_view' => "view_{$feature}",
            'can_create' => "create_{$feature}",
            'can_edit' => "edit_{$feature}",
            'can_delete' => "delete_{$feature}",
            'can_approve' => "approve_{$feature}",
            'can_export' => "export_{$feature}",
            'can_upload' => "upload_{$feature}",
            'can_all' => "manage_{$feature}",
        ];

        foreach ($map as $field => $permName) {
            if ($matrix->$field) {
                $permission = Permission::firstOrCreate(['name' => $permName,'guard_name'=>'web']);
                $role->givePermissionTo($permission);
            }
        }
    }
}