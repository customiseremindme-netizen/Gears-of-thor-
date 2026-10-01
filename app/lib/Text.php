<?php
declare(strict_types=1);

namespace Got;

/** Turns owner-written text into safe HTML. */
final class Text
{
    /** Escape a line and turn *words* into the accent highlight. */
    public static function inline(string $text): string
    {
        $html = e($text);
        return (string) preg_replace('/\*([^*\n]+)\*/u', '<span class="hl">$1</span>', $html);
    }

    /** Text without the *highlight* markers (for titles and alt text). */
    public static function plain(string $text): string
    {
        return trim((string) preg_replace('/\s+/', ' ', str_replace('*', '', $text)));
    }

    public static function lines(string $text): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", $text)), static fn ($l) => $l !== ''));
    }

    /** A heading where each line of text becomes a line on screen. */
    public static function heading(string $text): string
    {
        return implode('<br>', array_map([self::class, 'inline'], self::lines($text)));
    }

    /** A heading split into masked lines for the entrance animation. */
    public static function animatedHeading(string $text): string
    {
        $out = '';
        foreach (self::lines($text) as $i => $line) {
            $out .= '<span class="line"><span class="line__inner" style="--i:' . $i . '">' . self::inline($line) . '</span></span>';
        }
        return $out;
    }

    /** Paragraphs from plain text, with [phone]-style shortcuts replaced. */
    public static function paragraphs(string $text, array $tokens = []): string
    {
        $blocks = preg_split("/\n\s*\n/", trim($text)) ?: [];
        $html = '';
        foreach ($blocks as $block) {
            if (trim($block) === '') {
                continue;
            }
            $html .= '<p>' . self::tokens(nl2br(e(trim($block)), false), $tokens) . '</p>';
        }
        return $html;
    }

    /** Replace [shortcut] tokens in already-escaped HTML. */
    public static function tokens(string $html, array $tokens): string
    {
        if (!$tokens) {
            return $html;
        }
        return (string) preg_replace_callback('/\[(phone|address|hours|training|instagram|whatsapp|email)\]/i', static function ($m) use ($tokens) {
            $key = strtolower($m[1]);
            return $tokens[$key] ?? '';
        }, $html);
    }

    /** "tel:" link value from a displayed phone number. */
    public static function telHref(string $phone): string
    {
        $digits = (string) preg_replace('/[^\d+]/', '', $phone);
        return 'tel:' . $digits;
    }

    /** WhatsApp click-to-chat link (country code required; Indian numbers assumed). */
    public static function whatsappHref(string $phone, string $message = ''): string
    {
        $digits = (string) preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 10) {
            $digits = '91' . $digits;
        } elseif (strlen($digits) === 11 && $digits[0] === '0') {
            $digits = '91' . substr($digits, 1);
        }
        return 'https://wa.me/' . $digits . ($message !== '' ? '?text=' . rawurlencode($message) : '');
    }

    /** "@got_fitnezz" from an Instagram profile link. */
    public static function instagramHandle(string $url): string
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $handle = explode('/', $path)[0] ?? '';
        return $handle !== '' ? '@' . $handle : 'Instagram';
    }

    public static function slug(string $text): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $text), '-'));
        return $slug !== '' ? $slug : 'section';
    }

    /** "a, b and c" */
    public static function listing(array $items): string
    {
        $items = array_values(array_filter($items, static fn ($i) => $i !== ''));
        if (count($items) <= 1) {
            return $items[0] ?? '';
        }
        $last = array_pop($items);
        return implode(', ', $items) . ' and ' . $last;
    }
}
