<?php
declare(strict_types=1);

namespace Got;

/** Photos and videos uploaded through the dashboard (stored in public/uploads). */
final class Media
{
    public const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    public const VIDEO_TYPES = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];
    public const MAX_IMAGE_BYTES = 20 * 1048576;
    public const MAX_VIDEO_BYTES = 80 * 1048576;

    private static array $cache = [];

    public static function find(?int $id): ?array
    {
        if (!$id) {
            return null;
        }
        if (!array_key_exists($id, self::$cache)) {
            $row = Db::get()->one('SELECT * FROM media WHERE id = ?', [$id]);
            self::$cache[$id] = $row ? self::decode($row) : null;
        }
        return self::$cache[$id];
    }

    /** Load many items in one query (used before rendering a page). */
    public static function preload(array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $missing = array_values(array_filter($ids, static fn ($id) => !array_key_exists($id, self::$cache)));
        if (!$missing) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($missing), '?'));
        foreach (Db::get()->all("SELECT * FROM media WHERE id IN ({$placeholders})", $missing) as $row) {
            self::$cache[(int) $row['id']] = self::decode($row);
        }
        foreach ($missing as $id) {
            self::$cache[$id] ??= null;
        }
    }

    public static function all(string $kind = ''): array
    {
        $rows = $kind !== ''
            ? Db::get()->all('SELECT * FROM media WHERE kind = ? ORDER BY id DESC', [$kind])
            : Db::get()->all('SELECT * FROM media ORDER BY id DESC');
        return array_map([self::class, 'decode'], $rows);
    }

    /** @return array<int, string> id => kind */
    public static function kindMap(): array
    {
        $map = [];
        foreach (Db::get()->all('SELECT id, kind FROM media') as $row) {
            $map[(int) $row['id']] = (string) $row['kind'];
        }
        return $map;
    }

    public static function decode(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['width'] = $row['width'] !== null ? (int) $row['width'] : null;
        $row['height'] = $row['height'] !== null ? (int) $row['height'] : null;
        $row['bytes'] = (int) $row['bytes'];
        $variants = json_decode((string) ($row['variants'] ?? ''), true);
        $row['variants'] = is_array($variants) ? $variants : [];
        return $row;
    }

    /** Handle one file from $_FILES. */
    public static function upload(array $file, ?int $userId): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new MediaException(self::uploadErrorMessage($error));
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || (!is_uploaded_file($tmp) && PHP_SAPI !== 'cli')) {
            throw new MediaException('The upload did not complete. Please try again.');
        }
        return self::store($tmp, (string) ($file['name'] ?? 'upload'), $userId, '', false);
    }

    /** Add a file that is already on the server (used when installing the logo). */
    public static function importFile(string $path, string $name, ?int $userId, string $alt = ''): array
    {
        return self::store($path, $name, $userId, $alt, true);
    }

    private static function store(string $path, string $originalName, ?int $userId, string $alt, bool $keepSource): array
    {
        $mime = self::detectMime($path);
        $bytes = (int) filesize($path);
        $originalName = mb_substr(Sanitizer::cleanText(basename($originalName), false), 0, 200);

        if (isset(self::IMAGE_TYPES[$mime])) {
            if ($bytes > self::MAX_IMAGE_BYTES) {
                throw new MediaException('This photo is larger than 20 MB. Please choose a smaller file.');
            }
            [$dir, $base] = self::newLocation();
            $result = ImageProcessor::process($path, $mime, UPLOADS_DIR . '/' . $dir . '/' . $base);
            $stored = UPLOADS_DIR . "/{$dir}/{$base}.{$result['ext']}";
            $id = Db::get()->insert('media', [
                'kind' => 'image',
                'path' => "{$dir}/{$base}",
                'ext' => $result['ext'],
                'mime' => $result['ext'] === 'png' ? 'image/png' : 'image/jpeg',
                'bytes' => (int) @filesize($stored),
                'width' => $result['width'],
                'height' => $result['height'],
                'variants' => json_encode($result['variants']),
                'original_name' => $originalName,
                'alt' => mb_substr($alt, 0, 300),
                'created_at' => now_utc(),
                'created_by' => $userId,
            ]);
            if (!$keepSource) {
                @unlink($path);
            }
            unset(self::$cache[$id]);
            return self::find($id);
        }

        if (isset(self::VIDEO_TYPES[$mime])) {
            if ($bytes > self::MAX_VIDEO_BYTES) {
                throw new MediaException('This video is larger than 80 MB. Please export a shorter or smaller MP4 (under 15 MB is ideal).');
            }
            $ext = self::VIDEO_TYPES[$mime];
            [$dir, $base] = self::newLocation();
            $dest = UPLOADS_DIR . "/{$dir}/{$base}.{$ext}";
            $moved = $keepSource ? copy($path, $dest) : (is_uploaded_file($path) ? move_uploaded_file($path, $dest) : rename($path, $dest));
            if (!$moved) {
                throw new MediaException('The video could not be saved. Check that the uploads folder is writable.');
            }
            @chmod($dest, 0644);
            $id = Db::get()->insert('media', [
                'kind' => 'video',
                'path' => "{$dir}/{$base}",
                'ext' => $ext,
                'mime' => $mime,
                'bytes' => $bytes,
                'width' => null,
                'height' => null,
                'variants' => json_encode([]),
                'original_name' => $originalName,
                'alt' => mb_substr($alt, 0, 300),
                'created_at' => now_utc(),
                'created_by' => $userId,
            ]);
            unset(self::$cache[$id]);
            return self::find($id);
        }

        if ($mime === 'video/quicktime') {
            throw new MediaException('iPhone .MOV videos are not supported by all browsers. Please export the video as MP4 and upload that.');
        }
        if (in_array($mime, ['image/heic', 'image/heif'], true)) {
            throw new MediaException('HEIC photos from iPhones are not supported by browsers. Please export the photo as JPG and upload that.');
        }
        throw new MediaException('Please upload a JPG, PNG or WebP photo, or an MP4 video.');
    }

    private static function detectMime(string $path): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? (string) finfo_file($finfo, $path) : '';
            if ($mime !== '') {
                return strtolower($mime);
            }
        }
        $info = @getimagesize($path);
        return $info ? strtolower((string) $info['mime']) : 'application/octet-stream';
    }

    /** @return array{0: string, 1: string} folder (YYYY/MM) and random base name */
    private static function newLocation(): array
    {
        $dir = gmdate('Y') . '/' . gmdate('m');
        $full = UPLOADS_DIR . '/' . $dir;
        if (!is_dir($full) && !@mkdir($full, 0755, true) && !is_dir($full)) {
            throw new MediaException('The uploads folder is not writable. Please check folder permissions (public/uploads).');
        }
        return [$dir, bin2hex(random_bytes(10))];
    }

    public static function uploadErrorMessage(int $error): string
    {
        $limit = human_bytes(min(ini_bytes((string) ini_get('upload_max_filesize')), ini_bytes((string) ini_get('post_max_size'))));
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "This file is bigger than your hosting allows ({$limit}). Choose a smaller file, or raise “upload_max_filesize” in hPanel → PHP Configuration.",
            UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE => 'Please choose a file to upload.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'The server could not save the upload. Please contact your hosting support.',
            default => 'The upload failed. Please try again.',
        };
    }

    public static function maxUploadBytes(): int
    {
        return min(ini_bytes((string) ini_get('upload_max_filesize')), ini_bytes((string) ini_get('post_max_size')));
    }

    /** Delete the files and the database record. */
    public static function delete(int $id): void
    {
        $media = self::find($id);
        if (!$media) {
            return;
        }
        foreach (self::files($media) as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        Db::get()->run('DELETE FROM media WHERE id = ?', [$id]);
        unset(self::$cache[$id]);
    }

    /** Every file on disk that belongs to a media item. */
    public static function files(array $media): array
    {
        $base = UPLOADS_DIR . '/' . $media['path'];
        $files = [$base . '.' . $media['ext']];
        $variants = $media['variants'];
        foreach ($variants['widths'] ?? [] as $w) {
            $files[] = "{$base}-{$w}." . ($variants['format'] ?? 'webp');
        }
        foreach ($variants['icons'] ?? [] as $size) {
            $files[] = "{$base}-icon{$size}.png";
        }
        return $files;
    }

    public static function setAlt(int $id, string $alt): void
    {
        Db::get()->update('media', ['alt' => mb_substr(Sanitizer::cleanText($alt, false), 0, 300)], 'id = ?', [$id]);
        unset(self::$cache[$id]);
    }

    // --- URLs -------------------------------------------------------------

    public static function fileUrl(array $media): string
    {
        return url('/uploads/' . $media['path'] . '.' . $media['ext']);
    }

    /** Absolute URL of the JPEG/PNG version (for social sharing tags). */
    public static function absoluteFileUrl(array $media): string
    {
        return abs_url('/uploads/' . $media['path'] . '.' . $media['ext']);
    }

    public static function variantUrl(array $media, int $width): string
    {
        $format = $media['variants']['format'] ?? 'webp';
        return url('/uploads/' . $media['path'] . '-' . $width . '.' . $format);
    }

    public static function variantMime(array $media): string
    {
        return match ($media['variants']['format'] ?? 'webp') {
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            default => 'image/webp',
        };
    }

    public static function srcset(array $media): string
    {
        $parts = [];
        foreach ($media['variants']['widths'] ?? [] as $w) {
            $parts[] = self::variantUrl($media, (int) $w) . ' ' . $w . 'w';
        }
        return implode(', ', $parts);
    }

    /** Best single URL for a target display width. */
    public static function urlFor(array $media, int $width = 1440): string
    {
        $widths = $media['variants']['widths'] ?? [];
        if (!$widths) {
            return self::fileUrl($media);
        }
        foreach ($widths as $w) {
            if ($w >= $width) {
                return self::variantUrl($media, (int) $w);
            }
        }
        return self::variantUrl($media, (int) end($widths));
    }

    public static function iconUrl(array $media, int $size): ?string
    {
        if (in_array($size, $media['variants']['icons'] ?? [], true)) {
            return url('/uploads/' . $media['path'] . '-icon' . $size . '.png');
        }
        return null;
    }

    /** Where a media item is used in the draft and live content. */
    public static function usage(int $id): array
    {
        $places = [];
        foreach (['draft' => 'draft', 'published' => 'live site'] as $which => $label) {
            $ids = Content::mediaIds(Content::load($which));
            foreach ($ids[$id] ?? [] as $path) {
                $places[Schema::describePath($path)][] = $label;
            }
        }
        $out = [];
        foreach ($places as $place => $where) {
            $out[] = $place . ' (' . implode(', ', array_unique($where)) . ')';
        }
        return $out;
    }

    public static function forget(): void
    {
        self::$cache = [];
    }
}
