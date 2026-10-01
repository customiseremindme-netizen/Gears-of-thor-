<?php
declare(strict_types=1);

namespace Got\Controllers\Admin;

use Got\AdminView;
use Got\Auth;
use Got\Csrf;
use Got\Db;
use Got\Logger;
use Got\RateLimiter;
use Got\Request;
use Got\Security;
use Got\Session;
use Got\View;

/** Sign in, sign out and password recovery for the dashboard. */
final class AuthController
{
    public static function loginForm(): void
    {
        if (Auth::check()) {
            redirect('/admin');
        }
        Session::start();
        $old = Session::takeOld();
        self::page('login', [
            'title' => 'Sign in',
            'email' => (string) ($old['values']['email'] ?? ''),
            'flashes' => Session::takeFlashes(),
        ]);
    }

    public static function login(): void
    {
        AdminView::requireCsrf();
        $email = Request::post('email');
        $result = Auth::attempt($email, (string) ($_POST['password'] ?? ''));
        if (!$result['ok']) {
            Session::keepOld(['email' => $email]);
            Session::flash('error', $result['error']);
            redirect('/admin/login');
        }
        Auth::login($result['user']);
        $after = $_SESSION['after_login'] ?? null;
        unset($_SESSION['after_login']);
        $target = is_string($after) && preg_match('#^/admin(/[a-z0-9/\-]*)?$#', $after) ? $after : '/admin';
        redirect($target);
    }

    public static function logout(): void
    {
        AdminView::requireCsrf();
        Auth::logout();
        Session::start();
        Session::flash('success', 'You have signed out.');
        redirect('/admin/login');
    }

    public static function recoverForm(): void
    {
        Session::start();
        self::page('recover', [
            'title' => 'Reset a password',
            'flashes' => Session::takeFlashes(),
            'available' => is_file(STORAGE_DIR . '/recovery.txt'),
        ]);
    }

    /**
     * Password reset for someone with access to the hosting File Manager:
     * they create storage/recovery.txt containing a code of their choice.
     */
    public static function recover(): void
    {
        AdminView::requireCsrf();
        $file = STORAGE_DIR . '/recovery.txt';
        $ip = Request::ip();

        if (RateLimiter::tooMany('recover', $ip, 5, 3600)) {
            Session::flash('error', 'Too many attempts. Please wait an hour and try again.');
            redirect('/admin/recover');
        }
        RateLimiter::hit('recover', $ip);

        $expected = is_file($file) ? trim((string) file_get_contents($file)) : '';
        $code = Request::post('code');
        $email = strtolower(Request::post('email'));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        if (strlen($expected) < 12) {
            Session::flash('error', 'Recovery is not switched on. Create storage/recovery.txt with a code of at least 12 characters first.');
            redirect('/admin/recover');
        }
        if (!hash_equals($expected, $code)) {
            Session::flash('error', 'That recovery code does not match the one in storage/recovery.txt.');
            redirect('/admin/recover');
        }
        if ($problem = Auth::validatePassword($password)) {
            Session::flash('error', $problem);
            redirect('/admin/recover');
        }
        if ($password !== $confirm) {
            Session::flash('error', 'The two passwords do not match.');
            redirect('/admin/recover');
        }
        $user = Db::get()->one('SELECT id FROM users WHERE email = ?', [$email]);
        if (!$user) {
            Session::flash('error', 'No dashboard account uses that email address.');
            redirect('/admin/recover');
        }
        Db::get()->update('users', [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'is_active' => 1,
            'updated_at' => now_utc(),
        ], 'id = ?', [(int) $user['id']]);
        @unlink($file);
        RateLimiter::clear('login_email', $email);
        Logger::info('Password reset with a recovery code', ['user' => (int) $user['id']]);
        Session::flash('success', 'Password changed. The recovery file has been deleted. You can sign in now.');
        redirect('/admin/login');
    }

    private static function page(string $template, array $vars): void
    {
        Security::headers('admin');
        header('Content-Type: text/html; charset=utf-8');
        Csrf::token();
        echo View::render('admin/' . $template, $vars, 'admin/auth-layout');
    }
}
