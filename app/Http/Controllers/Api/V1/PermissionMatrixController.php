<?php
// app/Http/Controllers/Api/PermissionMatrixController.php

namespace App\Http\Controllers\Api\V1;

use App\Models\PermissionMatrix;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionMatrixResource;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Menu;
use Illuminate\Support\Facades\Auth;
class PermissionMatrixController extends Controller
{

    /**
     * Get permission matrix with employee-specific data
     */
    public function index(Request $request)
    {
    $user = Auth::user(); 

        
        // Check if user has permission to view permission matrix
        // if (!$user->can('view permissions')) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Unauthorized'
        //     ], 403);
        // }

        $query = PermissionMatrix::with('role');

        if ($request->has('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->has('feature_name')) {
            $query->where('feature_name', 'like', '%' . $request->feature_name . '%');
        }

        $permissions = $query->paginate($request->get('per_page', 15));

        // Get employee counts per role
        $employeeCounts = User::selectRaw('roles.name as role_name, count(*) as total')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->groupBy('roles.name')
            ->pluck('total', 'role_name');

        return response()->json([
            'success' => true,
            'data' => PermissionMatrixResource::collection($permissions),
            'employee_counts' => $employeeCounts,
            'meta' => [
                'total' => $permissions->total(),
                'per_page' => $permissions->perPage(),
                'current_page' => $permissions->currentPage(),
                'last_page' => $permissions->lastPage(),
            ]
        ]);
    }

    /**
     * Get matrix grouped by feature for easy management
     */
    public function getMatrixGrouped()
    {
        $user = request()->user();
        
        if (!$user->can('view permissions')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $roles = Role::all();
        $features = PermissionMatrix::all()->groupBy('feature_name');
        
        $matrix = [];
        foreach ($features as $featureName => $featurePermissions) {
            $matrix[$featureName] = [
                'feature' => $featureName,
                'permissions' => []
            ];
            
            foreach ($featurePermissions as $permission) {
                $matrix[$featureName]['permissions'][$permission->role->name] = [
                    'id' => $permission->id,
                    'role_id' => $permission->role_id,
                    'role_name' => $permission->role->name,
                    'can_view' => $permission->can_view,
                    'can_create' => $permission->can_create,
                    'can_edit' => $permission->can_edit,
                    'can_delete' => $permission->can_delete,
                    'can_approve' => $permission->can_approve,
                    'can_export' => $permission->can_export,
                    'can_upload' => $permission->can_upload,
                    'can_all' => $permission->can_all,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $matrix,
            'roles' => $roles->pluck('name')
        ]);
    }

    /**
     * Store permission matrix for employee roles
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if (!$user->can('create permissions')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'feature_name' => 'required|string|max:255',
            'role_id' => 'required|exists:roles,id',
            'can_view' => 'boolean',
            'can_create' => 'boolean',
            'can_edit' => 'boolean',
            'can_delete' => 'boolean',
            'can_approve' => 'boolean',
            'can_export' => 'boolean',
            'can_upload' => 'boolean',
            'can_all' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if combination already exists
        $exists = PermissionMatrix::where('feature_name', $request->feature_name)
            ->where('role_id', $request->role_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Permission matrix for this feature and role already exists'
            ], 409);
        }

        // Generate unique permission key
        $permissionKey = Str::slug($request->feature_name) . '_' . $request->role_id;

        // Create permission matrix
        $matrix = PermissionMatrix::create(array_merge(
            $request->all(),
            ['permission_key' => $permissionKey]
        ));

        // Sync with Spatie permissions
        $this->syncSpatiePermissions($matrix);

        // Update menu permissions if feature is 'menus'
        if ($request->feature_name === 'menus') {
            $this->syncMenuPermissions($matrix);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission matrix created successfully',
            'data' => new PermissionMatrixResource($matrix)
        ], 201);
    }

    /**
     * Update permission matrix
     */
    public function update(Request $request, PermissionMatrix $permissionMatrix)
    {
        $user = $request->user();
        
        if (!$user->can('edit permissions')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'can_view' => 'boolean',
            'can_create' => 'boolean',
            'can_edit' => 'boolean',
            'can_delete' => 'boolean',
            'can_approve' => 'boolean',
            'can_export' => 'boolean',
            'can_upload' => 'boolean',
            'can_all' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $permissionMatrix->update($request->all());

        // Sync with Spatie permissions
        $this->syncSpatiePermissions($permissionMatrix);

        // Update menu permissions if feature is 'menus'
        if ($permissionMatrix->feature_name === 'menus') {
            $this->syncMenuPermissions($permissionMatrix);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission matrix updated successfully',
            'data' => new PermissionMatrixResource($permissionMatrix)
        ]);
    }

    /**
     * Get permissions for employee role
     */
    public function getEmployeePermissions(Request $request, $roleId)
    {
        $user = $request->user();
        
        $permissions = PermissionMatrix::where('role_id', $roleId)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->feature_name => $item->getPermissionsArray()];
            });

        // Get menus accessible by this role
        $role = Role::find($roleId);
        $menus = Menu::with('children')
            ->whereNull('parent_id')
            ->active()
            ->get()
            ->map(function ($menu) use ($role) {
                $menu->accessible = $this->isMenuAccessibleByRole($menu, $role);
                if ($menu->children->isNotEmpty()) {
                    $menu->children = $menu->children->map(function ($child) use ($role) {
                        $child->accessible = $this->isMenuAccessibleByRole($child, $role);
                        return $child;
                    });
                }
                return $menu;
            });

        return response()->json([
            'success' => true,
            'data' => [
                'feature_permissions' => $permissions,
                'menu_permissions' => $menus
            ]
        ]);
    }

    /**
     * Sync with Spatie permissions
     */
    private function syncSpatiePermissions(PermissionMatrix $matrix)
    {
        $role = Role::find($matrix->role_id);
        $feature = Str::slug($matrix->feature_name);

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

    /**
     * Sync menu permissions based on matrix
     */
    private function syncMenuPermissions(PermissionMatrix $matrix)
    {
        $role = Role::find($matrix->role_id);
        
        // Get all menus that should be accessible based on permissions
        $menus = Menu::all();
        
        foreach ($menus as $menu) {
            if ($matrix->can_all || $matrix->can_view) {
                // If role can view menus, assign specific menu permissions
                $permissionName = "view menu-{$menu->id}";
                $permission = Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web'
                ]);
                $role->givePermissionTo($permission);
                
                // Update menu's permission_name if needed
                if (empty($menu->permission_name)) {
                    $menu->update(['permission_name' => $permissionName]);
                }
            }
        }
    }

    /**
     * Check if menu is accessible by role
     */
    private function isMenuAccessibleByRole(Menu $menu, Role $role): bool
    {
        if ($role->hasPermissionTo('manage menus')) {
            return true;
        }

        if (!empty($menu->permission_name)) {
            return $role->hasPermissionTo($menu->permission_name);
        }

        return $menu->is_public ?? false;
    }

    /**
     * Bulk update permissions for employee roles
     */
    public function bulkUpdateForEmployee(Request $request)
    {
        $user = $request->user();
        
        if (!$user->can('edit permissions')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'required|array',
            'permissions.*.feature_name' => 'required|string',
            'permissions.*.can_view' => 'boolean',
            'permissions.*.can_create' => 'boolean',
            'permissions.*.can_edit' => 'boolean',
            'permissions.*.can_delete' => 'boolean',
            'permissions.*.can_approve' => 'boolean',
            'permissions.*.can_export' => 'boolean',
            'permissions.*.can_upload' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $updated = [];
        foreach ($request->permissions as $permData) {
            $matrix = PermissionMatrix::updateOrCreate(
                [
                    'feature_name' => $permData['feature_name'],
                    'role_id' => $request->role_id
                ],
                [
                    'can_view' => $permData['can_view'] ?? false,
                    'can_create' => $permData['can_create'] ?? false,
                    'can_edit' => $permData['can_edit'] ?? false,
                    'can_delete' => $permData['can_delete'] ?? false,
                    'can_approve' => $permData['can_approve'] ?? false,
                    'can_export' => $permData['can_export'] ?? false,
                    'can_upload' => $permData['can_upload'] ?? false,
                    'permission_key' => Str::slug($permData['feature_name']) . '_' . $request->role_id
                ]
            );
            
            $this->syncSpatiePermissions($matrix);
            $updated[] = $matrix;
        }

        return response()->json([
            'success' => true,
            'message' => 'Employee permissions updated successfully',
            'data' => PermissionMatrixResource::collection($updated)
        ]);
    }
}