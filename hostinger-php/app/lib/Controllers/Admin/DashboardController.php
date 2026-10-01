<?php
declare(strict_types=1);

namespace Got\Controllers\Admin;

use Got\AdminView;
use Got\Auth;
use Got\Checklist;
use Got\Db;
use Got\Enquiries;
use Got\Markdown;
use Got\Request;
use Got\Session;
use Got\Settings;

/** Dashboard overview, owner checklist and the built-in guide. */
final class DashboardController
{
    public static function index(): void
    {
        $user = Auth::require('enquiries');
        $counts = Enquiries::counts();
        [$recent] = Enquiries::search(['status' => 'new'], 1, 5);

        $published = null;
        $checklist = [];
        if (Auth::can('content')) {
            $published = Db::get()->one(
                "SELECT c.updated_at, u.name AS user_name FROM content c LEFT JOIN users u ON u.id = c.updated_by WHERE c.name = 'published'"
            );
            $checklist = Checklist::items();
        }

        AdminView::render('dashboard', [
            'title' => 'Overview',
            'nav' => 'overview',
            'counts' => $counts,
            'recent' => $recent,
            'published' => $published,
            'checklist' => $checklist,
            'progress' => $checklist ? Checklist::progress($checklist) : null,
            'firstName' => explode(' ', (string) $user['name'])[0],
        ]);
    }

    public static function checklist(): void
    {
        Auth::require('content');
        $items = Checklist::items();
        AdminView::render('checklist', [
            'title' => 'Owner checklist',
            'nav' => 'checklist',
            'items' => $items,
            'progress' => Checklist::progress($items),
        ]);
    }

    public static function saveChecklist(): void
    {
        Auth::require('content');
        AdminView::requireCsrf();
        $key = Request::post('key');
        if (array_key_exists($key, Checklist::MANUAL)) {
            $done = Request::post('done') === '1';
            Settings::set($key, $done);
            Session::flash('success', $done ? 'Marked as done.' : 'Marked as not done.');
        }
        redirect('/admin/checklist');
    }

    public static function help(): void
    {
        Auth::require('enquiries');
        $file = APP_ROOT . '/docs/OWNER-GUIDE.md';
        $markdown = is_file($file) ? (string) file_get_contents($file) : '# Guide not found';
        // The first heading becomes the page title.
        $markdown = (string) preg_replace('/^#\s+.*\n/', '', $markdown, 1);
        $html = Markdown::render($markdown);
        AdminView::render('help', [
            'title' => 'How-to guide',
            'nav' => 'help',
            'html' => $html,
            'toc' => array_filter(Markdown::$headings, static fn ($h) => $h['level'] === 2),
        ]);
    }
}
