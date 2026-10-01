<?php
declare(strict_types=1);

namespace Got;

/**
 * Private site settings stored in the database (not part of the published
 * website content): spam protection keys, notification email, checklist ticks.
 */
final class Settings
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            if (!Config::installed()) {
                return self::$cache;
            }
            try {
                foreach (Db::get()->all('SELECT name, value FROM settings') as $row) {
                    $decoded = json_decode((string) $row['value'], true);
                    self::$cache[$row['name']] = json_last_error() === JSON_ERROR_NONE ? $decoded : $row['value'];
                }
            } catch (\Throwable $e) {
                Logger::error('Could not read settings: ' . $e->getMessage());
            }
        }
        return self::$cache;
    }

    public static function get(string $name, mixed $default = null): mixed
    {
        $all = self::all();
        return array_key_exists($name, $all) ? $all[$name] : $default;
    }

    public static function set(string $name, mixed $value): void
    {
        $db = Db::get();
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $db->transaction(static function (Db $db) use ($name, $encoded): void {
            $exists = $db->value('SELECT COUNT(*) FROM settings WHERE name = ?', [$name]);
            if ((int) $exists > 0) {
                $db->update('settings', ['value' => $encoded, 'updated_at' => now_utc()], 'name = ?', [$name]);
            } else {
                $db->insert('settings', ['name' => $name, 'value' => $encoded, 'updated_at' => now_utc()]);
            }
        });
        if (self::$cache !== null) {
            self::$cache[$name] = $value;
        }
    }

    public static function forget(): void
    {
        self::$cache = null;
    }
}
