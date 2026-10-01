<?php
declare(strict_types=1);

namespace Got;

/** Validation for the public "Request a Callback" form. */
final class EnquiryForm
{
    public const FIELDS = ['name', 'phone', 'goal', 'time', 'message', 'consent'];

    /**
     * Indian mobile numbers (with or without +91 / 0) and international
     * numbers in +country format are accepted.
     * @return array{e164: string, display: string}|null
     */
    public static function normalizePhone(string $raw): ?array
    {
        $compact = (string) preg_replace('/[\s\-().]/', '', $raw);
        if (preg_match('/^(?:\+?91|0)?([6-9]\d{9})$/', $compact, $m)) {
            $e164 = '+91' . $m[1];
            return ['e164' => $e164, 'display' => '+91 ' . substr($m[1], 0, 5) . ' ' . substr($m[1], 5)];
        }
        if (preg_match('/^(?:\+|00)([1-9]\d{7,14})$/', $compact, $m) && !str_starts_with($m[1], '91')) {
            return ['e164' => '+' . $m[1], 'display' => '+' . $m[1]];
        }
        return null;
    }

    /**
     * @return array{0: array, 1: array<string, string>} clean values and errors keyed by field
     */
    public static function validate(array $input): array
    {
        $get = static fn (string $key): string => Sanitizer::cleanText(is_string($input[$key] ?? null) ? $input[$key] : '', $key === 'message');

        $values = [
            'name' => $get('name'),
            'phone' => $get('phone'),
            'goal' => $get('goal'),
            'time' => $get('time'),
            'message' => $get('message'),
            'consent' => in_array($input['consent'] ?? null, ['1', 'on', 'yes'], true),
        ];
        $errors = [];

        $nameLength = mb_strlen($values['name']);
        if ($nameLength === 0) {
            $errors['name'] = 'Please enter your name.';
        } elseif ($nameLength < 2 || $nameLength > 80) {
            $errors['name'] = 'Please enter a name between 2 and 80 characters.';
        } elseif (!preg_match("/^[\p{L}\p{M}][\p{L}\p{M}\s.'\-]*$/u", $values['name'])) {
            $errors['name'] = 'Please use letters only in your name.';
        }

        if ($values['phone'] === '') {
            $errors['phone'] = 'Please enter your mobile number.';
        } elseif (self::normalizePhone($values['phone']) === null) {
            $errors['phone'] = 'Please enter a valid 10-digit mobile number.';
        }

        if ($values['goal'] === '') {
            $errors['goal'] = 'Please choose your fitness goal.';
        } elseif (mb_strlen($values['goal']) > 120) {
            $errors['goal'] = 'Please choose one of the options.';
        }

        if ($values['time'] === '') {
            $errors['time'] = 'Please choose a time that suits you.';
        } elseif (mb_strlen($values['time']) > 120) {
            $errors['time'] = 'Please choose one of the options.';
        }

        if (mb_strlen($values['message']) > 1000) {
            $errors['message'] = 'Please keep your message under 1,000 characters.';
        }

        if (!$values['consent']) {
            $errors['consent'] = 'Please tick the box so the team can contact you about your enquiry.';
        }

        return [$values, $errors];
    }

    /** Messages that link-spam bots typically send. */
    public static function looksLikeSpam(array $values): ?string
    {
        $text = $values['name'] . ' ' . $values['message'];
        if (preg_match_all('#https?://|www\.#i', $text) >= 2) {
            return 'links';
        }
        if (preg_match('/<\s*a\s+href|\[url=/i', $text)) {
            return 'markup';
        }
        return null;
    }
}
