<?php
declare(strict_types=1);

namespace Got;

/**
 * Website content with a simple publishing workflow:
 * the dashboard edits the "draft"; visitors see the "published" copy;
 * publishing copies the draft over and keeps a history entry for restoring.
 */
final class Content
{
    private static array $cache = [];
    private static ?array $defaults = null;

    public static function defaults(): array
    {
        return self::$defaults ??= require APP_DIR . '/seed/content.php';
    }

    /** Full content for 'draft' or 'published', with defaults filled in. */
    public static function load(string $which = 'published'): array
    {
        if (isset(self::$cache[$which])) {
            return self::$cache[$which];
        }
        $row = self::row($which);
        $data = $row ? $row['data'] : [];
        return self::$cache[$which] = self::mergeDefaults($data, self::defaults());
    }

    /** @return array{data: array, version: int, updated_at: string, updated_by: ?int}|null */
    public static function row(string $which): ?array
    {
        $row = Db::get()->one('SELECT data, version, updated_at, updated_by FROM content WHERE name = ?', [$which]);
        if (!$row) {
            return null;
        }
        $decoded = json_decode((string) $row['data'], true);
        return [
            'data' => is_array($decoded) ? $decoded : [],
            'version' => (int) $row['version'],
            'updated_at' => (string) $row['updated_at'],
            'updated_by' => $row['updated_by'] !== null ? (int) $row['updated_by'] : null,
        ];
    }

    /**
     * Fill in any keys missing from saved content (for example after an update
     * adds a new setting). Lists are never merged item by item.
     */
    public static function mergeDefaults(mixed $data, array $defaults): array
    {
        $data = is_array($data) ? $data : [];
        $result = $data;
        foreach ($defaults as $key => $default) {
            $isObject = is_array($default) && $default !== [] && !array_is_list($default);
            if ($isObject) {
                $result[$key] = self::mergeDefaults($data[$key] ?? [], $default);
            } elseif (!array_key_exists($key, $data)) {
                $result[$key] = $default;
            }
        }
        return $result;
    }

    /** Create both copies during installation. */
    public static function initialize(array $data, ?int $userId): void
    {
        $json = self::encode($data);
        $db = Db::get();
        $db->transaction(static function (Db $db) use ($json, $userId): void {
            foreach (['draft', 'published'] as $name) {
                $db->run('DELETE FROM content WHERE name = ?', [$name]);
                $db->insert('content', [
                    'name' => $name,
                    'data' => $json,
                    'version' => 1,
                    'updated_at' => now_utc(),
                    'updated_by' => $userId,
                ]);
            }
        });
        self::$cache = [];
    }

    /**
     * Safely change the draft. The callback receives the current draft and
     * returns the new one; concurrent edits are retried instead of lost.
     */
    public static function updateDraft(callable $change, ?int $userId): array
    {
        $db = Db::get();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $row = self::row('draft');
            if (!$row) {
                throw new \RuntimeException('Draft content is missing.');
            }
            $current = self::mergeDefaults($row['data'], self::defaults());
            $next = $change($current);
            if (!is_array($next)) {
                throw new \RuntimeException('Content change did not return data.');
            }
            $updated = $db->run(
                'UPDATE content SET data = ?, version = version + 1, updated_at = ?, updated_by = ? WHERE name = ? AND version = ?',
                [self::encode($next), now_utc(), $userId, 'draft', $row['version']]
            )->rowCount();
            if ($updated > 0) {
                unset(self::$cache['draft']);
                return $next;
            }
            usleep(50000);
        }
        throw new \RuntimeException('Someone else is saving changes at the same time. Please try again.');
    }

    public static function publish(?int $userId, string $note = ''): void
    {
        $db = Db::get();
        $db->transaction(static function (Db $db) use ($userId, $note): void {
            $draft = $db->value('SELECT data FROM content WHERE name = ?', ['draft']);
            if ($draft === null) {
                throw new \RuntimeException('Draft content is missing.');
            }
            $db->run(
                'UPDATE content SET data = ?, version = version + 1, updated_at = ?, updated_by = ? WHERE name = ?',
                [$draft, now_utc(), $userId, 'published']
            );
            $db->insert('content_history', [
                'data' => $draft,
                'note' => mb_substr($note, 0, 255),
                'created_at' => now_utc(),
                'created_by' => $userId,
            ]);
            // Keep the 40 most recent versions.
            $keep = $db->all('SELECT id FROM content_history ORDER BY id DESC LIMIT 40');
            if (count($keep) === 40) {
                $oldest = (int) end($keep)['id'];
                $db->run('DELETE FROM content_history WHERE id < ?', [$oldest]);
            }
        });
        self::$cache = [];
    }

    /** Throw away unpublished changes. */
    public static function discard(?int $userId): void
    {
        $published = Db::get()->value('SELECT data FROM content WHERE name = ?', ['published']);
        if ($published === null) {
            return;
        }
        Db::get()->run(
            'UPDATE content SET data = ?, version = version + 1, updated_at = ?, updated_by = ? WHERE name = ?',
            [$published, now_utc(), $userId, 'draft']
        );
        self::$cache = [];
    }

    /** Copy an earlier published version into the draft (publish to make it live). */
    public static function restore(int $historyId, ?int $userId): bool
    {
        $data = Db::get()->value('SELECT data FROM content_history WHERE id = ?', [$historyId]);
        if ($data === null) {
            return false;
        }
        Db::get()->run(
            'UPDATE content SET data = ?, version = version + 1, updated_at = ?, updated_by = ? WHERE name = ?',
            [$data, now_utc(), $userId, 'draft']
        );
        self::$cache = [];
        return true;
    }

    public static function history(int $limit = 40): array
    {
        return Db::get()->all(
            'SELECT h.id, h.note, h.created_at, u.name AS user_name
             FROM content_history h LEFT JOIN users u ON u.id = h.created_by
             ORDER BY h.id DESC LIMIT ?',
            [$limit]
        );
    }

    /** Top-level areas that differ between the draft and the live site. */
    public static function changedKeys(): array
    {
        $draft = self::load('draft');
        $published = self::load('published');
        $changed = [];
        foreach (array_unique(array_merge(array_keys($draft), array_keys($published))) as $key) {
            if (self::encode($draft[$key] ?? null) !== self::encode($published[$key] ?? null)) {
                $changed[] = $key;
            }
        }
        return $changed;
    }

    public static function hasChanges(): bool
    {
        return self::changedKeys() !== [];
    }

    public static function encode(mixed $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function forget(): void
    {
        self::$cache = [];
    }

    /** Every media id referenced anywhere in a content array. */
    public static function mediaIds(array $content): array
    {
        $ids = [];
        $walk = static function (array $node, string $path) use (&$walk, &$ids): void {
            foreach ($node as $key => $value) {
                $here = $path === '' ? (string) $key : $path . '.' . $key;
                if (is_array($value)) {
                    $walk($value, $here);
                } elseif (is_int($value) && Schema::isMediaPath($here)) {
                    $ids[$value][] = $here;
                }
            }
        };
        $walk($content, '');
        return $ids;
    }
}
