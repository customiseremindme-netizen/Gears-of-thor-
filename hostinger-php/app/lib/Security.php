<?php
declare(strict_types=1);

namespace Got;

/** HTTP security headers, including a strict Content Security Policy. */
final class Security
{
    private static ?string $nonce = null;

    /** Per-request nonce that allows our own small inline scripts. */
    public static function nonce(): string
    {
        return self::$nonce ??= rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }

    /**
     * @param string $context 'site' for public pages, 'admin' for the dashboard
     * @param array{turnstile?: bool, maps?: bool} $allow optional third-party services
     */
    public static function headers(string $context = 'site', array $allow = []): void
    {
        if (headers_sent()) {
            return;
        }
        $nonce = self::nonce();
        $script = ["'self'", "'nonce-{$nonce}'"];
        $connect = ["'self'"];
        $frame = [];

        if (!empty($allow['turnstile'])) {
            $script[] = 'https://challenges.cloudflare.com';
            $connect[] = 'https://challenges.cloudflare.com';
            $frame[] = 'https://challenges.cloudflare.com';
        }
        if (!empty($allow['maps'])) {
            $frame[] = 'https://www.google.com';
            $frame[] = 'https://maps.google.com';
        }

        $policy = [
            "default-src 'self'",
            'script-src ' . implode(' ', $script),
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self'",
            "media-src 'self' blob:",
            'connect-src ' . implode(' ', $connect),
            'frame-src ' . ($frame ? implode(' ', $frame) : "'self'"),
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
            "manifest-src 'self'",
        ];
        if (Request::isHttps()) {
            $policy[] = 'upgrade-insecure-requests';
            header('Strict-Transport-Security: max-age=15552000');
        }

        header('Content-Security-Policy: ' . implode('; ', $policy));
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        // Pages change the moment you publish, so the server must not keep cached copies.
        header('X-LiteSpeed-Cache-Control: no-cache');

        if ($context === 'admin') {
            header('Cache-Control: no-store, private');
            header('X-Robots-Tag: noindex, nofollow');
        }
    }
}
