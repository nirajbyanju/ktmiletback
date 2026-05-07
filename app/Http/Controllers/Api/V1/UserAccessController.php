<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesRbacRequests;
use App\Http\Requests\StoreManagedUserRequest;
use App\Http\Requests\UpdateManagedUserStatusRequest;
use App\Models\User;
use App\Services\UserAdministrationService;
use App\Services\UserMenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserAccessController extends BaseController
{
    use AuthorizesRbacRequests;

    public function __construct(
        private readonly UserMenuService $userMenuService,
        private readonly UserAdministrationService $userAdministrationService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['view_employees', 'view_roles', 'manage_all'])) {
            return $response;
        }

        $search = trim((string) $request->get('search'));

        $users = User::query()
            ->select('id', 'userCode', 'first_name', 'middle_name', 'last_name', 'username', 'email', 'phone', 'status', 'created_at')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', (int) $request->get('status'));
            })
            ->orderBy('first_name')
            ->paginate((int) $request->get('per_page', 15));

        $users->getCollection()->transform(fn (User $user) => $this->formatUserSummary($user));

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['view_employees', 'view_roles', 'manage_all'])) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'data' => $this->buildAccessPayload($user),
        ]);
    }

    public function store(StoreManagedUserRequest $request): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['create_employees', 'edit_roles', 'manage_all'])) {
            return $response;
        }

        $user = $this->userAdministrationService->createUser($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $this->buildAccessPayload($user),
        ], 201);
    }

    public function syncRoles(Request $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['edit_employees', 'edit_roles', 'manage_all'])) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'roles' => 'required|array',
            'roles.*' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $roles = $this->resolveRoles($request->input('roles', []));

        if ($response = $this->guardPrivilegedUserMutation($request, $user, $roles)) {
            return $response;
        }

        $user->syncRoles($roles->pluck('name')->all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'User roles updated successfully.',
            'data' => $this->buildAccessPayload($user->fresh()),
        ]);
    }

    public function syncPermissions(Request $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['edit_permissions', 'manage_all'])) {
            return $response;
        }

        if ($response = $this->guardPrivilegedUserMutation($request, $user)) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $permissions = $this->resolvePermissions($request->input('permissions', []));
        $user->syncPermissions($permissions->pluck('name')->all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'User direct permissions updated successfully.',
            'data' => $this->buildAccessPayload($user->fresh()),
        ]);
    }

    public function updateStatus(UpdateManagedUserStatusRequest $request, User $user): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['edit_employees', 'manage_all'])) {
            return $response;
        }

        $updatedUser = $this->userAdministrationService->updateStatus(
            $user,
            (bool) $request->boolean('status'),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => $updatedUser->status ? 'User activated successfully.' : 'User deactivated successfully.',
            'data' => $this->buildAccessPayload($updatedUser),
        ]);
    }

    private function buildAccessPayload(User $user): array
    {
        $user->loadMissing('roles', 'userDetail');

        return [
            'user' => $this->formatUserSummary($user),
            'roles' => $user->getRoleNames()->values(),
            'direct_permissions' => $user->getDirectPermissions()->pluck('name')->sort()->values(),
            'all_permissions' => $user->getAllPermissions()->pluck('name')->sort()->values(),
            'detail' => [
                'date_of_birth' => optional($user->userDetail?->date_of_birth)->toDateString(),
                'bio' => $user->userDetail?->bio,
                'profile_picture' => $user->userDetail?->profile_picture,
                'profile_picture_url' => $user->userDetail?->profile_picture_url,
                'gender' => $user->userDetail?->gender,
                'country' => $user->userDetail?->country,
                'state' => $user->userDetail?->state,
                'district' => $user->userDetail?->district,
                'local_bodies' => $user->userDetail?->local_bodies,
                'street_name' => $user->userDetail?->street_name,
            ],
            'menus' => $this->userMenuService->getForUser($user),
        ];
    }

    private function formatUserSummary(User $user): array
    {
        $name = collect([
            $user->first_name,
            $user->middle_name,
            $user->last_name,
        ])->filter()->implode(' ');

        return [
            'id' => $user->id,
            'user_code' => $user->userCode,
            'name' => $name,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'is_active' => (bool) $user->status,
            'roles' => $user->getRoleNames()->values(),
            'direct_permissions' => $user->getDirectPermissions()->pluck('name')->sort()->values(),
            'created_at' => $user->created_at?->toISOString(),
        ];
    }

    private function guardPrivilegedUserMutation(
        Request $request,
        ?User $targetUser = null,
        ?Collection $roles = null
    ): ?JsonResponse {
        $actor = $request->user();

        if (!$actor || $actor->hasRole('Super Admin')) {
            return null;
        }

        if ($targetUser && $targetUser->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Only a Super Admin can modify a Super Admin user.',
            ], 403);
        }

        if ($roles && $roles->contains(fn (Role $role) => $role->name === 'Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Only a Super Admin can assign the Super Admin role.',
            ], 403);
        }

        return null;
    }

    private function resolveRoles(array $roleInputs): Collection
    {
        $roles = collect($roleInputs)
            ->map(function ($value) {
                return is_numeric($value)
                    ? Role::query()->find((int) $value)
                    : Role::query()->where('name', (string) $value)->first();
            })
            ->filter()
            ->values();

        if ($roles->count() !== count($roleInputs)) {
            throw ValidationException::withMessages([
                'roles' => ['One or more roles are invalid.'],
            ]);
        }

        return $roles;
    }

    private function resolvePermissions(array $permissionInputs): Collection
    {
        $permissions = collect($permissionInputs)
            ->map(function ($value) {
                return is_numeric($value)
                    ? Permission::query()->find((int) $value)
                    : Permission::query()->where('name', (string) $value)->first();
            })
            ->filter()
            ->values();

        if ($permissions->count() !== count($permissionInputs)) {
            throw ValidationException::withMessages([
                'permissions' => ['One or more permissions are invalid.'],
            ]);
        }

        return $permissions;
    }
}
