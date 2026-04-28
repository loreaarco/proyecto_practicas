<?php

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $error = Session::getFlash('error');
        include BASE_PATH . '/views/auth/login.php';
    }

    public function login(): void
    {
        $email    = filter_input(INPUT_POST, 'email',    FILTER_SANITIZE_EMAIL)   ?? '';
        $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW)       ?? '';

        if (empty($email) || empty($password)) {
            Session::flash('error', 'Email y contraseña son obligatorios.');
            $this->redirect('/auth/login');
        }

        if (Auth::login($email, $password)) {
            $this->redirect('/dashboard');
        } else {
            Logger::warning('Auth', 'Intento de login fallido', ['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR']]);
            Session::flash('error', 'Credenciales incorrectas.');
            $this->redirect('/auth/login');
        }
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/auth/login');
    }
}
