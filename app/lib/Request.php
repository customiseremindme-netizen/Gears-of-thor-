<?php
declare(strict_types=1);

namespace Got;

/** Information about the current web request. */
final class Request
{
    private static ?string $base = null;
    private static ?string $path = null;

    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    /**
     * The folder the site lives in ('' when installed at the domain root).
     * Works when public_html contains the whole project (requests are
     * rewritten into /public) and when /public is the web root.
     */
    public static function base(): string
    {
        if (self::$base === null && PHP_SAPI === 'cli') {
            self::$base = '';
        }
        if (self::$base === null) {
            $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
            $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
            $dir = (string) preg_replace('#/public$#', '', $dir);
            self::$base = ($dir === '/' || $dir === '.') ? '' : $dir;
        }
        return self::$base;
    }

    /** Path of the request relative to the site, e.g. "/admin/enquiries". */
    public static function path(): string
    {
        if (self::$path === null) {
            $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
            $path = rawurldecode((string) (parse_url($uri, PHP_URL_PATH) ?: '/'));
            $base = self::base();
            if ($base !== '' && str_starts_with($path, $base)) {
                $path = substr($path, strlen($base));
            }
            // Requests that reach us as /public/... (direct access) behave the same.
            if (str_starts_with($path, '/public/')) {
                $path = substr($path, 7);
            }
            $path = '/' . trim($path, '/');
            self::$path = $path === '/index.php' ? '/' : $path;
        }
        return self::$path;
    }

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
            return true;
        }
        $forwarded = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        return $forwarded === 'https';
    }

    public static function host(): string
    {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
        // Keep only characters valid in a host name (and port).
        $host = strtolower((string) preg_replace('/[^a-z0-9.\-:\[\]]/i', '', $host));
        return $host !== '' ? $host : 'localhost';
    }

    public static function origin(): string
    {
        return (self::isHttps() ? 'https://' : 'http://') . self::host();
    }

    /** Visitor IP address (used only in hashed form for spam and abuse limits). */
    public static function ip(): string
    {
        $header = Config::get('trusted_ip_header');
        if (is_string($header) && $header !== '' && !empty($_SERVER[$header])) {
            $candidate = trim(explode(',', (string) $_SERVER[$header])[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public static function userAgent(): string
    {
        return mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public static function wantsJson(): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $requestedWith = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        return str_contains($accept, 'application/json') || strtolower($requestedWith) === 'fetch';
    }

    /** A POSTed text value (arrays are ignored), trimmed. */
    public static function post(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    /** A query-string text value, trimmed. */
    public static function query(string $key, string $default = ''): string
    {
        $value = $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    public static function postArray(string $key): array
    {
        $value = $_POST[$key] ?? [];
        return is_array($value) ? $value : [];
    }

    /** Reset cached values (used by tests). */
    public static function reset(): void
    {
        self::$base = null;
        self::$path = null;
    }
}
