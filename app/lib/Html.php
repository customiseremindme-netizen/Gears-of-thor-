<?php
declare(strict_types=1);

namespace Got;

/** Small HTML building helpers shared by the website and the dashboard. */
final class Html
{
    public static function attrs(array $attrs): string
    {
        $out = '';
        foreach ($attrs as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            $out .= $value === true ? ' ' . $name : ' ' . $name . '="' . e((string) $value) . '"';
        }
        return $out;
    }

    /**
     * Responsive image: WebP sizes with a JPEG/PNG fallback.
     * @param string $sizes how wide the image is shown, e.g. "(min-width: 900px) 50vw, 100vw"
     */
    public static function picture(?array $media, string $sizes, string $alt = '', array $attrs = [], bool $eager = false): string
    {
        if (!$media || $media['kind'] !== 'image') {
            return '';
        }
        $alt = $alt !== '' ? $alt : (string) $media['alt'];
        $img = self::attrs(array_merge([
            'src' => Media::fileUrl($media),
            'width' => $media['width'],
            'height' => $media['height'],
            'alt' => $alt,
            'loading' => $eager ? 'eager' : 'lazy',
            'decoding' => $eager ? 'sync' : 'async',
            'fetchpriority' => $eager ? 'high' : null,
        ], $attrs));
        $srcset = Media::srcset($media);
        if ($srcset === '') {
            return '<img' . $img . '>';
        }
        $type = ' type="' . Media::variantMime($media) . '"';
        return '<picture><source' . $type . ' srcset="' . e($srcset) . '" sizes="' . e($sizes) . '"><img' . $img . '></picture>';
    }

    /** Inline SVG icons (decorative: hidden from screen readers). */
    public static function icon(string $name, string $class = 'icon'): string
    {
        $paths = [
            'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
            'pin' => '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>',
            'facebook' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z"/>',
            'youtube' => '<rect x="2.5" y="5" width="19" height="14" rx="4"/><path d="M10 9l5 3-5 3z" fill="currentColor"/>',
            'whatsapp' => '<path d="M4 20l1.3-4A8.5 8.5 0 1 1 8.2 19z"/><path d="M9 8.5c0 3 2.5 6 6 6.5l1-1.5-1.8-1-1 .8a5 5 0 0 1-2.5-2.5l.8-1-1-1.8z" fill="currentColor" stroke="none"/>',
            'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
            'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
            'arrow-down' => '<path d="M12 5v14M6 13l6 6 6-6"/>',
            'arrow-up' => '<path d="M12 19V5M6 11l6-6 6 6"/>',
            'directions' => '<path d="M12 2l10 10-10 10L2 12z"/><path d="M9 13v-2a1 1 0 0 1 1-1h5M13 8l2 2-2 2"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'close' => '<path d="M6 6l12 12M18 6L6 18"/>',
            'menu' => '<path d="M4 7h16M4 12h16M4 17h10"/>',
            'chevron-left' => '<path d="M15 5l-7 7 7 7"/>',
            'chevron-right' => '<path d="M9 5l7 7-7 7"/>',
            'pause' => '<path d="M9 5v14M15 5v14"/>',
            'play' => '<path d="M7 5l12 7-12 7z"/>',
            'check' => '<path d="M5 12.5l4.5 4.5L19 7"/>',
            'expand' => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
            'quote' => '<path d="M10 7H6a2 2 0 0 0-2 2v4h5v5M20 7h-4a2 2 0 0 0-2 2v4h5v5" />',
        ];
        $body = $paths[$name] ?? '';
        return '<svg class="' . e($class) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $body . '</svg>';
    }
}
