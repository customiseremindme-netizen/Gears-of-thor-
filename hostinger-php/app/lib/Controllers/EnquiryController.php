<?php
declare(strict_types=1);

namespace Got\Controllers;

use Got\Enquiries;
use Got\EnquiryForm;
use Got\FormToken;
use Got\Logger;
use Got\Mailer;
use Got\RateLimiter;
use Got\Request;
use Got\Site;
use Got\Turnstile;

/**
 * Receives the callback form. An enquiry is reported as received only after
 * it has been saved in the database; on any problem the visitor keeps what
 * they typed and can try again.
 */
final class EnquiryController
{
    private static ?string $keepToken = null;

    public static function token(): void
    {
        header('X-Robots-Tag: noindex');
        json_out(['ok' => true, 'token' => FormToken::issue()]);
    }

    public static function submit(): void
    {
        $site = Site::build(false);
        $phone = $site->phone();
        $callUs = $phone !== '' ? " or call us on {$phone}" : '';

        // 1. Form token: proves the form was loaded from this site recently.
        $submittedToken = Request::post('_token');
        $tokenState = FormToken::check($submittedToken);
        if ($tokenState === 'invalid') {
            self::fail($site, 400, 'invalid_token', 'Your form expired. Please press the button again.');
        }
        if ($tokenState === 'expired') {
            self::fail($site, 409, 'expired', 'This page was open for a long time. Please press the button again to send your enquiry.');
        }
        // After a correctable error the visitor keeps their original token, so a
        // quick second try is not mistaken for a robot.
        self::$keepToken = $submittedToken;

        // 2. Validate what the visitor typed.
        [$values, $errors] = EnquiryForm::validate($_POST);
        if ($errors) {
            self::fail($site, 422, 'validation', 'Please check the highlighted fields.', $values, $errors);
        }

        // 3. Limit repeated submissions from one connection.
        $ip = Request::ip();
        try {
            $limited = RateLimiter::tooMany('enquiry', $ip, 5, 600) || RateLimiter::tooMany('enquiry_day', $ip, 20, 86400);
        } catch (\Throwable $e) {
            Logger::error('Rate limit check failed: ' . $e->getMessage());
            $limited = false;
        }
        if ($limited) {
            self::fail($site, 429, 'rate_limited', "You have sent several enquiries in a short time. Please try again later{$callUs}.", $values);
        }

        // 4. Optional Cloudflare Turnstile check.
        if (Turnstile::enabled()) {
            $result = Turnstile::verify(Request::post('cf-turnstile-response'), $ip);
            if ($result === 'failed') {
                self::fail($site, 422, 'turnstile', 'Please complete the security check and try again.', $values);
            }
        }

        // 5. Quiet spam signals: saved, but filed under Spam for review.
        $spamReason = null;
        if (Request::post('hp_note') !== '') {
            $spamReason = 'hidden field filled';
        } elseif ($tokenState === 'too_fast') {
            $spamReason = 'sent too quickly';
        } elseif ($reason = EnquiryForm::looksLikeSpam($values)) {
            $spamReason = 'suspicious ' . $reason;
        }

        $normalized = EnquiryForm::normalizePhone($values['phone']);
        $record = [
            'name' => $values['name'],
            'phone' => $normalized['e164'],
            'phone_display' => $normalized['display'],
            'goal' => mb_substr($values['goal'], 0, 120),
            'contact_time' => mb_substr($values['time'], 0, 120),
            'message' => $values['message'],
            'is_spam' => $spamReason !== null,
            'spam_reason' => $spamReason,
            'consent_text' => $site->str('contact.form.consent'),
            'ip_hash' => RateLimiter::hashKey($ip),
            'user_agent' => Request::userAgent(),
        ];

        // 6. Save. Only now do we tell the visitor it worked.
        try {
            RateLimiter::hit('enquiry', $ip);
            RateLimiter::hit('enquiry_day', $ip);
            $id = Enquiries::recentDuplicate($record['phone'], $record['message']);
            $isNew = $id === null;
            if ($isNew) {
                $id = Enquiries::create($record);
            }
        } catch (\Throwable $e) {
            Logger::error('Enquiry could not be saved: ' . $e->getMessage());
            self::fail($site, 500, 'server', "Sorry — we couldn’t save your enquiry just now. Your details are still in the form, so please try again{$callUs}.", $values);
        }

        $message = $site->str('contact.form.success');
        if (Request::wantsJson()) {
            self::respondThenNotify(
                ['ok' => true, 'message' => $message, 'token' => FormToken::issue()],
                $isNew && !$record['is_spam'] ? $id : null,
                $record
            );
        }
        if ($isNew && !$record['is_spam']) {
            Mailer::notifyNewEnquiry($record + ['id' => $id]);
        }
        redirect('/?enquiry=sent&ref=' . rawurlencode(SiteController::receipt($id)) . '#enquire');
    }

    /** Send the JSON answer first, then the optional email, so visitors never wait for email. */
    private static function respondThenNotify(array $payload, ?int $id, array $record): never
    {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        header('Content-Length: ' . strlen((string) $body));
        echo $body;
        if ($id !== null) {
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            } elseif (function_exists('litespeed_finish_request')) {
                litespeed_finish_request();
            } else {
                @ob_flush();
                flush();
            }
            Mailer::notifyNewEnquiry($record + ['id' => $id]);
        }
        exit;
    }

    private static function fail(Site $site, int $status, string $code, string $message, array $values = [], array $errors = []): never
    {
        $token = self::$keepToken ?? FormToken::issue();
        if (Request::wantsJson()) {
            json_out([
                'ok' => false,
                'code' => $code,
                'message' => $message,
                'errors' => $errors,
                'token' => $token,
            ], $status);
        }
        // Without JavaScript: show the page again with the visitor's details kept.
        SiteController::render($site, [
            'status' => 'error',
            'values' => $values ?: array_map(static fn ($v) => is_string($v) ? $v : '', array_intersect_key($_POST, array_flip(EnquiryForm::FIELDS))),
            'errors' => $errors,
            'message' => $message,
            'token' => $token,
        ], $status);
        exit;
    }
}
