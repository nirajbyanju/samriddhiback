<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesRbacRequests;
use App\Models\User;
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
        private readonly UserMenuService $userMenuService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        if ($response = $this->authorizeAnyAbility($request, ['view_employees', 'view_roles', 'manage_all'])) {
            return $response;
        }

        $search = trim((string) $request->get('search'));

        $users = User::query()
            ->select('id', 'first_name', 'middle_name', 'last_name', 'username', 'email', 'status', 'created_at')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
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

    private function buildAccessPayload(User $user): array
    {
        $user->load('roles');

        return [
            'user' => $this->formatUserSummary($user),
            'roles' => $user->getRoleNames()->values(),
            'direct_permissions' => $user->getDirectPermissions()->pluck('name')->sort()->values(),
            'all_permissions' => $user->getAllPermissions()->pluck('name')->sort()->values(),
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
            'name' => $name,
            'username' => $user->username,
            'email' => $user->email,
            'status' => $user->status,
            'roles' => $user->getRoleNames()->values(),
            'direct_permissions' => $user->getDirectPermissions()->pluck('name')->sort()->values(),
            'created_at' => $user->created_at?->toISOString(),
        ];
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
