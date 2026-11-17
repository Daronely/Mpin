<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;

final class AuthController
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        View::render('auth/login', ['title' => 'Log in — Mpin', 'active' => '']);
    }

    public function registerForm(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        View::render('auth/register', ['title' => 'Create account — Mpin', 'active' => '']);
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/');
    }
}
