<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Get all users with their roles and permissions
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');

        $query = User::with(['roles', 'permissions']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Get a specific user's permissions (both from roles and direct)
     */
    public function getUserPermissions($userId)
    {
        $user = User::with(['roles.permissions', 'permissions'])->findOrFail($userId);

        // Get permissions from roles
        $rolePermissions = $user->roles->flatMap->permissions->unique('id');
        
        // Get direct permissions
        $directPermissions = $user->permissions;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'role_permissions' => $rolePermissions,
                'direct_permissions' => $directPermissions,
            ]
        ]);
    }

    /**
     * Get user's effective permissions (merged from roles and direct)
     */
    public function getEffectivePermissions($userId)
    {
        $user = User::with(['roles.permissions', 'permissions'])->findOrFail($userId);

        // Get all unique permissions
        $rolePermissions = $user->roles->flatMap->permissions;
        $allPermissions = $rolePermissions->merge($user->permissions)->unique('id');

        return response()->json([
            'success' => true,
            'data' => [
                'permissions' => $allPermissions,
                'permission_slugs' => $allPermissions->pluck('slug')->toArray(),
            ]
        ]);
    }

    /**
     * Assign a direct permission to a user
     */
    public function assignPermission(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'permission_id' => 'required|exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::findOrFail($userId);
        $permission = Permission::findOrFail($request->permission_id);

        // Check if user already has this permission directly
        if ($user->permissions->contains($permission->id)) {
            return response()->json([
                'success' => false,
                'message' => 'User already has this permission'
            ], 400);
        }

        $user->givePermission($permission);

        return response()->json([
            'success' => true,
            'message' => 'Permission assigned successfully',
            'data' => $user->load(['roles', 'permissions'])
        ]);
    }

    /**
     * Revoke a direct permission from a user
     */
    public function revokePermission($userId, $permissionId)
    {
        $user = User::findOrFail($userId);
        $permission = Permission::findOrFail($permissionId);

        // Check if user has this permission directly
        if (!$user->permissions->contains($permission->id)) {
            return response()->json([
                'success' => false,
                'message' => 'User does not have this direct permission'
            ], 400);
        }

        $user->revokePermission($permission);

        return response()->json([
            'success' => true,
            'message' => 'Permission revoked successfully',
            'data' => $user->load(['roles', 'permissions'])
        ]);
    }
}
