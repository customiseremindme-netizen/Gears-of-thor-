<?php
declare(strict_types=1);

namespace Got\Controllers;

use Got\Auth;
use Got\Config;
use Got\Content;
use Got\FormToken;
use Got\Media;
use Got\Request;
use Got\Security;
use Got\Site;
use Got\Turnstile;
use Got\View;

/** The public website. */
final class SiteController
{
    public static function home(): void
    {
        $preview = self::isPreview();
        $site = Site::build($preview);
        $form = self::emptyForm();

        // Arriving back after a successful no-JavaScript submission.
        if (Request::query('enquiry') === 'sent' && self::validReceipt(Request::query('ref'))) {
            $form['status'] = 'success';
        }
        self::render($site, $form);
    }

    /** Render the one-page site (also used to show form errors without JavaScript). */
    public static function render(Site $site, array $form, int $status = 200): void
    {
        Security::headers('site', ['turnstile' => Turnstile::enabled() && !$site->preview]);
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        if ($site->preview) {
            header('Cache-Control: no-store, private');
            header('X-Robots-Tag: noindex, nofollow');
        } else {
            header('Cache-Control: no-cache');
        }
        echo View::render('site/home', ['site' => $site, 'form' => $form, 'page' => 'home'], 'site/layout');
    }

    public static function privacy(): void
    {
        $site = Site::build(self::isPreview());
        Security::headers('site');
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-cache');
        echo View::render('site/privacy', ['site' => $site, 'page' => 'privacy'], 'site/layout');
    }

    public static function notFound(): void
    {
        http_response_code(404);
        if (!Config::installed()) {
            echo 'Not found';
            return;
        }
        $site = Site::build(false);
        Security::headers('site');
        header('Content-Type: text/html; charset=utf-8');
        echo View::render('site/not-found', ['site' => $site, 'page' => '404'], 'site/layout');
    }

    public static function robots(): void
    {
        $site = Site::build(false);
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        if (!empty($site->c['seo']['hide_from_search'])) {
            echo "User-agent: *\nDisallow: /\n";
            return;
        }
        echo "User-agent: *\nDisallow: /admin\nDisallow: /api/\nDisallow: /enquire\n\nSitemap: " . abs_url('/sitemap.xml') . "\n";
    }

    public static function sitemap(): void
    {
        $row = Content::row('published');
        $lastmod = $row ? gmdate('Y-m-d', (int) strtotime($row['updated_at'] . ' UTC')) : gmdate('Y-m-d');
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach (['/' => '1.0', '/privacy' => '0.3'] as $path => $priority) {
            echo '  <url><loc>' . e(abs_url($path)) . '</loc><lastmod>' . $lastmod . '</lastmod><priority>' . $priority . "</priority></url>\n";
        }
        echo "</urlset>\n";
    }

    public static function manifest(): void
    {
        $site = Site::build(false);
        $icons = [];
        if ($icon = $site->media($site->get('brand.icon', null))) {
            foreach ([192, 512] as $size) {
                if ($url = Media::iconUrl($icon, $size)) {
                    $icons[] = ['src' => $url, 'sizes' => "{$size}x{$size}", 'type' => 'image/png'];
                }
            }
        }
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo json_encode([
            'name' => $site->str('brand.name') . ($site->str('brand.expanded') !== '' ? ' — ' . $site->str('brand.expanded') : ''),
            'short_name' => $site->str('brand.name'),
            'start_url' => url('/'),
            'display' => 'browser',
            'background_color' => $site->str('theme.bg'),
            'theme_color' => $site->str('theme.bg'),
            'icons' => $icons,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function emptyForm(): array
    {
        return ['status' => 'idle', 'values' => [], 'errors' => [], 'message' => '', 'token' => FormToken::issue()];
    }

    /** Preview of unpublished changes — only for signed-in editors. */
    public static function isPreview(): bool
    {
        return Request::query('preview') === '1' && Auth::can('content');
    }

    /** Signed receipt so the "received" message only follows a real saved enquiry. */
    public static function receipt(int $enquiryId): string
    {
        $time = time();
        return $enquiryId . '.' . $time . '.' . substr(hash_hmac('sha256', "receipt|{$enquiryId}|{$time}", Config::key()), 0, 20);
    }

    private static function validReceipt(string $ref): bool
    {
        $parts = explode('.', $ref);
        if (count($parts) !== 3 || !ctype_digit($parts[0]) || !ctype_digit($parts[1])) {
            return false;
        }
        $expected = substr(hash_hmac('sha256', "receipt|{$parts[0]}|{$parts[1]}", Config::key()), 0, 20);
        return hash_equals($expected, $parts[2]) && (time() - (int) $parts[1]) < 900;
    }
}
