<?php

// database/seeders/PermissionMatrixSeeder.php

namespace Database\Seeders;

use App\Models\PermissionMatrix;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionMatrixSeeder extends Seeder
{
    public function run()
    {
        PermissionMatrix::whereIn('feature_name', [
            'dashboard',
            'reports',
            'products',
            'orders',
            'inventory',
        ])->delete();

        $rolePermissions = [
            'Admin' => [
                'menus' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                'settings' => ['view' => true, 'edit' => true],
                'course_catalog' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                'invoices' => ['view' => true, 'create' => true, 'edit' => true, 'approve' => true],
                'enrollments' => ['view' => true, 'create' => true, 'edit' => true],
            ],
            'User' => [
                'course_catalog' => ['view' => true],
                'enrollments' => ['view' => true],
            ],
        ];

        foreach ($rolePermissions as $roleName => $features) {
            $role = Role::where('name', $roleName)->first();

            if (! $role) {
                continue;
            }

            foreach ($features as $feature => $perms) {
                $matrix = PermissionMatrix::updateOrCreate(
                    [
                        'feature_name' => $feature,
                        'role_id' => $role->id,
                    ],
                    [
                        'permission_key' => $feature.'_'.$role->id,
                        'can_view' => $perms['view'] ?? false,
                        'can_create' => $perms['create'] ?? false,
                        'can_edit' => $perms['edit'] ?? false,
                        'can_delete' => $perms['delete'] ?? false,
                        'can_approve' => $perms['approve'] ?? false,
                        'can_export' => $perms['export'] ?? false,
                        'can_upload' => $perms['upload'] ?? false,
                        'can_all' => $perms['all'] ?? false,
                    ]
                );

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
                $permission = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
                $role->givePermissionTo($permission);
            }
        }
    }
}
