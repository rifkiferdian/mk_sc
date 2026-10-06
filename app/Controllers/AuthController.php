<?php

namespace App\Controllers;

use App\Services\AuthService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const ATTEMPT_WINDOW_SECONDS = 900;

    public function login(): string|RedirectResponse
    {
        if (session()->get('auth_user_id')) {
            return redirect()->to(site_url('dashboard'));
        }

        return view('auth/login');
    }

    public function attempt(): RedirectResponse
    {
        $session = session();

        if ($this->tooManyAttempts()) {
            return redirect()->back()->withInput()->with('error', 'Terlalu banyak percobaan masuk. Coba lagi dalam 15 menit.');
        }

        $rules = [
            'identity' => 'required|max_length[150]',
            'password' => 'required|max_length[255]',
        ];

        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $identity = trim((string) $this->request->getPost('identity'));
        $password = (string) $this->request->getPost('password');
        $user = (new AuthService())->attempt($identity, $password);

        if ($user === null) {
            $this->recordFailedAttempt();

            return redirect()->back()->withInput()->with('error', 'Username/email atau kata sandi tidak sesuai.');
        }

        $session->regenerate(true);
        $session->set([
            'auth_user_id'   => (int) $user['id'],
            'auth_user_name' => $user['name'],
            'auth_username'  => $user['username'],
        ]);
        $session->remove('login_attempts');

        return redirect()->to(site_url('dashboard'));
    }

    public function logout(): RedirectResponse
    {
        session()->destroy();

        return redirect()->to(site_url('login'))->with('success', 'Anda telah keluar dari aplikasi.');
    }

    private function tooManyAttempts(): bool
    {
        $attempts = session()->get('login_attempts');

        if (! is_array($attempts) || ($attempts['started_at'] + self::ATTEMPT_WINDOW_SECONDS) < time()) {
            session()->remove('login_attempts');

            return false;
        }

        return $attempts['count'] >= self::MAX_LOGIN_ATTEMPTS;
    }

    private function recordFailedAttempt(): void
    {
        $attempts = session()->get('login_attempts');

        if (! is_array($attempts) || ($attempts['started_at'] + self::ATTEMPT_WINDOW_SECONDS) < time()) {
            session()->set('login_attempts', ['count' => 1, 'started_at' => time()]);

            return;
        }

        session()->set('login_attempts', [
            'count'      => $attempts['count'] + 1,
            'started_at' => $attempts['started_at'],
        ]);
    }
}
