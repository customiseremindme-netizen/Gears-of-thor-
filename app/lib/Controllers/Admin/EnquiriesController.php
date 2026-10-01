<?php
declare(strict_types=1);

namespace Got\Controllers\Admin;

use Got\AdminView;
use Got\Auth;
use Got\Csv;
use Got\Enquiries;
use Got\Request;
use Got\Session;
use Got\Settings;

/** Enquiries from the website: list, follow-up status, notes and CSV export. */
final class EnquiriesController
{
    private const PER_PAGE = 25;

    public static function index(): void
    {
        Auth::require('enquiries');
        if (Settings::get('spam_auto_delete', true) && random_int(1, 20) === 1) {
            Enquiries::purgeSpam(30);
        }
        $filters = self::filters();
        $page = max(1, (int) Request::query('page', '1'));
        [$rows, $total] = Enquiries::search($filters, $page, self::PER_PAGE);

        AdminView::render('enquiries', [
            'title' => 'Enquiries',
            'nav' => 'enquiries',
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / self::PER_PAGE)),
            'filters' => $filters,
            'counts' => Enquiries::counts(),
            'returnTo' => self::currentListUrl(),
        ]);
    }

    public static function show(int $id): void
    {
        Auth::require('enquiries');
        $enquiry = Enquiries::find($id);
        if (!$enquiry) {
            Session::flash('error', 'That enquiry no longer exists.');
            redirect('/admin/enquiries');
        }
        AdminView::render('enquiry', [
            'title' => 'Enquiry from ' . $enquiry['name'],
            'nav' => 'enquiries',
            'enquiry' => $enquiry,
            'events' => Enquiries::events($id),
            'back' => self::safeReturn(Request::query('return'), '/admin/enquiries'),
        ]);
    }

    public static function update(int $id): void
    {
        $user = Auth::require('enquiries');
        AdminView::requireCsrf();
        if (!Enquiries::find($id)) {
            redirect('/admin/enquiries');
        }
        Enquiries::setStatus($id, Request::post('status'), $user['id']);
        $notes = mb_substr(str_replace("\r\n", "\n", (string) ($_POST['notes'] ?? '')), 0, 5000);
        Enquiries::setNotes($id, trim($notes), $user['id']);
        Session::flash('success', 'Enquiry updated.');
        redirect('/admin/enquiries/' . $id);
    }

    /** Quick status change from the list. */
    public static function status(int $id): void
    {
        $user = Auth::require('enquiries');
        AdminView::requireCsrf();
        if (Enquiries::setStatus($id, Request::post('status'), $user['id'])) {
            Session::flash('success', 'Status updated to ' . (Enquiries::STATUSES[Request::post('status')] ?? '') . '.');
        }
        redirect(self::safeReturn(Request::post('return'), '/admin/enquiries'));
    }

    public static function spam(int $id): void
    {
        $user = Auth::require('enquiries');
        AdminView::requireCsrf();
        $spam = Request::post('spam') === '1';
        Enquiries::setSpam($id, $spam, $user['id']);
        Session::flash('success', $spam ? 'Moved to Spam.' : 'Moved back to your enquiries.');
        redirect('/admin/enquiries/' . $id);
    }

    public static function delete(int $id): void
    {
        Auth::require('enquiries.delete');
        AdminView::requireCsrf();
        Enquiries::delete($id);
        Session::flash('success', 'Enquiry deleted permanently.');
        redirect('/admin/enquiries');
    }

    public static function purgeSpam(): void
    {
        Auth::require('enquiries.delete');
        AdminView::requireCsrf();
        $count = Enquiries::purgeSpam(0);
        Session::flash('success', $count === 1 ? '1 spam enquiry deleted.' : "{$count} spam enquiries deleted.");
        redirect('/admin/enquiries?view=spam');
    }

    public static function export(): void
    {
        Auth::require('enquiries.export');
        $filters = self::filters();
        $rows = Enquiries::exportRows($filters);
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                $row['id'],
                ist($row['created_at'], 'Y-m-d H:i'),
                $row['name'],
                Enquiries::localPhone((string) $row['phone']),
                $row['goal'],
                $row['contact_time'],
                (string) $row['message'],
                Enquiries::STATUSES[$row['status']] ?? $row['status'],
                (string) $row['notes'],
                ist($row['consent_at'], 'Y-m-d H:i'),
            ];
        }
        $suffix = ($filters['view'] ?? '') === 'spam' ? '-spam' : (($filters['status'] ?? '') !== '' ? '-' . $filters['status'] : '');
        Csv::download(
            'got-fitnezz-enquiries' . $suffix . '-' . ist(now_utc(), 'Y-m-d') . '.csv',
            ['ID', 'Received (IST)', 'Name', 'Mobile', 'Fitness goal', 'Preferred contact time', 'Message', 'Status', 'Notes', 'Consent given (IST)'],
            $out
        );
    }

    private static function filters(): array
    {
        $status = Request::query('status');
        return [
            'status' => isset(Enquiries::STATUSES[$status]) ? $status : '',
            'view' => Request::query('view') === 'spam' ? 'spam' : '',
            'q' => mb_substr(Request::query('q'), 0, 80),
            'from' => Request::query('from'),
            'to' => Request::query('to'),
        ];
    }

    private static function currentListUrl(): string
    {
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
        return '/admin/enquiries' . ($query !== '' ? '?' . $query : '');
    }

    /** Only allow returning to a dashboard enquiries page. */
    private static function safeReturn(string $target, string $fallback): string
    {
        return preg_match('#^/admin/enquiries(\?[^\s<>"\']*)?$#', $target) ? $target : $fallback;
    }
}
