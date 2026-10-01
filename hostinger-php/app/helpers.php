<?php
declare(strict_types=1);

use Got\Request;
use Got\Settings;

/** Escape text for safe use inside HTML. */
function e(mixed $value): string
{
    if ($value === null || is_array($value) || is_object($value)) {
        return '';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Cast any value to a trimmed string (arrays and objects become ''). */
function s(mixed $value): string
{
    if (is_string($value)) {
        return trim($value);
    }
    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }
    return '';
}

/** Path on this site, respecting installs inside a sub-folder. */
function url(string $path = '/'): string
{
    $path = '/' . ltrim($path, '/');
    return Request::base() . $path;
}

/** Absolute URL (for canonical links, sitemaps and social sharing tags). */
function abs_url(string $path = '/'): string
{
    $site = rtrim((string) Settings::get('site_url', ''), '/');
    if ($site === '') {
        $site = Request::origin() . Request::base();
    }
    return $site . '/' . ltrim($path, '/');
}

/** URL of a file in public/assets with a cache-busting version. */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = PUBLIC_DIR . '/assets/' . $path;
    $version = is_file($file) ? substr(md5((string) filemtime($file) . APP_VERSION), 0, 8) : APP_VERSION;
    return url('/assets/' . $path) . '?v=' . $version;
}

function redirect(string $to, int $status = 303): never
{
    if (!preg_match('#^https?://#i', $to)) {
        $to = url($to);
    }
    header('Location: ' . $to, true, $status);
    exit;
}

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/** Current time in UTC as stored in the database. */
function now_utc(): string
{
    return gmdate('Y-m-d H:i:s');
}

/** Format a stored UTC date-time in India Standard Time. */
function ist(?string $utc, string $format = 'j M Y, g:i A'): string
{
    if ($utc === null || $utc === '') {
        return '';
    }
    try {
        $date = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
        return $date->setTimezone(new DateTimeZone('Asia/Kolkata'))->format($format);
    } catch (Throwable) {
        return '';
    }
}

/** "5 minutes ago" style description of a stored UTC time. */
function time_ago(?string $utc): string
{
    if (!$utc) {
        return '';
    }
    $seconds = time() - strtotime($utc . ' UTC');
    if ($seconds < 60) {
        return 'just now';
    }
    $units = [86400 * 30 => 'month', 86400 * 7 => 'week', 86400 => 'day', 3600 => 'hour', 60 => 'minute'];
    foreach ($units as $size => $name) {
        if ($seconds >= $size) {
            $n = intdiv($seconds, $size);
            return $n . ' ' . $name . ($n === 1 ? '' : 's') . ' ago';
        }
    }
    return 'just now';
}

/** Read a nested array value using a dotted path such as "hero.headline". */
function array_get(array $array, string $path, mixed $default = null): mixed
{
    $current = $array;
    foreach (explode('.', $path) as $segment) {
        if (!is_array($current) || !array_key_exists($segment, $current)) {
            return $default;
        }
        $current = $current[$segment];
    }
    return $current;
}

/** Write a nested array value using a dotted path. */
function array_set(array &$array, string $path, mixed $value): void
{
    $segments = explode('.', $path);
    $current = &$array;
    foreach ($segments as $i => $segment) {
        if ($i === count($segments) - 1) {
            $current[$segment] = $value;
            return;
        }
        if (!isset($current[$segment]) || !is_array($current[$segment])) {
            $current[$segment] = [];
        }
        $current = &$current[$segment];
    }
}

function human_bytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024) . ' KB';
    }
    return $bytes . ' bytes';
}

/** Bytes from php.ini shorthand such as "64M". */
function ini_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '' || $value === '-1') {
        return PHP_INT_MAX;
    }
    $unit = strtolower(substr($value, -1));
    $number = (float) $value;
    return (int) match ($unit) {
        'g' => $number * 1073741824,
        'm' => $number * 1048576,
        'k' => $number * 1024,
        default => $number,
    };
}

function plural(int $count, string $singular, ?string $plural = null): string
{
    return $count . ' ' . ($count === 1 ? $singular : ($plural ?? $singular . 's'));
}
