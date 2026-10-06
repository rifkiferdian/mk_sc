<?php

namespace App\Services;

class PermissionService
{
    public function can(int $userId, string $permission): bool
    {
        $db = db_connect();
        $roles = $db->table('user_roles')
            ->select('roles.name')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->get()
            ->getResultArray();

        if (in_array('superadmin', array_column($roles, 'name'), true)) {
            return true;
        }

        $fromRoles = $db->table('role_has_permissions')
            ->join('user_roles', 'user_roles.role_id = role_has_permissions.role_id')
            ->join('permissions', 'permissions.id = role_has_permissions.permission_id')
            ->where('user_roles.user_id', $userId)
            ->where('permissions.name', $permission)
            ->countAllResults() > 0;

        if ($fromRoles) {
            return true;
        }

        return $db->table('user_permissions')
            ->join('permissions', 'permissions.id = user_permissions.permission_id')
            ->where('user_permissions.user_id', $userId)
            ->where('permissions.name', $permission)
            ->countAllResults() > 0;
    }
}
