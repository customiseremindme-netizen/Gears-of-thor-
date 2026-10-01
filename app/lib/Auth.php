<?php
declare(strict_types=1);

namespace Got;

/**
 * Dashboard sign-in, roles and permissions.
 *
 * Roles:
 *  - owner:  everything, including users and settings
 *  - editor: website content, photos, publishing and enquiries
 *  - staff:  enquiries only (view, update status, add notes)
 */
final class Auth
{
    public const IDLE_TIMEOUT = 4 * 3600;
    public const ABSOLUTE_LIFETIME = 24 * 3600;
    public const MIN_PASSWORD = 10;

    public const ROLES = [
        'owner' => 'Owner',
        'editor' => 'Editor',
        'staff' => 'Staff',
    ];

    private const ABILITIES = [
        'owner' => ['content', 'media', 'publish', 'enquiries', 'enquiries.export', 'enquiries.delete', 'settings', 'users'],
        'editor' => ['content', 'media', 'publish', 'enquiries', 'enquiries.export'],
        'staff' => ['enquiries'],
    ];

    private static array|false|null $user = null;

    /** The signed-in user, or null. */
    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user ?: null;
        }
        self::$user = false;

        if (session_status() !== PHP_SESSION_ACTIVE && !Session::hasCookie()) {
            return null;
        }
        Session::start();
        $id = (int) ($_SESSION['uid'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        $now = time();
        $idle = $now - (int) ($_SESSION['last_seen'] ?? 0);
        $age = $now - (int) ($_SESSION['login_at'] ?? 0);
        if ($idle > self::IDLE_TIMEOUT || $age > self::ABSOLUTE_LIFETIME) {
            self::logout();
            Session::start();
            Session::flash('info', 'You were signed out after a period of inactivity. Please sign in again.');
            return null;
        }

        $user = Db::get()->one(
            'SELECT id, name, email, role, is_active, password_hash FROM users WHERE id = ?',
            [$id]
        );
        // Signing out everywhere after a password change: the session remembers a
        // fingerprint of the password hash it was created with.
        if (!$user || !(int) $user['is_active']
            || !hash_equals((string) ($_SESSION['pwf'] ?? ''), self::fingerprint((string) $user['password_hash']))) {
            self::logout();
            return null;
        }

        $_SESSION['last_seen'] = $now;
        unset($user['password_hash']);
        $user['id'] = (int) $user['id'];
        self::$user = $user;
        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function can(string $ability): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }
        return in_array($ability, self::ABILITIES[$user['role']] ?? [], true);
    }

    /** Stop the request unless the user is signed in and allowed to do this. */
    public static function require(string $ability = 'enquiries'): array
    {
        $user = self::user();
        if (!$user) {
            if (Request::wantsJson()) {
                json_out(['ok' => false, 'error' => 'Please sign in again.'], 401);
            }
            Session::start();
            $_SESSION['after_login'] = Request::path();
            redirect('/admin/login');
        }
        if (!self::can($ability)) {
            if (Request::wantsJson()) {
                json_out(['ok' => false, 'error' => 'Your account does not have permission to do this.'], 403);
            }
            AdminView::render('forbidden', ['title' => 'Not allowed'], 403);
            exit;
        }
        return $user;
    }

    /**
     * Check an email and password with brute-force protection.
     * @return array{ok: bool, error?: string, user?: array}
     */
    public static function attempt(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $ip = Request::ip();
        $generic = 'That email and password combination was not recognised.';

        if (RateLimiter::tooMany('login_ip', $ip, 10, 900) || RateLimiter::tooMany('login_email', $email, 5, 900)) {
            return ['ok' => false, 'error' => 'Too many sign-in attempts. Please wait 15 minutes and try again.'];
        }

        $user = $email === '' ? null : Db::get()->one('SELECT * FROM users WHERE email = ?', [$email]);
        // Always run a password check so response times do not reveal which emails exist.
        $hash = $user['password_hash'] ?? '$2y$10$0SjLLtVt9WeIhB/qWB.mUuTh4Iv7AD1ixz4aNA2wYvYMr5u6YE0x.';
        $valid = password_verify($password, (string) $hash);

        if (!$user || !$valid) {
            RateLimiter::hit('login_ip', $ip);
            RateLimiter::hit('login_email', $email);
            Logger::warning('Failed dashboard sign-in', ['email' => $email]);
            return ['ok' => false, 'error' => $generic];
        }
        if (!(int) $user['is_active']) {
            return ['ok' => false, 'error' => 'This account has been switched off. Ask the owner to turn it back on.'];
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $user['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            Db::get()->update('users', ['password_hash' => $user['password_hash']], 'id = ?', [(int) $user['id']]);
        }

        RateLimiter::clear('login_email', $email);
        return ['ok' => true, 'user' => $user];
    }

    public static function login(array $user): void
    {
        Session::start();
        Session::regenerate();
        $after = $_SESSION['after_login'] ?? null;
        $_SESSION = [
            'uid' => (int) $user['id'],
            'login_at' => time(),
            'last_seen' => time(),
            'pwf' => self::fingerprint((string) $user['password_hash']),
            'after_login' => $after,
        ];
        Csrf::rotate();
        Db::get()->update('users', ['last_login_at' => now_utc()], 'id = ?', [(int) $user['id']]);
        self::$user = null;
    }

    public static function logout(): void
    {
        Session::start();
        Session::destroy();
        self::$user = null;
    }

    /** Refresh the session after the signed-in user changes their own password. */
    public static function refreshFingerprint(string $newHash): void
    {
        Session::start();
        $_SESSION['pwf'] = self::fingerprint($newHash);
        self::$user = null;
    }

    public static function fingerprint(string $hash): string
    {
        return substr(hash('sha256', $hash), 0, 24);
    }

    public static function validatePassword(string $password): ?string
    {
        if (mb_strlen($password) < self::MIN_PASSWORD) {
            return 'Use at least ' . self::MIN_PASSWORD . ' characters.';
        }
        if (mb_strlen($password) > 200) {
            return 'That password is too long.';
        }
        $common = ['password123', '1234567890', 'qwertyuiop', 'gotfitnezz1', 'gotfitnezz123', 'palladam123'];
        if (in_array(strtolower($password), $common, true)) {
            return 'That password is too easy to guess. Please choose another.';
        }
        return null;
    }

    /** Forget the cached user (used by tests). */
    public static function reset(): void
    {
        self::$user = null;
    }
}
