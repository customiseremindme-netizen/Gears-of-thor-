<?php
declare(strict_types=1);

namespace Got\Controllers\Admin;

use Got\AdminView;
use Got\Auth;
use Got\Content;
use Got\Logger;
use Got\Request;
use Got\Session;

/** Preview the draft, publish it, or go back to an earlier version. */
final class PublishController
{
    public static function preview(): void
    {
        Auth::require('content');
        AdminView::render('preview', [
            'title' => 'Preview & publish',
            'nav' => 'preview',
            'device' => in_array(Request::query('device'), ['desktop', 'tablet', 'phone'], true) ? Request::query('device') : 'desktop',
        ]);
    }

    public static function publish(): void
    {
        $user = Auth::require('publish');
        AdminView::requireCsrf();
        $changed = Content::changedKeys();
        if (!$changed) {
            Session::flash('info', 'There were no unpublished changes — the website is already up to date.');
            self::back();
        }
        $note = 'Updated: ' . implode(', ', array_map([AdminView::class, 'areaLabel'], $changed));
        Content::publish($user['id'], $note);
        Logger::info('Website published', ['user' => $user['id'], 'changes' => $changed]);
        Session::flash('success', 'Published! Your changes are now live on the website.');
        self::back();
    }

    public static function discard(): void
    {
        $user = Auth::require('publish');
        AdminView::requireCsrf();
        Content::discard($user['id']);
        Session::flash('success', 'Unpublished changes were discarded. The draft now matches the live website.');
        self::back();
    }

    public static function history(): void
    {
        Auth::require('publish');
        AdminView::render('history', [
            'title' => 'Publish history',
            'nav' => 'history',
            'entries' => Content::history(),
        ]);
    }

    public static function restore(int $id): void
    {
        $user = Auth::require('publish');
        AdminView::requireCsrf();
        if (Content::restore($id, $user['id'])) {
            Session::flash('success', 'That version is now in your draft. Preview it, then publish to put it live.');
            redirect('/admin/preview');
        }
        Session::flash('error', 'That version could not be found.');
        redirect('/admin/history');
    }

    private static function back(): never
    {
        $target = Request::post('return');
        redirect(preg_match('#^/admin(/[a-z0-9/\-]*)?$#', $target) ? $target : '/admin/preview');
    }
}
