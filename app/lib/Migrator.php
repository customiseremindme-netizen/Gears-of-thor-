<?php
declare(strict_types=1);

namespace Got;

/** Creates and updates the database tables (files in app/migrations). */
final class Migrator
{
    /** @return array<int, string> version => file */
    public static function files(): array
    {
        $files = [];
        foreach (glob(APP_DIR . '/migrations/*.php') ?: [] as $file) {
            if (preg_match('/^(\d+)_/', basename($file), $m)) {
                $files[(int) $m[1]] = $file;
            }
        }
        ksort($files);
        return $files;
    }

    public static function latest(): int
    {
        $files = self::files();
        return $files ? (int) array_key_last($files) : 0;
    }

    /** Run any migrations that have not been applied yet. Returns the versions applied. */
    public static function migrate(Db $db): array
    {
        $db->createTable('migrations', [
            'version INT NOT NULL PRIMARY KEY',
            'applied_at DATETIME NOT NULL',
        ]);
        $done = array_map('intval', array_column($db->all('SELECT version FROM migrations'), 'version'));
        $applied = [];
        foreach (self::files() as $version => $file) {
            if (in_array($version, $done, true)) {
                continue;
            }
            $migration = require $file;
            $migration($db);
            $db->insert('migrations', ['version' => $version, 'applied_at' => now_utc()]);
            $applied[] = $version;
        }
        return $applied;
    }

    /** Cheap check on each request: only touches the database after an update. */
    public static function ensureCurrent(): void
    {
        $marker = STORAGE_DIR . '/cache/schema-version';
        $latest = (string) self::latest();
        if (is_file($marker) && trim((string) file_get_contents($marker)) === $latest) {
            return;
        }
        try {
            self::migrate(Db::get());
            @file_put_contents($marker, $latest, LOCK_EX);
        } catch (\Throwable $e) {
            Logger::error('Database update failed: ' . $e->getMessage());
        }
    }
}
