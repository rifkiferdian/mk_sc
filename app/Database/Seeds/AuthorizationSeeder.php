<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AuthorizationSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $roles = $this->db->table('roles');
        $role = $roles->where('name', 'superadmin')->get()->getRowArray();

        if ($role === null) {
            $roles->insert([
                'name'        => 'superadmin',
                'description' => 'Akses penuh ke seluruh fitur aplikasi.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $roleId = (int) $this->db->insertID();
        } else {
            $roleId = (int) $role['id'];
        }

        $users = $this->db->table('users');
        $user = $users->where('username', 'admin')->get()->getRowArray();

        $adminData = [
            'name'          => 'Super Administrator',
            'email'         => 'admin@mk-sc.local',
            'password_hash' => password_hash('qweqwe', PASSWORD_DEFAULT),
            'is_active'     => 1,
            'updated_at'    => $now,
        ];

        if ($user === null) {
            $adminData['username'] = 'admin';
            $adminData['created_at'] = $now;
            $users->insert($adminData);
            $userId = (int) $this->db->insertID();
        } else {
            $users->where('id', $user['id'])->update($adminData);
            $userId = (int) $user['id'];
        }

        $userRoles = $this->db->table('user_roles');
        $hasRole = $userRoles
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->get()
            ->getRowArray();

        if ($hasRole === null) {
            $userRoles->insert([
                'user_id'    => $userId,
                'role_id'    => $roleId,
                'created_at' => $now,
            ]);
        }
    }
}
