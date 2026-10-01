<?php
declare(strict_types=1);

namespace Got\Controllers\Admin;

use Got\AdminView;
use Got\Auth;
use Got\Content;
use Got\Logger;
use Got\Media;
use Got\MediaException;
use Got\Request;
use Got\Schema;
use Got\Session;

/** The photo and video library. */
final class MediaController
{
    public static function index(): void
    {
        Auth::require('media');
        $kind = in_array(Request::query('kind'), ['image', 'video'], true) ? Request::query('kind') : '';
        $items = Media::all($kind);
        AdminView::render('media', [
            'title' => 'Photos & videos',
            'nav' => 'media',
            'items' => $items,
            'kind' => $kind,
            'used' => self::usageMap(),
            'maxUpload' => Media::maxUploadBytes(),
        ]);
    }

    /** JSON list for the photo picker. */
    public static function list(): void
    {
        Auth::require('media');
        $kind = Request::query('kind') === 'video' ? 'video' : 'image';
        json_out(['ok' => true, 'items' => array_map([self::class, 'summary'], Media::all($kind))]);
    }

    public static function upload(): void
    {
        $user = Auth::require('media');
        $json = Request::wantsJson();

        // A file bigger than the hosting allows arrives with no form data at all.
        $length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($length > 0 && $_POST === [] && $_FILES === []) {
            $message = Media::uploadErrorMessage(UPLOAD_ERR_INI_SIZE);
            if ($json) {
                json_out(['ok' => false, 'items' => [], 'errors' => [$message]], 413);
            }
            Session::flash('error', $message);
            redirect('/admin/media');
        }
        AdminView::requireCsrf();

        $files = self::collectFiles();
        $items = [];
        $errors = [];
        foreach ($files as $file) {
            try {
                $items[] = self::summary(Media::upload($file, $user['id']));
            } catch (MediaException $e) {
                $errors[] = ($file['name'] ?? 'File') . ': ' . $e->getMessage();
            } catch (\Throwable $e) {
                Logger::error('Upload failed: ' . $e->getMessage());
                $errors[] = ($file['name'] ?? 'File') . ': the upload failed. Please try again.';
            }
        }
        if (!$files) {
            $errors[] = 'Please choose a file to upload.';
        }

        if ($json) {
            json_out(['ok' => $errors === [], 'items' => $items, 'errors' => $errors], $items || !$errors ? 200 : 422);
        }
        if ($items) {
            Session::flash('success', count($items) === 1 ? 'Uploaded. Add a short description so everyone can understand the photo.' : count($items) . ' files uploaded.');
        }
        foreach ($errors as $error) {
            Session::flash('error', $error);
        }
        redirect('/admin/media');
    }

    public static function show(int $id): void
    {
        Auth::require('media');
        $media = Media::find($id);
        if (!$media) {
            redirect('/admin/media');
        }
        AdminView::render('media-show', [
            'title' => $media['original_name'] ?: 'Media',
            'nav' => 'media',
            'media' => $media,
            'usage' => Media::usage($id),
            'maxUpload' => Media::maxUploadBytes(),
        ]);
    }

    public static function update(int $id): void
    {
        Auth::require('media');
        AdminView::requireCsrf();
        if (Media::find($id)) {
            Media::setAlt($id, Request::post('alt'));
            Session::flash('success', 'Description saved. It is used on the website straight away.');
        }
        redirect('/admin/media/' . $id);
    }

    /** Upload a new file and swap it in everywhere the old one is used in the draft. */
    public static function replace(int $id): void
    {
        $user = Auth::require('media');
        $length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($length > 0 && $_POST === [] && $_FILES === []) {
            Session::flash('error', Media::uploadErrorMessage(UPLOAD_ERR_INI_SIZE));
            redirect('/admin/media/' . $id);
        }
        AdminView::requireCsrf();
        $old = Media::find($id);
        if (!$old) {
            redirect('/admin/media');
        }
        try {
            $new = Media::upload((array) ($_FILES['file'] ?? []), $user['id']);
        } catch (MediaException $e) {
            Session::flash('error', $e->getMessage());
            redirect('/admin/media/' . $id);
        }
        if ($new['kind'] !== $old['kind']) {
            Media::delete($new['id']);
            Session::flash('error', 'Please replace a ' . ($old['kind'] === 'video' ? 'video with a video' : 'photo with a photo') . '.');
            redirect('/admin/media/' . $id);
        }
        if ($old['alt'] !== '') {
            Media::setAlt($new['id'], $old['alt']);
        }
        $swapped = 0;
        Content::updateDraft(static function (array $draft) use ($id, $new, &$swapped): array {
            $walk = static function (array &$node, string $path) use (&$walk, $id, $new, &$swapped): void {
                foreach ($node as $key => &$value) {
                    $here = $path === '' ? (string) $key : $path . '.' . $key;
                    if (is_array($value)) {
                        $walk($value, $here);
                    } elseif ($value === $id && Schema::isMediaPath($here)) {
                        $value = $new['id'];
                        $swapped++;
                    }
                }
            };
            $swapped = 0;
            $walk($draft, '');
            return $draft;
        }, $user['id']);

        Session::flash('success', $swapped > 0
            ? 'Replaced in your draft. Preview and publish to show the new file on the website. The old file stays in the library until you delete it.'
            : 'Uploaded the new file. The old one was not used in the draft, so nothing else changed.');
        redirect('/admin/media/' . $new['id']);
    }

    public static function delete(int $id): void
    {
        Auth::require('media');
        AdminView::requireCsrf();
        $usage = Media::usage($id);
        if ($usage) {
            Session::flash('error', 'This file is still used: ' . implode('; ', $usage) . '. Remove it from those places (and publish) before deleting it.');
            redirect('/admin/media/' . $id);
        }
        Media::delete($id);
        Session::flash('success', 'File deleted.');
        redirect('/admin/media');
    }

    public static function summary(array $media): array
    {
        return [
            'id' => $media['id'],
            'kind' => $media['kind'],
            'thumb' => $media['kind'] === 'image' ? Media::urlFor($media, 480) : null,
            'url' => Media::fileUrl($media),
            'name' => $media['original_name'] ?: ('File ' . $media['id']),
            'width' => $media['width'],
            'height' => $media['height'],
            'alt' => $media['alt'],
            'size' => human_bytes((int) $media['bytes']),
        ];
    }

    /** Normalise single and multiple file inputs into one list. */
    private static function collectFiles(): array
    {
        $files = [];
        foreach (['file', 'files'] as $field) {
            $entry = $_FILES[$field] ?? null;
            if (!$entry) {
                continue;
            }
            if (is_array($entry['name'])) {
                foreach ($entry['name'] as $i => $name) {
                    if (($entry['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $files[] = [
                        'name' => $name,
                        'type' => $entry['type'][$i] ?? '',
                        'tmp_name' => $entry['tmp_name'][$i] ?? '',
                        'error' => $entry['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $entry['size'][$i] ?? 0,
                    ];
                }
            } elseif (($entry['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $files[] = $entry;
            }
        }
        return array_slice($files, 0, 30);
    }

    /** media id => true when used in the draft or on the live site */
    private static function usageMap(): array
    {
        $used = [];
        foreach (['draft', 'published'] as $which) {
            foreach (array_keys(Content::mediaIds(Content::load($which))) as $id) {
                $used[$id] = true;
            }
        }
        return $used;
    }
}
