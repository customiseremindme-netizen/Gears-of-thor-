<?php
declare(strict_types=1);

namespace Got;

/** Renders dashboard pages inside the dashboard layout. */
final class AdminView
{
    public static function render(string $template, array $vars = [], int $status = 200): void
    {
        Security::headers('admin');
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');

        $user = Auth::user();
        $layoutVars = [
            'user' => $user,
            'flashes' => Session::takeFlashes(),
            'newEnquiries' => 0,
            'hasChanges' => false,
            'changedKeys' => [],
            'checklistOpen' => 0,
        ];
        if ($user) {
            try {
                $layoutVars['newEnquiries'] = (int) Db::get()->value("SELECT COUNT(*) FROM enquiries WHERE status = 'new' AND is_spam = 0");
                if (Auth::can('content')) {
                    $layoutVars['changedKeys'] = Content::changedKeys();
                    $layoutVars['hasChanges'] = $layoutVars['changedKeys'] !== [];
                    $layoutVars['checklistOpen'] = count(array_filter(Checklist::items(), static fn ($i) => !$i['done'] && $i['level'] === 'required'));
                }
            } catch (\Throwable $e) {
                Logger::error('Dashboard layout data failed: ' . $e->getMessage());
            }
        }
        echo View::render('admin/' . $template, $vars + $layoutVars, 'admin/layout');
    }

    /** Stop a dashboard form submission that lacks a valid security token. */
    public static function requireCsrf(): void
    {
        if (Csrf::verify()) {
            return;
        }
        if (Request::wantsJson()) {
            json_out(['ok' => false, 'error' => 'Your session expired. Please reload the page and try again.'], 403);
        }
        Session::flash('error', 'That form had expired, so nothing was changed. Please try again.');
        $back = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $host = (string) parse_url($back, PHP_URL_HOST);
        redirect($back !== '' && $host === parse_url('http://' . Request::host(), PHP_URL_HOST) ? $back : '/admin');
    }

    /** Human label for a top-level content key (used for "unpublished changes" lists). */
    public static function areaLabel(string $key): string
    {
        return [
            'brand' => 'Logo & name', 'theme' => 'Colours', 'business' => 'Business info', 'hours' => 'Opening hours',
            'seo' => 'Search & sharing', 'nav' => 'Menu labels', 'sections' => 'Section order', 'hero' => 'Hero',
            'statement' => 'About', 'strip' => 'Text strip', 'training' => 'Training', 'gym' => 'The Gym',
            'coaches' => 'Coaches', 'reviews' => 'Reviews', 'stories' => 'Member stories', 'membership' => 'Membership',
            'contact' => 'Visit & contact form', 'faq' => 'FAQ', 'footer' => 'Footer', 'privacy' => 'Privacy notice',
        ][$key] ?? ucfirst($key);
    }
}
