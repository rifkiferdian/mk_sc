<?php

namespace App\Services;

use App\Models\UserModel;

class AuthService
{
    public function attempt(string $identity, string $password): ?array
    {
        $user = (new UserModel())
            ->groupStart()
            ->where('username', $identity)
            ->orWhere('email', $identity)
            ->groupEnd()
            ->where('is_active', 1)
            ->first();

        if ($user === null || ! password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }

    /** @return list<string> */
    public function rolesForUser(int $userId): array
    {
        $rows = db_connect()->table('user_roles')
            ->select('roles.name')
            ->join('roles', 'roles.id = user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->orderBy('roles.name')
            ->get()
            ->getResultArray();

        return array_column($rows, 'name');
    }
}
