<?php
declare(strict_types=1);

namespace Got;

/** Validates and cleans dashboard form input using the Schema. */
final class Sanitizer
{
    private static ?array $mediaKinds = null;

    /**
     * @return array{values: array<string, mixed>, errors: array<string, string>}
     *   values are keyed by content path; errors by form path.
     */
    public static function page(array $page, array $input): array
    {
        $values = [];
        $errors = [];
        foreach (Schema::fields($page) as $field) {
            $raw = array_get($input, $field['path']);
            if ($field['type'] === 'list') {
                [$items, $itemErrors] = self::list($field, $raw);
                $values[$field['path']] = $items;
                $errors += $itemErrors;
                continue;
            }
            [$value, $error] = self::value($field, $raw);
            $values[$field['path']] = $value;
            if ($error !== null) {
                $errors[$field['path']] = $error;
            }
        }
        return ['values' => $values, 'errors' => $errors];
    }

    /** @return array{0: array, 1: array<string, string>} */
    private static function list(array $field, mixed $raw): array
    {
        $items = [];
        $errors = [];
        if (!is_array($raw)) {
            return [[], []];
        }
        foreach ($raw as $key => $itemRaw) {
            if (!is_array($itemRaw)) {
                continue;
            }
            $item = [];
            $itemErrors = [];
            foreach ($field['fields'] as $sub) {
                [$value, $error] = self::value($sub, $itemRaw[$sub['key']] ?? null);
                $item[$sub['key']] = $value;
                if ($error !== null) {
                    $itemErrors[$field['path'] . '.' . $key . '.' . $sub['key']] = $error;
                }
            }
            if (self::isBlank($field['fields'], $item)) {
                continue; // an empty, unused row is simply dropped
            }
            $items[(string) $key] = $item;
            $errors += $itemErrors;
        }
        $max = (int) ($field['max_items'] ?? 50);
        if (count($items) > $max) {
            $errors[$field['path']] = "You can add up to {$max}. Please remove some first.";
        }
        return [$items, $errors];
    }

    private static function isBlank(array $fields, array $item): bool
    {
        foreach ($fields as $sub) {
            if (in_array($sub['type'], ['toggle', 'select'], true)) {
                continue;
            }
            $value = $item[$sub['key']] ?? null;
            if ($value !== null && $value !== '' && $value !== []) {
                return false;
            }
        }
        return true;
    }

    /** @return array{0: mixed, 1: ?string} */
    public static function value(array $field, mixed $raw): array
    {
        $required = (bool) ($field['required'] ?? false);
        $type = $field['type'];

        switch ($type) {
            case 'toggle':
                return [in_array($raw, ['1', 1, true, 'on', 'yes'], true), null];

            case 'select':
                $options = $field['options'] ?? [];
                $value = is_string($raw) && array_key_exists($raw, $options) ? $raw : (string) array_key_first($options);
                return [$value, null];

            case 'image':
            case 'video':
                $id = is_numeric($raw) ? (int) $raw : 0;
                if ($id > 0 && (self::mediaKinds()[$id] ?? null) === $type) {
                    return [$id, null];
                }
                return [null, $required ? 'Please choose a ' . ($type === 'video' ? 'video' : 'photo') . '.' : null];

            case 'hours':
                return self::hours($raw);

            case 'color':
                $value = strtoupper(trim(is_string($raw) ? $raw : ''));
                if ($value !== '' && $value[0] !== '#') {
                    $value = '#' . $value;
                }
                if (!preg_match('/^#[0-9A-F]{6}$/', $value)) {
                    return [(string) ($field['default'] ?? '#000000'), 'Please use a colour such as #D8E12A.'];
                }
                return [$value, null];
        }

        $multiline = $type === 'textarea';
        $value = self::cleanText(is_string($raw) ? $raw : '', $multiline);
        $max = (int) ($field['max'] ?? ($multiline ? 2000 : 200));
        if (mb_strlen($value) > $max) {
            return [mb_substr($value, 0, $max), "Please keep this under {$max} characters."];
        }
        if ($value === '') {
            return ['', $required ? 'This is required.' : null];
        }

        switch ($type) {
            case 'url':
                if (!preg_match('#^[a-z][a-z0-9+.\-]*://#i', $value) && preg_match('/^[\w\-]+(\.[\w\-]+)+/u', $value)) {
                    $value = 'https://' . $value;
                }
                $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
                if (!in_array($scheme, ['http', 'https'], true) || !filter_var($value, FILTER_VALIDATE_URL)) {
                    return [$value, 'Please enter a full web address starting with https://'];
                }
                return [$value, null];

            case 'tel':
                $value = (string) preg_replace('/[^\d+\s\-()]/', '', $value);
                $digits = strlen((string) preg_replace('/\D/', '', $value));
                if ($digits < 8 || $digits > 15) {
                    return [$value, 'Please enter a valid phone number, for example +91 86086 11123.'];
                }
                return [trim((string) preg_replace('/\s+/', ' ', $value)), null];

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return [$value, 'Please enter a valid email address.'];
                }
                return [strtolower($value), null];
        }

        return [$value, null];
    }

    /** Clean pasted text: valid UTF-8, no control characters, tidy line breaks. */
    public static function cleanText(string $value, bool $multiline): string
    {
        $value = mb_scrub($value, 'UTF-8');
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        if (!$multiline) {
            $value = str_replace(["\n", "\t"], ' ', $value);
        } else {
            $value = (string) preg_replace("/\n{3,}/", "\n\n", $value);
        }
        return trim($value);
    }

    /** @return array{0: array, 1: ?string} */
    private static function hours(mixed $raw): array
    {
        $days = [];
        $problem = null;
        foreach (array_keys(Hours::DAYS) as $day) {
            $sessions = [];
            $rows = is_array($raw[$day] ?? null) ? $raw[$day] : [];
            foreach ($rows as $row) {
                $open = is_array($row) ? trim((string) ($row['open'] ?? '')) : '';
                $close = is_array($row) ? trim((string) ($row['close'] ?? '')) : '';
                if ($open === '' && $close === '') {
                    continue;
                }
                $validOpen = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $open);
                $validClose = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $close);
                if (!$validOpen || !$validClose) {
                    $problem ??= 'Please give both an opening and a closing time for ' . Hours::DAYS[$day] . '.';
                    continue;
                }
                if ($close <= $open) {
                    $problem ??= 'On ' . Hours::DAYS[$day] . ' the closing time must be after the opening time.';
                    continue;
                }
                $sessions[] = ['open' => $open, 'close' => $close];
            }
            usort($sessions, static fn ($a, $b) => strcmp($a['open'], $b['open']));
            for ($i = 1; $i < count($sessions); $i++) {
                if ($sessions[$i]['open'] < $sessions[$i - 1]['close']) {
                    $problem ??= 'The times for ' . Hours::DAYS[$day] . ' overlap.';
                }
            }
            $days[$day] = array_slice($sessions, 0, 3);
        }
        return [$days, $problem];
    }

    private static function mediaKinds(): array
    {
        return self::$mediaKinds ??= Media::kindMap();
    }

    public static function reset(): void
    {
        self::$mediaKinds = null;
    }
}
