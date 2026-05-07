<?php
// app/Http/Controllers/Api/MenuController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesRbacRequests;
use App\Models\Menu;
use App\Services\MenuPermissionService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    use AuthorizesRbacRequests;

    public function __construct(
        private readonly MenuPermissionService $menuPermissionService
    ) {
    }
  
    /**
     * Get menus based on user permissions
     */
    public function getAccessibleMenus(Request $request)
    {
        $user = $request->user();
        
        $menus = Menu::with(['children' => function($query) use ($user) {
            $query->active()->orderBy('order');
        }])
        ->whereNull('parent_id')
        ->active()
        ->orderBy('order')
        ->get()
        ->filter(function($menu) use ($user) {
            // Filter parent menus
            if (!$menu->isAccessibleBy($user)) {
                return false;
            }
            
            // Filter children menus
            $menu->children = $menu->children->filter(function($child) use ($user) {
                return $child->isAccessibleBy($user);
            })->values();
            
            return true;
        })->values();

        return response()->json([
            'success' => true,
            'data' => $menus,
            'meta' => $this->permissionContract(),
        ]);
    }

    /**
     * Get all menus (for admin)
     */
    public function index(Request $request)
    {
        if ($response = $this->authorizeAnyAbility($request, ['view_menus', 'manage_all'])) {
            return $response;
        }

        $menus = Menu::with(['parent', 'creator'])
            ->when($request->has('parent_id'), function($query) use ($request) {
                return $query->where('parent_id', $request->parent_id);
            })
            ->orderBy('order')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $menus,
            'meta' => $this->permissionContract(),
        ]);
    }

    /**
     * Show a single menu
     */
    public function show(Request $request, Menu $menu)
    {
        if ($response = $this->authorizeAnyAbility($request, ['view_menus', 'manage_all'])) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'data' => $menu->load(['parent', 'children', 'creator']),
            'meta' => array_merge($this->permissionContract(), [
                'generated_permissions' => $this->generatedPermissionsFor($menu),
            ]),
        ]);
    }

    /**
     * Store a new menu
     */
    public function store(Request $request)
    {
        if ($response = $this->authorizeAnyAbility($request, ['create_menus', 'manage_all'])) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'route' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:menus,id',
            'order' => 'integer',
            'is_status' => 'boolean',
            'permission_name' => 'nullable|string|max:255',
            'is_public' => 'boolean',
            'role_permissions' => 'sometimes|array',
            'role_permissions.*.role_id' => 'required_with:role_permissions|exists:roles,id',
            'role_permissions.*.actions' => 'sometimes|array',
            'role_permissions.*.actions.*' => ['required', Rule::in(MenuPermissionService::supportedActions())],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['created_by'] = $request->user()->id;
        unset($data['role_permissions']);

        $menu = Menu::create($data);

        if (!$menu->is_public || !empty($menu->permission_name)) {
            $generatedPermissions = $this->menuPermissionService->ensureForMenu($menu);
        } else {
            $generatedPermissions = [];
        }

        if ($request->filled('role_permissions')) {
            $this->menuPermissionService->syncRoles($menu, $request->input('role_permissions', []));
        }

        return response()->json([
            'success' => true,
            'message' => 'Menu created successfully',
            'data' => [
                'menu' => $menu->load('parent'),
                'generated_permissions' => $generatedPermissions,
                'supported_actions' => MenuPermissionService::supportedActions(),
            ]
        ], 201);
    }

    /**
     * Update menu
     */
    public function update(Request $request, Menu $menu)
    {
        if ($response = $this->authorizeAnyAbility($request, ['edit_menus', 'manage_all'])) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'icon' => 'nullable|string|max:50',
            'route' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:menus,id',
            'order' => 'integer',
            'is_status' => 'boolean',
            'permission_name' => 'nullable|string|max:255',
            'is_public' => 'boolean',
            'role_permissions' => 'sometimes|array',
            'role_permissions.*.role_id' => 'required_with:role_permissions|exists:roles,id',
            'role_permissions.*.actions' => 'sometimes|array',
            'role_permissions.*.actions.*' => ['required', Rule::in(MenuPermissionService::supportedActions())],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $oldPermissionBase = $menu->permission_name;
        $data = $request->all();
        unset($data['role_permissions']);
        $menu->update($data);

        if (!$menu->is_public || !empty($menu->permission_name)) {
            $generatedPermissions = $this->menuPermissionService->ensureForMenu($menu, $oldPermissionBase);
        } else {
            $generatedPermissions = [];
        }

        if ($request->filled('role_permissions')) {
            $this->menuPermissionService->syncRoles($menu, $request->input('role_permissions', []));
        }

        return response()->json([
            'success' => true,
            'message' => 'Menu updated successfully',
            'data' => [
                'menu' => $menu->fresh(['parent']),
                'generated_permissions' => $generatedPermissions,
                'supported_actions' => MenuPermissionService::supportedActions(),
            ]
        ]);
    }

    /**
     * Sync role permissions for a menu
     */
    public function syncRolePermissions(Request $request, Menu $menu)
    {
        if ($response = $this->authorizeAnyAbility($request, ['edit_menus', 'edit_roles', 'manage_all'])) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'role_permissions' => 'required|array',
            'role_permissions.*.role_id' => 'required|exists:roles,id',
            'role_permissions.*.actions' => 'sometimes|array',
            'role_permissions.*.actions.*' => ['required', Rule::in(MenuPermissionService::supportedActions())],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $generatedPermissions = $this->menuPermissionService->syncRoles(
            $menu,
            $request->input('role_permissions', [])
        );

        return response()->json([
            'success' => true,
            'message' => 'Menu permissions synced to roles successfully.',
            'data' => [
                'menu' => $menu->fresh(['parent']),
                'generated_permissions' => $generatedPermissions,
                'supported_actions' => MenuPermissionService::supportedActions(),
            ]
        ]);
    }

    /**
     * Delete menu
     */
    public function destroy(Request $request, Menu $menu)
    {
        if ($response = $this->authorizeAnyAbility($request, ['delete_menus', 'manage_all'])) {
            return $response;
        }

        // Check if has children
        if ($menu->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete menu with submenus'
            ], 422);
        }

        $this->menuPermissionService->deleteForMenu($menu);
        $menu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu deleted successfully'
        ]);
    }

    /**
     * Reorder menus
     */
    public function reorder(Request $request)
    {
        if ($response = $this->authorizeAnyAbility($request, ['edit_menus', 'manage_all'])) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'menus' => 'required|array',
            'menus.*.id' => 'required|exists:menus,id',
            'menus.*.order' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        foreach ($request->menus as $menuData) {
            Menu::where('id', $menuData['id'])->update(['order' => $menuData['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Menus reordered successfully'
        ]);
    }

    private function permissionContract(): array
    {
        return [
            'supported_actions' => MenuPermissionService::supportedActions(),
            'request_fields' => [
                'name',
                'icon',
                'route',
                'url',
                'parent_id',
                'order',
                'is_status',
                'permission_name',
                'is_public',
                'role_permissions',
            ],
        ];
    }

    private function generatedPermissionsFor(Menu $menu): array
    {
        if (empty($menu->permission_name) && (bool) $menu->is_public) {
            return [];
        }

        return $this->menuPermissionService->permissionNamesForBase(
            $menu->permission_name ?: $menu->name ?: "menu_{$menu->id}"
        );
    }
}
