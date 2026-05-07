<?php
// app/Http/Controllers/Api/EmployeePermissionController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Menu;
use App\Models\PermissionMatrix;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EmployeePermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all employee roles with their permissions
     */
    public function getEmployeeRoles()
    {
        $roles = Role::where('name', '!=', 'Super Admin')
            ->with('permissions')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'employee_count' => User::role($role->name)->count(),
                    'permissions' => $role->permissions->pluck('name')
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    /**
     * Get permission matrix for employee view
     */
    public function getEmployeePermissionMatrix()
    {
        $features = [
            'Dashboard',
            'Employees',
            'Menus',
            'Reports',
            'Settings',
            'Products',
            'Orders',
            'Inventory',
            'Customers',
            'Analytics'
        ];

        $roles = Role::where('name', '!=', 'Super Admin')->get();
        
        $matrix = [];
        foreach ($features as $feature) {
            $featureKey = Str::slug($feature);
            $matrix[$feature] = [];
            
            foreach ($roles as $role) {
                $permission = PermissionMatrix::where('feature_name', $featureKey)
                    ->where('role_id', $role->id)
                    ->first();
                
                $matrix[$feature][$role->name] = [
                    'role_id' => $role->id,
                    'permissions' => $permission ? $permission->getPermissionsArray() : [
                        'view' => false,
                        'create' => false,
                        'edit' => false,
                        'delete' => false,
                        'approve' => false,
                        'export' => false,
                        'upload' => false,
                        'all' => false
                    ]
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $matrix
        ]);
    }

    /**
     * Assign permissions to employee role
     */
    public function assignToEmployeeRole(Request $request, $roleId)
    {
        $validator = Validator::make($request->all(), [
            'feature' => 'required|string',
            'permissions' => 'required|array',
            'permissions.*' => 'in:view,create,edit,delete,approve,export,upload,all'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $role = Role::findOrFail($roleId);
        $feature = Str::slug($request->feature);

        // Update or create permission matrix
        $matrix = PermissionMatrix::updateOrCreate(
            [
                'feature_name' => $feature,
                'role_id' => $role->id
            ],
            [
                'can_view' => in_array('view', $request->permissions) || in_array('all', $request->permissions),
                'can_create' => in_array('create', $request->permissions) || in_array('all', $request->permissions),
                'can_edit' => in_array('edit', $request->permissions) || in_array('all', $request->permissions),
                'can_delete' => in_array('delete', $request->permissions) || in_array('all', $request->permissions),
                'can_approve' => in_array('approve', $request->permissions) || in_array('all', $request->permissions),
                'can_export' => in_array('export', $request->permissions) || in_array('all', $request->permissions),
                'can_upload' => in_array('upload', $request->permissions) || in_array('all', $request->permissions),
                'can_all' => in_array('all', $request->permissions),
                'permission_key' => $feature . '_' . $role->id
            ]
        );

        // Sync with Spatie permissions
        $this->syncRolePermissions($role, $feature, $matrix);

        return response()->json([
            'success' => true,
            'message' => 'Permissions assigned to employee role successfully',
            'data' => $matrix
        ]);
    }

    /**
     * Get employees by role
     */
    public function getEmployeesByRole($roleId)
    {
        $role = Role::findOrFail($roleId);
        
        $employees = User::role($role->name)
            ->select('id', 'name', 'email', 'created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $employees,
            'role' => $role->name
        ]);
    }

    /**
     * Sync role permissions with Spatie
     */
    private function syncRolePermissions($role, $feature, PermissionMatrix $matrix)
    {
        $permissionMappings = [
            'can_view' => "view {$feature}",
            'can_create' => "create {$feature}",
            'can_edit' => "edit {$feature}",
            'can_delete' => "delete {$feature}",
            'can_approve' => "approve {$feature}",
            'can_export' => "export {$feature}",
            'can_upload' => "upload {$feature}",
            'can_all' => "manage {$feature}",
        ];

        foreach ($permissionMappings as $field => $permissionName) {
            if ($matrix->$field) {
                $permission = Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web'
                ]);
                $role->givePermissionTo($permission);
            } else {
                $permission = Permission::where('name', $permissionName)->first();
                if ($permission) {
                    $role->revokePermissionTo($permission);
                }
            }
        }
    }
}