<?php

namespace App\Services;

class PermissionService
{
    /** @var array<int, list<string>> */
    private static array $rolesByUser = [];

    /** @var array<string, bool> */
    private static array $decisions = [];

    public function can(int $userId, string $permission): bool
    {
        $cacheKey = $userId . ':' . $permission;
        if (array_key_exists($cacheKey, self::$decisions)) {
            return self::$decisions[$cacheKey];
        }

        $db = db_connect();
        if (! array_key_exists($userId, self::$rolesByUser)) {
            self::$rolesByUser[$userId] = array_column(
                $db->table('user_roles')
                    ->select('roles.name')
                    ->join('roles', 'roles.id = user_roles.role_id')
                    ->where('user_roles.user_id', $userId)
                    ->get()
                    ->getResultArray(),
                'name'
            );
        }

        if (in_array('superadmin', self::$rolesByUser[$userId], true)) {
            return self::$decisions[$cacheKey] = true;
        }

        $fromRoles = $db->table('role_has_permissions')
            ->join('user_roles', 'user_roles.role_id = role_has_permissions.role_id')
            ->join('permissions', 'permissions.id = role_has_permissions.permission_id')
            ->where('user_roles.user_id', $userId)
            ->where('permissions.name', $permission)
            ->countAllResults() > 0;

        if ($fromRoles) {
            return self::$decisions[$cacheKey] = true;
        }

        return self::$decisions[$cacheKey] = $db->table('user_permissions')
            ->join('permissions', 'permissions.id = user_permissions.permission_id')
            ->where('user_permissions.user_id', $userId)
            ->where('permissions.name', $permission)
            ->countAllResults() > 0;
    }
}
