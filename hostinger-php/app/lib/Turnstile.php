<?php
declare(strict_types=1);

namespace Got;

/**
 * Optional Cloudflare Turnstile check on the enquiry form (free; switched on
 * in Dashboard → Settings once you add your keys).
 */
final class Turnstile
{
    public static function enabled(): bool
    {
        return (bool) Settings::get('turnstile_enabled', false)
            && (string) Settings::get('turnstile_site_key', '') !== ''
            && (string) Settings::get('turnstile_secret_key', '') !== '';
    }

    public static function siteKey(): string
    {
        return (string) Settings::get('turnstile_site_key', '');
    }

    /** @return 'ok'|'failed'|'unavailable' */
    public static function verify(string $response, string $ip): string
    {
        if ($response === '') {
            return 'failed';
        }
        $payload = http_build_query([
            'secret' => (string) Settings::get('turnstile_secret_key', ''),
            'response' => $response,
            'remoteip' => $ip,
        ]);
        $body = null;
        if (function_exists('curl_init')) {
            $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_CONNECTTIMEOUT => 4,
            ]);
            $result = curl_exec($ch);
            $body = is_string($result) ? $result : null;
        } else {
            $context = stream_context_create(['http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 6,
            ]]);
            $result = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
            $body = is_string($result) ? $result : null;
        }
        if ($body === null) {
            Logger::warning('Turnstile could not be reached; enquiry accepted without the check.');
            return 'unavailable';
        }
        $data = json_decode($body, true);
        return is_array($data) && !empty($data['success']) ? 'ok' : 'failed';
    }
}
