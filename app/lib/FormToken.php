<?php
declare(strict_types=1);

namespace Got;

/**
 * Signed, time-stamped token embedded in the enquiry form. Bots that post
 * instantly, replay old forms or forge submissions are caught without any
 * puzzle for real visitors.
 */
final class FormToken
{
    public const MIN_SECONDS = 3;
    public const MAX_AGE = 7 * 86400;

    public static function issue(?int $time = null): string
    {
        $time ??= time();
        $nonce = bin2hex(random_bytes(6));
        return $time . '.' . $nonce . '.' . self::sign($time, $nonce);
    }

    /** @return 'ok'|'too_fast'|'expired'|'invalid' */
    public static function check(string $token, ?int $now = null): string
    {
        $now ??= time();
        $parts = explode('.', $token);
        if (count($parts) !== 3 || !ctype_digit($parts[0]) || !ctype_xdigit($parts[1])) {
            return 'invalid';
        }
        [$time, $nonce, $signature] = $parts;
        if (!hash_equals(self::sign((int) $time, $nonce), $signature)) {
            return 'invalid';
        }
        $age = $now - (int) $time;
        if ($age < 0) {
            return 'invalid';
        }
        if ($age < self::MIN_SECONDS) {
            return 'too_fast';
        }
        if ($age > self::MAX_AGE) {
            return 'expired';
        }
        return 'ok';
    }

    private static function sign(int $time, string $nonce): string
    {
        return substr(hash_hmac('sha256', "enquiry|{$time}|{$nonce}", Config::key()), 0, 32);
    }
}
