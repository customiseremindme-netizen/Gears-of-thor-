<?php
declare(strict_types=1);

namespace Got;

use GdImage;

/**
 * Turns an uploaded photo into web-ready versions:
 * several WebP sizes for fast loading, one JPEG/PNG fallback (also used for
 * social sharing) and small square icons when the image is roughly square.
 * Re-encoding also removes hidden camera data such as GPS location.
 */
final class ImageProcessor
{
    public const WIDTHS = [480, 960, 1440, 2048];
    public const FALLBACK_WIDTH = 1600;
    public const ICON_SIZES = [32, 180, 192, 512];
    public const MAX_PIXELS = 40_000_000;

    public static function supportsWebp(): bool
    {
        return function_exists('imagewebp') && (imagetypes() & IMG_WEBP);
    }

    /**
     * @return array{width: int, height: int, ext: string, variants: array}
     */
    public static function process(string $source, string $mime, string $destBase): array
    {
        if (!extension_loaded('gd')) {
            throw new MediaException('Photo processing is not available on this server (the PHP “gd” extension is missing).');
        }
        $info = @getimagesize($source);
        if (!$info || $info[0] < 16 || $info[1] < 16) {
            throw new MediaException('This file does not look like a valid photo.');
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw new MediaException('This photo is very large (over 40 megapixels). Please make it smaller and try again.');
        }

        @ini_set('memory_limit', '512M');
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/png' => @imagecreatefrompng($source),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
            default => false,
        };
        if (!$image instanceof GdImage) {
            throw new MediaException('This photo could not be read. Please save it as JPG or PNG and try again.');
        }
        if (!imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }
        if ($mime === 'image/jpeg') {
            $image = self::applyExifOrientation($image, $source);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $alpha = $mime !== 'image/jpeg' && self::hasTransparency($image);
        if (!$alpha && $mime !== 'image/jpeg') {
            $image = self::flatten($image);
        }

        $format = self::supportsWebp() ? 'webp' : ($alpha ? 'png' : 'jpg');
        $widths = array_values(array_filter(self::WIDTHS, static fn ($w) => $w < $width));
        $widths[] = min($width, max(self::WIDTHS));
        $widths = array_values(array_unique($widths));
        sort($widths);

        $written = [];
        try {
            foreach ($widths as $target) {
                $file = "{$destBase}-{$target}.{$format}";
                $resized = self::resize($image, $target, $alpha);
                self::save($resized, $file, $format, $alpha);
                $written[] = $file;
                unset($resized);
            }

            $fallbackWidth = min($width, self::FALLBACK_WIDTH);
            $ext = $alpha ? 'png' : 'jpg';
            $resized = self::resize($image, $fallbackWidth, $alpha);
            self::save($resized, "{$destBase}.{$ext}", $ext, $alpha);
            $written[] = "{$destBase}.{$ext}";
            unset($resized);

            $icons = [];
            $ratio = $width / max(1, $height);
            if ($ratio > 0.8 && $ratio < 1.25) {
                foreach (self::ICON_SIZES as $size) {
                    $square = self::square($image, $size);
                    self::save($square, "{$destBase}-icon{$size}.png", 'png', true);
                    $written[] = "{$destBase}-icon{$size}.png";
                    unset($square);
                    $icons[] = $size;
                }
            }
        } catch (\Throwable $e) {
            foreach ($written as $file) {
                @unlink($file);
            }
            throw $e instanceof MediaException ? $e : new MediaException('The photo could not be saved: ' . $e->getMessage());
        } finally {
            unset($image);
        }

        return [
            'width' => $width,
            'height' => $height,
            'ext' => $ext,
            'variants' => [
                'format' => $format,
                'widths' => $widths,
                'fallback' => $fallbackWidth,
                'icons' => $icons,
                'alpha' => $alpha,
            ],
        ];
    }

    private static function save(GdImage $image, string $file, string $format, bool $alpha): void
    {
        $ok = match ($format) {
            'webp' => imagewebp($image, $file, $alpha ? 86 : 80),
            'png' => imagepng($image, $file, 7),
            default => (static function () use ($image, $file): bool {
                imageinterlace($image, true);
                return imagejpeg($image, $file, 82);
            })(),
        };
        if (!$ok || !is_file($file)) {
            throw new MediaException('The photo could not be saved. Check that the uploads folder is writable.');
        }
        @chmod($file, 0644);
    }

    private static function resize(GdImage $image, int $targetWidth, bool $alpha): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        if ($targetWidth >= $width) {
            return $image;
        }
        $targetHeight = max(1, (int) round($height * $targetWidth / $width));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($alpha) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        }
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        return $canvas;
    }

    /** Fit the image inside a transparent square (for browser and phone icons). */
    private static function square(GdImage $image, int $size): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min($size / $width, $size / $height);
        $w = max(1, (int) round($width * $scale));
        $h = max(1, (int) round($height * $scale));
        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagealphablending($canvas, true);
        imagecopyresampled($canvas, $image, intdiv($size - $w, 2), intdiv($size - $h, 2), 0, 0, $w, $h, $width, $height);
        imagealphablending($canvas, false);
        return $canvas;
    }

    private static function hasTransparency(GdImage $image): bool
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $step = max(1, (int) floor(sqrt(($width * $height) / 40000)));
        for ($y = 0; $y < $height; $y += $step) {
            for ($x = 0; $x < $width; $x += $step) {
                if (((imagecolorat($image, $x, $y) >> 24) & 0x7F) > 0) {
                    return true;
                }
            }
        }
        // Always check the corners, where transparent backgrounds usually are.
        foreach ([[0, 0], [$width - 1, 0], [0, $height - 1], [$width - 1, $height - 1]] as [$x, $y]) {
            if (((imagecolorat($image, $x, $y) >> 24) & 0x7F) > 0) {
                return true;
            }
        }
        return false;
    }

    /** Place an image with an alpha channel onto white (for JPEG output). */
    private static function flatten(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $canvas = imagecreatetruecolor($width, $height);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);
        return $canvas;
    }

    /** Rotate phone photos the right way up using their EXIF orientation. */
    private static function applyExifOrientation(GdImage $image, string $file): GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }
        $exif = @exif_read_data($file);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        $rotate = static function (GdImage $img, int $angle): GdImage {
            $rotated = imagerotate($img, $angle, 0);
            return $rotated instanceof GdImage ? $rotated : $img;
        };
        switch ($orientation) {
            case 2:
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;
            case 3:
                $image = $rotate($image, 180);
                break;
            case 4:
                imageflip($image, IMG_FLIP_VERTICAL);
                break;
            case 5:
                $image = $rotate($image, -90);
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;
            case 6:
                $image = $rotate($image, -90);
                break;
            case 7:
                $image = $rotate($image, 90);
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;
            case 8:
                $image = $rotate($image, 90);
                break;
        }
        return $image;
    }
}
