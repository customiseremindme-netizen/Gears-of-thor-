<?php
declare(strict_types=1);

namespace Got\Controllers\Admin;

use Got\AdminView;
use Got\Auth;
use Got\Mailer;
use Got\Request;
use Got\Sanitizer;
use Got\Session;
use Got\Settings;

/** Owner-only settings: website address, email alerts and spam protection. */
final class SettingsController
{
    public static function index(): void
    {
        Auth::require('settings');
        AdminView::render('settings', [
            'title' => 'Settings',
            'nav' => 'settings',
            's' => [
                'site_url' => (string) Settings::get('site_url', ''),
                'notify_enabled' => (bool) Settings::get('notify_enabled', false),
                'notify_email' => (string) Settings::get('notify_email', ''),
                'notify_from' => (string) Settings::get('notify_from', ''),
                'turnstile_enabled' => (bool) Settings::get('turnstile_enabled', false),
                'turnstile_site_key' => (string) Settings::get('turnstile_site_key', ''),
                'turnstile_secret_set' => (string) Settings::get('turnstile_secret_key', '') !== '',
                'spam_auto_delete' => (bool) Settings::get('spam_auto_delete', true),
            ],
            'errors' => [],
        ]);
    }

    public static function save(): void
    {
        Auth::require('settings');
        AdminView::requireCsrf();
        $errors = [];

        $siteUrl = rtrim(Sanitizer::cleanText(Request::post('site_url'), false), '/');
        if ($siteUrl !== '' && (!filter_var($siteUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $siteUrl))) {
            $errors[] = 'The website address must start with https:// (for example https://gotfitnezz.in).';
        }
        $notifyEmail = strtolower(Request::post('notify_email'));
        if ($notifyEmail !== '' && !filter_var($notifyEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address for enquiry alerts.';
        }
        $notifyFrom = strtolower(Request::post('notify_from'));
        if ($notifyFrom !== '' && !filter_var($notifyFrom, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid “send from” email address, or leave it empty.';
        }
        $notifyEnabled = Request::post('notify_enabled') === '1';
        if ($notifyEnabled && $notifyEmail === '') {
            $errors[] = 'Add the email address that should receive enquiry alerts.';
        }
        $siteKey = Sanitizer::cleanText(Request::post('turnstile_site_key'), false);
        $secret = Sanitizer::cleanText(Request::post('turnstile_secret_key'), false);
        $turnstileOn = Request::post('turnstile_enabled') === '1';
        $hasSecret = $secret !== '' || (string) Settings::get('turnstile_secret_key', '') !== '';
        if ($turnstileOn && ($siteKey === '' || !$hasSecret)) {
            $errors[] = 'To switch on Cloudflare Turnstile, add both the site key and the secret key.';
        }

        if ($errors) {
            foreach ($errors as $error) {
                Session::flash('error', $error);
            }
            redirect('/admin/settings');
        }

        if ($siteUrl !== '') {
            Settings::set('site_url', $siteUrl);
        }
        Settings::set('notify_enabled', $notifyEnabled);
        Settings::set('notify_email', $notifyEmail);
        Settings::set('notify_from', $notifyFrom);
        Settings::set('turnstile_enabled', $turnstileOn);
        Settings::set('turnstile_site_key', $siteKey);
        if ($secret !== '') {
            Settings::set('turnstile_secret_key', $secret);
        }
        if (Request::post('turnstile_clear') === '1') {
            Settings::set('turnstile_secret_key', '');
            Settings::set('turnstile_enabled', false);
        }
        Settings::set('spam_auto_delete', Request::post('spam_auto_delete') === '1');

        Session::flash('success', 'Settings saved.');
        redirect('/admin/settings');
    }

    public static function testEmail(): void
    {
        Auth::require('settings');
        AdminView::requireCsrf();
        $to = (string) Settings::get('notify_email', '');
        if ($to === '') {
            Session::flash('error', 'Save an email address for enquiry alerts first.');
            redirect('/admin/settings');
        }
        $sent = Mailer::send($to, 'Test email from your GOT FITNEZZ website', "This is a test.\n\nIf you can read this, enquiry alerts will reach this inbox.\nIf it went to spam, mark it as “Not spam”.\n");
        Session::flash($sent ? 'success' : 'error', $sent
            ? "Test email sent to {$to}. If it does not arrive within a few minutes, check the spam folder."
            : 'The server could not send email. In hPanel, check that email sending is enabled for this website (Emails → Email service / PHP mail).');
        redirect('/admin/settings');
    }
}
