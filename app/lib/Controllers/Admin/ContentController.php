<?php
declare(strict_types=1);

namespace Got\Controllers\Admin;

use Got\AdminView;
use Got\Auth;
use Got\Content;
use Got\Media;
use Got\Request;
use Got\Sanitizer;
use Got\Schema;
use Got\Session;
use Got\Site;

/** Editing website content. Every change is saved to the draft until published. */
final class ContentController
{
    public static function index(): void
    {
        Auth::require('content');
        $draft = Content::load('draft');
        $site = Site::fromContent($draft, true);
        AdminView::render('content-index', [
            'title' => 'Page sections',
            'nav' => 'content',
            'site' => $site,
            'pages' => Schema::pages(),
        ]);
    }

    public static function saveSections(): void
    {
        $user = Auth::require('content');
        AdminView::requireCsrf();
        $order = array_values(array_filter((array) ($_POST['order'] ?? []), static fn ($id) => is_string($id) && isset(Schema::SECTIONS[$id])));
        $on = (array) ($_POST['on'] ?? []);

        Content::updateDraft(static function (array $draft) use ($order, $on): array {
            $sections = [];
            foreach (array_unique($order) as $id) {
                $sections[] = ['id' => $id, 'on' => (Schema::SECTIONS[$id]['locked'] ?? false) || !empty($on[$id])];
            }
            // Keep any section that was not in the form at the end.
            foreach (array_keys(Schema::SECTIONS) as $id) {
                if (!in_array($id, $order, true)) {
                    $sections[] = ['id' => $id, 'on' => true];
                }
            }
            // The hero always stays first.
            usort($sections, static fn ($a, $b) => ($b['id'] === 'hero') <=> ($a['id'] === 'hero'));
            $draft['sections'] = $sections;
            return $draft;
        }, $user['id']);

        Session::flash('success', 'Section order saved as a draft. Preview it, then publish when you are happy.');
        redirect('/admin/content');
    }

    public static function edit(string $slug): void
    {
        Auth::require('content');
        $page = Schema::page($slug);
        if (!$page) {
            redirect('/admin/content');
        }
        self::form($slug, $page, Content::load('draft'), []);
    }

    public static function save(string $slug): void
    {
        $user = Auth::require('content');
        $page = Schema::page($slug);
        if (!$page) {
            redirect('/admin/content');
        }
        AdminView::requireCsrf();

        $input = is_array($_POST['f'] ?? null) ? $_POST['f'] : [];
        $result = Sanitizer::page($page, $input);

        if ($result['errors']) {
            $display = Content::load('draft');
            foreach ($result['values'] as $path => $value) {
                array_set($display, $path, $value);
            }
            Session::flash('error', 'Nothing was saved yet — please fix the items marked in red.');
            self::form($slug, $page, $display, $result['errors'], 422);
            return;
        }

        Content::updateDraft(static function (array $draft) use ($result): array {
            foreach ($result['values'] as $path => $value) {
                array_set($draft, $path, is_array($value) && !self::isHoursPath($path) ? array_values($value) : $value);
            }
            return $draft;
        }, $user['id']);

        Session::flash('success', 'Saved as a draft. Preview your changes, then press Publish to make them live.');
        if (Request::post('then') === 'preview') {
            redirect('/admin/preview');
        }
        redirect('/admin/content/' . $slug);
    }

    private static function isHoursPath(string $path): bool
    {
        return $path === 'hours.days';
    }

    private static function form(string $slug, array $page, array $values, array $errors, int $status = 200): void
    {
        Media::preload(array_keys(Content::mediaIds($values)));
        AdminView::render('content-edit', [
            'title' => $page['title'],
            'nav' => match ($page['menu']) {
                'business' => 'business',
                'brand' => 'brand',
                'seo' => 'seo',
                default => 'content',
            },
            'slug' => $slug,
            'page' => $page,
            'values' => $values,
            'errors' => $errors,
        ], $status);
    }
}
