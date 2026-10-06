<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\AuthService;
use App\Services\PermissionService;
use CodeIgniter\HTTP\RedirectResponse;

class DashboardController extends BaseController
{
    public function index(): string|RedirectResponse
    {
        $userId = (int) session('auth_user_id');
        $user = (new UserModel())->find($userId);

        if ($user === null || ! $user['is_active']) {
            session()->destroy();

            return redirect()->to(site_url('login'))->with('error', 'Akun Anda sudah tidak aktif.');
        }

        return view('dashboard/index', [
            'user'  => $user,
            'roles' => (new AuthService())->rolesForUser($userId),
            'showMasterNavigation' => (new PermissionService())->can($userId, 'stores.view'),
        ]);
    }
}
