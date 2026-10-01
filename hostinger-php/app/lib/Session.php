<?php
declare(strict_types=1);

namespace Got;

/**
 * Sessions are only started for the dashboard (and for previewing), so normal
 * visitors never receive a cookie.
 */
final class Session
{
    public const NAME = 'gotfz_admin';

    public static function hasCookie(): bool
    {
        return isset($_COOKIE[self::NAME]) && is_string($_COOKIE[self::NAME]) && $_COOKIE[self::NAME] !== '';
    }

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (PHP_SAPI === 'cli' && !isset($_SERVER['REQUEST_METHOD'])) {
            $_SESSION = $_SESSION ?? [];
            return;
        }
        $dir = STORAGE_DIR . '/sessions';
        if (is_dir($dir) && is_writable($dir)) {
            session_save_path($dir);
            ini_set('session.gc_probability', '1');
            ini_set('session.gc_divisor', '100');
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.gc_maxlifetime', (string) (Auth::ABSOLUTE_LIFETIME + 3600));
        session_name(self::NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => Request::base() === '' ? '/' : Request::base() . '/',
            'secure' => Request::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_cache_limiter('');
        session_start();
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(self::NAME, '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'secure' => $params['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_destroy();
    }

    /** One-time message shown on the next page ('success', 'error', 'info'). */
    public static function flash(string $type, string $message): void
    {
        self::start();
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function takeFlashes(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return [];
        }
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return is_array($messages) ? $messages : [];
    }

    /** Keep submitted form values for one request (used after validation errors). */
    public static function keepOld(array $values, array $errors = []): void
    {
        self::start();
        $_SESSION['_old'] = ['values' => $values, 'errors' => $errors];
    }

    public static function takeOld(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return ['values' => [], 'errors' => []];
        }
        $old = $_SESSION['_old'] ?? ['values' => [], 'errors' => []];
        unset($_SESSION['_old']);
        return $old;
    }
}
