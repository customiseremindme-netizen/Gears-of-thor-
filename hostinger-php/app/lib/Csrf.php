<?php
declare(strict_types=1);

namespace Got;

/** Protects dashboard forms against cross-site request forgery. */
final class Csrf
{
    public static function token(): string
    {
        Session::start();
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function verify(): bool
    {
        $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!is_string($sent) || $sent === '') {
            return false;
        }
        if (!hash_equals(self::token(), $sent)) {
            return false;
        }
        // Extra check: if the browser tells us where the form came from, it must be this site.
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (is_string($origin) && $origin !== '' && $origin !== 'null') {
            $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
            $requestHost = strtolower((string) parse_url('http://' . Request::host(), PHP_URL_HOST));
            if ($originHost === '' || $originHost !== $requestHost) {
                return false;
            }
        }
        return true;
    }

    public static function rotate(): void
    {
        Session::start();
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
}
