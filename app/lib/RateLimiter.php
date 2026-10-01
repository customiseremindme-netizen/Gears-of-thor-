<?php
declare(strict_types=1);

namespace Got;

/**
 * Counts recent attempts (logins, enquiries) per visitor without storing raw
 * IP addresses: keys are hashed with the site's secret key.
 */
final class RateLimiter
{
    public static function hashKey(string $key): string
    {
        return hash_hmac('sha256', strtolower($key), Config::key());
    }

    public static function hit(string $bucket, string $key): void
    {
        Db::get()->insert('throttle', [
            'bucket' => $bucket,
            'key_hash' => self::hashKey($key),
            'created_at' => now_utc(),
        ]);
        if (random_int(1, 50) === 1) {
            self::cleanup();
        }
    }

    public static function count(string $bucket, string $key, int $seconds): int
    {
        return (int) Db::get()->value(
            'SELECT COUNT(*) FROM throttle WHERE bucket = ? AND key_hash = ? AND created_at >= ?',
            [$bucket, self::hashKey($key), gmdate('Y-m-d H:i:s', time() - $seconds)]
        );
    }

    public static function tooMany(string $bucket, string $key, int $max, int $seconds): bool
    {
        return self::count($bucket, $key, $seconds) >= $max;
    }

    public static function clear(string $bucket, string $key): void
    {
        Db::get()->run('DELETE FROM throttle WHERE bucket = ? AND key_hash = ?', [$bucket, self::hashKey($key)]);
    }

    public static function cleanup(): void
    {
        Db::get()->run('DELETE FROM throttle WHERE created_at < ?', [gmdate('Y-m-d H:i:s', time() - 172800)]);
    }
}
