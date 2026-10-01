<?php
declare(strict_types=1);

namespace Got\Controllers\Admin;

use Got\AdminView;
use Got\Auth;
use Got\Db;
use Got\Request;
use Got\Sanitizer;
use Got\Session;

/** Dashboard accounts: the owner can add staff; everyone can manage their own account. */
final class UsersController
{
    public static function index(): void
    {
        Auth::require('users');
        AdminView::render('users', [
            'title' => 'Users',
            'nav' => 'users',
            'users' => Db::get()->all('SELECT id, name, email, role, is_active, last_login_at, created_at FROM users ORDER BY role, name'),
        ]);
    }

    public static function create(): void
    {
        Auth::require('users');
        AdminView::requireCsrf();
        $name = Sanitizer::cleanText(Request::post('name'), false);
        $email = strtolower(Request::post('email'));
        $role = Request::post('role');
        $password = (string) ($_POST['password'] ?? '');

        $error = null;
        if ($name === '' || mb_strlen($name) > 100) {
            $error = 'Please enter a name.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (!isset(Auth::ROLES[$role])) {
            $error = 'Please choose a role.';
        } elseif ($problem = Auth::validatePassword($password)) {
            $error = 'Password: ' . $problem;
        } elseif (Db::get()->value('SELECT COUNT(*) FROM users WHERE email = ?', [$email]) > 0) {
            $error = 'Someone already uses that email address.';
        }
        if ($error) {
            Session::flash('error', $error);
            redirect('/admin/users');
        }

        Db::get()->insert('users', [
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'is_active' => 1,
            'last_login_at' => null,
            'created_at' => now_utc(),
            'updated_at' => now_utc(),
        ]);
        Session::flash('success', "Account created for {$name}. Share the email and temporary password with them privately, and ask them to change it under “My account”.");
        redirect('/admin/users');
    }

    public static function update(int $id): void
    {
        $me = Auth::require('users');
        AdminView::requireCsrf();
        $user = Db::get()->one('SELECT * FROM users WHERE id = ?', [$id]);
        if (!$user) {
            redirect('/admin/users');
        }

        if (Request::post('action') === 'password') {
            $password = (string) ($_POST['password'] ?? '');
            if ($problem = Auth::validatePassword($password)) {
                Session::flash('error', 'Password: ' . $problem);
                redirect('/admin/users');
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            Db::get()->update('users', ['password_hash' => $hash, 'updated_at' => now_utc()], 'id = ?', [$id]);
            if ($id === $me['id']) {
                Auth::refreshFingerprint($hash);
            }
            Session::flash('success', 'Password changed for ' . $user['name'] . '. They will need to sign in again.');
            redirect('/admin/users');
        }

        $role = Request::post('role');
        $active = Request::post('is_active') === '1';
        if (!isset(Auth::ROLES[$role])) {
            redirect('/admin/users');
        }
        $owners = (int) Db::get()->value("SELECT COUNT(*) FROM users WHERE role = 'owner' AND is_active = 1");
        $losingOwner = $user['role'] === 'owner' && (int) $user['is_active'] === 1 && ($role !== 'owner' || !$active);
        if ($losingOwner && $owners <= 1) {
            Session::flash('error', 'There must always be at least one active owner.');
            redirect('/admin/users');
        }
        if ($id === $me['id'] && !$active) {
            Session::flash('error', 'You cannot switch off your own account.');
            redirect('/admin/users');
        }
        Db::get()->update('users', ['role' => $role, 'is_active' => $active ? 1 : 0, 'updated_at' => now_utc()], 'id = ?', [$id]);
        Session::flash('success', 'Saved changes for ' . $user['name'] . '.');
        redirect('/admin/users');
    }

    public static function account(): void
    {
        $me = Auth::require('enquiries');
        AdminView::render('account', [
            'title' => 'My account',
            'nav' => 'account',
            'me' => $me,
        ]);
    }

    public static function saveAccount(): void
    {
        $me = Auth::require('enquiries');
        AdminView::requireCsrf();
        $row = Db::get()->one('SELECT * FROM users WHERE id = ?', [$me['id']]);
        $current = (string) ($_POST['current_password'] ?? '');
        if (!$row || !password_verify($current, (string) $row['password_hash'])) {
            Session::flash('error', 'Your current password was not correct, so nothing was changed.');
            redirect('/admin/account');
        }

        $name = Sanitizer::cleanText(Request::post('name'), false);
        $email = strtolower(Request::post('email'));
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirm'] ?? '');
        $changes = ['updated_at' => now_utc()];

        if ($name === '') {
            Session::flash('error', 'Please enter your name.');
            redirect('/admin/account');
        }
        $changes['name'] = mb_substr($name, 0, 100);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Please enter a valid email address.');
            redirect('/admin/account');
        }
        if ($email !== $row['email'] && Db::get()->value('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?', [$email, $me['id']]) > 0) {
            Session::flash('error', 'Someone else already uses that email address.');
            redirect('/admin/account');
        }
        $changes['email'] = $email;

        if ($new !== '') {
            if ($problem = Auth::validatePassword($new)) {
                Session::flash('error', 'New password: ' . $problem);
                redirect('/admin/account');
            }
            if ($new !== $confirm) {
                Session::flash('error', 'The two new passwords do not match.');
                redirect('/admin/account');
            }
            $changes['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
        }

        Db::get()->update('users', $changes, 'id = ?', [$me['id']]);
        if (isset($changes['password_hash'])) {
            Auth::refreshFingerprint($changes['password_hash']);
        }
        Session::flash('success', isset($changes['password_hash']) ? 'Account updated and password changed.' : 'Account updated.');
        redirect('/admin/account');
    }
}
