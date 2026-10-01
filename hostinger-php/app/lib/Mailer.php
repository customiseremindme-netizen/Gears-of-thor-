<?php
declare(strict_types=1);

namespace Got;

/**
 * Optional email alerts for new enquiries, sent with the hosting's built-in
 * mail function. Enquiries are always saved first; an email problem never
 * loses an enquiry.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !function_exists('mail')) {
            return false;
        }
        $host = (string) preg_replace('/^www\./', '', explode(':', Request::host())[0]);
        $from = (string) Settings::get('notify_from', '');
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $from = 'website@' . ($host !== '' && $host !== 'localhost' ? $host : 'example.com');
        }
        $headers = [
            'From: GOT FITNEZZ Website <' . $from . '>',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'X-Mailer: GOT FITNEZZ website',
        ];
        $subject = '=?UTF-8?B?' . base64_encode(str_replace(["\r", "\n"], ' ', $subject)) . '?=';
        try {
            return @mail($to, $subject, str_replace("\n", "\r\n", $body), implode("\r\n", $headers));
        } catch (\Throwable $e) {
            Logger::warning('Email could not be sent: ' . $e->getMessage());
            return false;
        }
    }

    public static function notifyNewEnquiry(array $enquiry): void
    {
        if (!Settings::get('notify_enabled', false)) {
            return;
        }
        $to = (string) Settings::get('notify_email', '');
        if ($to === '') {
            return;
        }
        $body = "A new enquiry has arrived on the GOT FITNEZZ website.\n\n"
            . 'Name: ' . $enquiry['name'] . "\n"
            . 'Mobile: ' . $enquiry['phone_display'] . "\n"
            . 'Fitness goal: ' . $enquiry['goal'] . "\n"
            . 'Preferred contact time: ' . $enquiry['contact_time'] . "\n"
            . ($enquiry['message'] !== '' ? "Message:\n" . $enquiry['message'] . "\n" : '')
            . "\nOpen the dashboard to update its status:\n" . abs_url('/admin/enquiries/' . $enquiry['id']) . "\n";
        if (!self::send($to, 'New enquiry from ' . $enquiry['name'], $body)) {
            Logger::warning('New-enquiry email could not be sent', ['enquiry' => $enquiry['id']]);
        }
    }
}
