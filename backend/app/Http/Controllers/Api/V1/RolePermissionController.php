<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Permission\UpdateRolePermissionRequest;
use App\Http\Requests\Api\V1\Permission\UserPermissionOverrideRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    public function index(Request $request)
    {
        // Only Admin can access
        abort_unless($request->user()->hasRole('admin'), 403);

        $roleName = $request->route('role') ?? $request->query('role');

        abort_unless(in_array($roleName, ['karyawan', 'kurir'], true), 404);

        $role = Role::findByName($roleName, 'web');
        $permissions = Permission::query()->where('guard_name', 'web')->get();

        $rolePermissions = [];
        foreach ($permissions as $permission) {
            $rolePermissions[$permission->name] = [
                'name' => $permission->name,
                'granted' => $role->hasPermissionTo($permission),
            ];
        }

        return response()->json([
            'success' => true,
            'role' => $roleName,
            'permissions' => $rolePermissions,
        ]);
    }

    public function update(UpdateRolePermissionRequest $request, $roleName)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        abort_unless(in_array($roleName, ['karyawan', 'kurir'], true), 404);

        $role = Role::findByName($roleName, 'web');

        $validated = $request->validated();
        $permissionsToUpdate = $validated['permissions'] ?? [];

        DB::transaction(function () use ($role, $permissionsToUpdate) {
            foreach ($permissionsToUpdate as $permission) {
                $permName = $permission['name'];
                $status = $permission['status'];

                if ($status === 'GRANT') {
                    $role->givePermissionTo($permName);
                } elseif ($status === 'REVOKE' || $status === 'DENY') {
                    $role->revokePermissionTo($permName);
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Permission role berhasil diupdate.',
            'role' => $roleName,
        ]);
    }

    public function updateUser(UserPermissionOverrideRequest $request, $userId)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $user = User::findOrFail($userId);

        // Admin cannot edit own or owner permissions
        if ($user->hasRole('admin') || $user->hasRole('owner')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat mengubah permission akun Admin atau Owner.',
            ], 403);
        }

        $validated = $request->validated();
        $permissionName = $validated['permission_name'];
        $status = $validated['status']; // GRANT, DENY, or INHERIT

        DB::transaction(function () use ($user, $permissionName, $status) {
            if ($status === 'INHERIT') {
                // Remove any override, so user inherits from role
                DB::table('user_permission_overrides')
                    ->where('user_id', $user->id)
                    ->where('permission_name', $permissionName)
                    ->delete();
            } elseif ($status === 'GRANT') {
                DB::table('user_permission_overrides')
                    ->upsert(
                        [
                            [
                                'user_id' => $user->id,
                                'permission_name' => $permissionName,
                                'status' => 'GRANT',
                            ]
                        ],
                        ['user_id', 'permission_name'],
                        ['status' => 'GRANT']
                    );
            } elseif ($status === 'DENY') {
                DB::table('user_permission_overrides')
                    ->upsert(
                        [
                            [
                                'user_id' => $user->id,
                                'permission_name' => $permissionName,
                                'status' => 'DENY',
                            ]
                        ],
                        ['user_id', 'permission_name'],
                        ['status' => 'DENY']
                    );
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => 'Permission akun berhasil diupdate.',
            'user' => $user->name,
            'permission' => $permissionName,
            'status' => $status,
        ]);
    }

    public function showUserOverrides(Request $request, $userId)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $user = User::findOrFail($userId);

        // Admin cannot view permissions for other Admin or Owner
        if ($user->hasRole('admin') || $user->hasRole('owner')) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Admin atau Owner tidak dapat dikelola.',
            ], 403);
        }

        // Get user's role-based permissions
        $overrides = DB::table('user_permission_overrides')
            ->where('user_id', $userId)
            ->pluck('status', 'permission_name');

        $permissions = Permission::query()->get()->mapWithKeys(function ($permission) use ($user, $overrides) {
            $status = $overrides->get($permission->name) ?? 'INHERIT';

            return [
                $permission->name => [
                    'name' => $permission->name,
                    'status' => $status,
                    'granted' => $user->hasPermissionTo($permission->name),
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'user' => $userId,
            'permissions' => $permissions,
        ]);
    }
}
