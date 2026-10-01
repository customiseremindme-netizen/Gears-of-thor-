<?php
declare(strict_types=1);

namespace Got;

/** Builds dashboard form fields from the Schema definitions. */
final class FormRenderer
{
    /** "hero.headline" → "f[hero][headline]" */
    public static function name(string $path): string
    {
        $parts = explode('.', $path);
        return 'f[' . implode('][', $parts) . ']';
    }

    public static function id(string $path): string
    {
        return 'fld-' . preg_replace('/[^a-z0-9]+/i', '-', $path);
    }

    /** Render one top-level field of a page. */
    public static function field(array $field, array $values, array $errors): string
    {
        $path = $field['path'];
        $value = array_get($values, $path);
        if ($field['type'] === 'list') {
            return self::listField($field, is_array($value) ? $value : [], $errors);
        }
        return self::control($field, $value, $path, $errors[$path] ?? null);
    }

    /**
     * A single control. $path is used for the input name and for matching errors.
     */
    public static function control(array $field, mixed $value, string $path, ?string $error): string
    {
        $type = $field['type'];
        $id = self::id($path);
        $name = self::name($path);
        $label = e($field['label'] ?? '');
        $required = !empty($field['required']);
        $help = isset($field['help']) ? '<p class="fld__help" id="' . $id . '-help">' . e($field['help']) . '</p>' : '';
        $describedBy = trim((isset($field['help']) ? $id . '-help ' : '') . ($error ? $id . '-error' : ''));
        $aria = ($describedBy !== '' ? ' aria-describedby="' . $describedBy . '"' : '') . ($error ? ' aria-invalid="true"' : '');
        $errorHtml = $error ? '<p class="fld__error" id="' . $id . '-error">' . e($error) . '</p>' : '';
        $req = $required ? ' <span class="req" title="Required">*</span>' : '';
        $class = 'fld fld--' . $type . ($error ? ' has-error' : '');

        switch ($type) {
            case 'toggle':
                $checked = $value ? ' checked' : '';
                return '<div class="' . $class . '">'
                    . '<label class="switch" for="' . $id . '">'
                    . '<input type="checkbox" id="' . $id . '" name="' . $name . '" value="1"' . $checked . $aria . ' data-toggle-input>'
                    . '<span class="switch__track" aria-hidden="true"></span>'
                    . '<span class="switch__label">' . $label . '</span>'
                    . '</label>' . $help . $errorHtml . '</div>';

            case 'select':
                $options = '';
                foreach ($field['options'] as $optValue => $optLabel) {
                    $options .= '<option value="' . e((string) $optValue) . '"' . ((string) $value === (string) $optValue ? ' selected' : '') . '>' . e($optLabel) . '</option>';
                }
                return '<div class="' . $class . '"><label for="' . $id . '">' . $label . $req . '</label>' . $help
                    . '<select id="' . $id . '" name="' . $name . '"' . $aria . '>' . $options . '</select>' . $errorHtml . '</div>';

            case 'textarea':
                $rows = (int) ($field['rows'] ?? 3);
                $max = (int) ($field['max'] ?? 2000);
                $counter = isset($field['counter']) ? ' data-counter="' . (int) $field['counter'] . '"' : '';
                return '<div class="' . $class . '"><label for="' . $id . '">' . $label . $req . '</label>' . $help
                    . '<textarea id="' . $id . '" name="' . $name . '" rows="' . $rows . '" maxlength="' . $max . '"' . $counter . ($required ? ' required' : '') . $aria . '>'
                    . e(is_string($value) ? $value : '') . '</textarea>' . $errorHtml . '</div>';

            case 'color':
                $hex = is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? strtoupper($value) : (string) ($field['default'] ?? '#000000');
                return '<div class="' . $class . '" data-color-field><label for="' . $id . '">' . $label . '</label>' . $help
                    . '<div class="color-input">'
                    . '<input type="color" value="' . e($hex) . '" aria-label="' . $label . ' picker" data-color-picker>'
                    . '<input type="text" id="' . $id . '" name="' . $name . '" value="' . e($hex) . '" maxlength="7" pattern="#?[0-9A-Fa-f]{6}" spellcheck="false" autocomplete="off"' . $aria . ' data-color-text>'
                    . (isset($field['default']) ? '<button type="button" class="btn-link" data-color-reset="' . e((string) $field['default']) . '">Reset</button>' : '')
                    . '</div>' . $errorHtml . '</div>';

            case 'image':
            case 'video':
                return self::mediaField($field, $value, $id, $name, $label . $req, $help, $errorHtml, $class);

            case 'hours':
                return self::hoursField(is_array($value) ? $value : [], $path, $error);
        }

        $inputType = match ($type) {
            'email' => 'email',
            'tel' => 'tel',
            default => 'text',
        };
        $extra = match ($type) {
            'url' => ' inputmode="url" autocapitalize="off" spellcheck="false"',
            'email' => ' autocapitalize="off" spellcheck="false"',
            'tel' => ' inputmode="tel"',
            default => '',
        };
        $max = (int) ($field['max'] ?? ($type === 'url' ? 500 : 200));
        $counter = isset($field['counter']) ? ' data-counter="' . (int) $field['counter'] . '"' : '';
        return '<div class="' . $class . '"><label for="' . $id . '">' . $label . $req . '</label>' . $help
            . '<input type="' . $inputType . '" id="' . $id . '" name="' . $name . '" value="' . e(is_scalar($value) ? (string) $value : '') . '" maxlength="' . $max . '"' . $extra . $counter . ($required ? ' required' : '') . $aria . '>'
            . $errorHtml . '</div>';
    }

    private static function mediaField(array $field, mixed $value, string $id, string $name, string $label, string $help, string $errorHtml, string $class): string
    {
        $kind = $field['type'];
        $media = is_int($value) ? Media::find($value) : null;
        if ($media && $media['kind'] !== $kind) {
            $media = null;
        }
        $preview = $media
            ? ($kind === 'image'
                ? '<img src="' . e(Media::urlFor($media, 480)) . '" alt="">'
                : '<video src="' . e(Media::fileUrl($media)) . '" muted playsinline preload="metadata"></video>')
            : '<span class="media-field__empty">' . ($kind === 'image' ? 'No photo chosen' : 'No video chosen') . '</span>';
        $meta = $media
            ? e($media['original_name'] ?: 'File ' . $media['id']) . ($media['width'] ? ' · ' . $media['width'] . '×' . $media['height'] : '') . ' · ' . human_bytes((int) $media['bytes'])
                . ($kind === 'image' && $media['alt'] === '' ? ' · <a href="' . e(url('/admin/media/' . $media['id'])) . '" class="warn-link">Add a description</a>' : '')
            : '';
        $accept = $kind === 'image' ? 'image/jpeg,image/png,image/webp' : 'video/mp4,video/webm';

        return '<div class="' . $class . '" data-media-field data-kind="' . $kind . '">'
            . '<span class="fld__label" id="' . $id . '-label">' . $label . '</span>' . $help
            . '<input type="hidden" id="' . $id . '" name="' . $name . '" value="' . ($media ? (int) $media['id'] : '') . '" data-media-input>'
            . '<div class="media-field">'
            . '<div class="media-field__preview" data-media-preview>' . $preview . '</div>'
            . '<div class="media-field__side">'
            . '<p class="media-field__meta" data-media-meta>' . $meta . '</p>'
            . '<div class="media-field__actions">'
            . '<button type="button" class="btn btn--light btn--sm" data-media-choose aria-describedby="' . $id . '-label">Choose from library</button>'
            . '<label class="btn btn--light btn--sm file-btn">Upload new<input type="file" accept="' . $accept . '" data-media-upload></label>'
            . '<button type="button" class="btn-link danger" data-media-clear' . ($media ? '' : ' hidden') . '>Remove</button>'
            . '</div>'
            . '<p class="media-field__status" data-media-status role="status"></p>'
            . '</div></div>' . $errorHtml . '</div>';
    }

    private static function hoursField(array $days, string $path, ?string $error): string
    {
        $html = '<div class="fld fld--hours' . ($error ? ' has-error' : '') . '" data-hours-field>';
        $html .= '<div class="hours-editor" role="group" aria-label="Weekly opening hours">';
        foreach (Hours::DAYS as $key => $dayName) {
            $sessions = is_array($days[$key] ?? null) ? array_values($days[$key]) : [];
            $html .= '<div class="hours-row" data-day="' . $key . '">';
            $html .= '<div class="hours-row__day">' . e($dayName) . ($sessions ? '' : ' <span class="tag">Closed</span>') . '</div>';
            $html .= '<div class="hours-row__sessions">';
            for ($i = 0; $i < 3; $i++) {
                $open = (string) ($sessions[$i]['open'] ?? '');
                $close = (string) ($sessions[$i]['close'] ?? '');
                $base = self::name($path . '.' . $key . '.' . $i);
                $hidden = $i > 0 && $open === '' && $close === '';
                $html .= '<div class="hours-session"' . ($hidden ? ' data-extra hidden' : '') . '>'
                    . '<input type="time" name="' . $base . '[open]" value="' . e($open) . '" aria-label="' . e($dayName) . ' session ' . ($i + 1) . ' opens">'
                    . '<span aria-hidden="true">to</span>'
                    . '<input type="time" name="' . $base . '[close]" value="' . e($close) . '" aria-label="' . e($dayName) . ' session ' . ($i + 1) . ' closes">'
                    . '</div>';
            }
            $html .= '<button type="button" class="btn-link" data-hours-more>+ Add another time</button>';
            $html .= '</div></div>';
        }
        $html .= '</div>';
        $html .= '<div class="hours-tools"><button type="button" class="btn btn--light btn--sm" data-hours-copy>Copy Monday to Tuesday–Saturday</button>'
            . '<span class="fld__help">Clear both times of a session to remove it. A day with no times shows as “Closed”.</span></div>';
        if ($error) {
            $html .= '<p class="fld__error">' . e($error) . '</p>';
        }
        return $html . '</div>';
    }

    /** A repeatable list of items (training options, photos, FAQs…). */
    private static function listField(array $field, array $items, array $errors): string
    {
        $path = $field['path'];
        $max = (int) ($field['max_items'] ?? 50);
        $itemLabel = (string) ($field['item_label'] ?? 'Item');
        $html = '<div class="list-field" data-list data-max="' . $max . '" data-item-label="' . e($itemLabel) . '">';
        if (isset($errors[$path])) {
            $html .= '<p class="fld__error">' . e($errors[$path]) . '</p>';
        }
        $html .= '<ol class="list-items" data-list-items>';
        $n = 0;
        foreach ($items as $key => $item) {
            $html .= self::listItem($field, is_array($item) ? $item : [], (string) $key, $errors, ++$n);
        }
        $html .= '</ol>';
        $html .= '<p class="list-empty" data-list-empty' . ($items ? ' hidden' : '') . '>Nothing added yet.</p>';
        $html .= '<template data-list-template>' . self::listItem($field, [], 'KEYPLACEHOLDER', [], 0, true) . '</template>';
        $html .= '<div class="list-actions">';
        $html .= '<button type="button" class="btn btn--light" data-list-add>+ Add ' . e(strtolower($itemLabel)) . '</button>';
        if (!empty($field['bulk_upload'])) {
            $html .= '<label class="btn btn--light file-btn">Upload several photos<input type="file" accept="image/jpeg,image/png,image/webp" multiple data-bulk-upload></label>';
            $html .= '<span class="list-actions__status" data-bulk-status role="status"></span>';
        }
        $html .= '</div></div>';
        return $html;
    }

    private static function listItem(array $field, array $item, string $key, array $errors, int $number, bool $template = false): string
    {
        $path = $field['path'];
        $titleField = (string) ($field['title_field'] ?? '');
        $title = $titleField !== '' ? s($item[$titleField] ?? '') : '';
        $hasError = false;
        foreach ($errors as $errorPath => $_) {
            if (str_starts_with($errorPath, $path . '.' . $key . '.')) {
                $hasError = true;
                break;
            }
        }
        $visibleField = null;
        foreach ($field['fields'] as $sub) {
            if ($sub['key'] === 'visible') {
                $visibleField = $sub;
            }
        }
        $isVisible = $visibleField ? (bool) ($item['visible'] ?? ($visibleField['default'] ?? true)) : true;
        $thumb = '';
        foreach ($field['fields'] as $sub) {
            if ($sub['type'] === 'image' && is_int($item[$sub['key']] ?? null) && ($m = Media::find($item[$sub['key']]))) {
                $thumb = '<img class="list-item__thumb" src="' . e(Media::urlFor($m, 480)) . '" alt="">';
                break;
            }
        }
        $itemLabel = (string) ($field['item_label'] ?? 'Item');
        $open = $template || $hasError || count($field['fields']) <= 2;

        $html = '<li class="list-item' . ($hasError ? ' has-error' : '') . ($isVisible ? '' : ' is-hidden-item') . '" data-list-item data-key="' . e($key) . '">';
        $html .= '<div class="list-item__head">';
        $html .= $thumb;
        $html .= '<button type="button" class="list-item__toggle" aria-expanded="' . ($open ? 'true' : 'false') . '" data-item-toggle>'
            . '<span class="list-item__title" data-item-title data-title-field="' . e($titleField) . '" data-fallback="' . e($itemLabel) . '">' . e($title !== '' ? $title : $itemLabel . ($number ? ' ' . $number : '')) . '</span>'
            . ($visibleField ? '<span class="list-item__badge" data-item-badge>' . ($isVisible ? 'Shown' : 'Hidden') . '</span>' : '')
            . '</button>';
        $html .= '<div class="list-item__tools">'
            . '<button type="button" class="icon-btn" data-move="up" aria-label="Move up">↑</button>'
            . '<button type="button" class="icon-btn" data-move="down" aria-label="Move down">↓</button>'
            . '<button type="button" class="icon-btn danger" data-remove aria-label="Remove this ' . e(strtolower($itemLabel)) . '">✕</button>'
            . '</div></div>';
        $html .= '<div class="list-item__body"' . ($open ? '' : ' hidden') . '>';
        foreach ($field['fields'] as $sub) {
            $subPath = $path . '.' . $key . '.' . $sub['key'];
            $value = array_key_exists($sub['key'], $item) ? $item[$sub['key']] : ($sub['default'] ?? null);
            $html .= self::control($sub, $value, $subPath, $errors[$subPath] ?? null);
        }
        $html .= '</div></li>';
        return $html;
    }
}
