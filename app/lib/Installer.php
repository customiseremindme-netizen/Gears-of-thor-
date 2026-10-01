<?php
declare(strict_types=1);

namespace Got;

/** First-time setup: checks the hosting, creates the database and the owner account. */
final class Installer
{
    public static function setupCodePath(): string
    {
        return STORAGE_DIR . '/setup-code.txt';
    }

    /** The one-time code (read from File Manager) that proves you own the server. */
    public static function setupCode(): ?string
    {
        $file = self::setupCodePath();
        if (!is_file($file)) {
            if (!is_writable(STORAGE_DIR)) {
                return null;
            }
            $code = strtoupper(implode('-', str_split(bin2hex(random_bytes(6)), 4)));
            file_put_contents($file, $code . "\n", LOCK_EX);
            @chmod($file, 0640);
        }
        $code = trim((string) file_get_contents($file));
        return $code !== '' ? $code : null;
    }

    /** @return array<int, array{label: string, ok: bool, detail: string, required: bool}> */
    public static function requirements(): array
    {
        $checks = [];
        $add = static function (string $label, bool $ok, string $detail, bool $required = true) use (&$checks): void {
            $checks[] = compact('label', 'ok', 'detail', 'required');
        };

        $add('PHP 8.1 or newer', PHP_VERSION_ID >= 80100, 'This server runs PHP ' . PHP_VERSION . '. In hPanel: Advanced → PHP Configuration → choose PHP 8.2 or 8.3.');
        $add('Database support (MySQL)', extension_loaded('pdo_mysql'), 'The PHP “pdo_mysql” extension is needed for a MySQL database.', !extension_loaded('pdo_sqlite'));
        $add('Photo processing (GD)', extension_loaded('gd'), 'Turn on the PHP “gd” extension in hPanel → PHP Configuration → PHP Extensions.');
        $add('WebP images (smaller, faster photos)', ImageProcessor::supportsWebp(), 'Optional: without WebP, photos are saved as JPEG.', false);
        $add('Text handling (mbstring)', extension_loaded('mbstring'), 'Turn on the PHP “mbstring” extension.');
        $add('File type checks (fileinfo)', extension_loaded('fileinfo'), 'Turn on the PHP “fileinfo” extension for safe video uploads.', false);
        $add('Photo rotation (exif)', function_exists('exif_read_data'), 'Optional: turns phone photos the right way up.', false);
        $add('“storage” folder is writable', is_writable(STORAGE_DIR), 'Set the “storage” folder permission to 755 in File Manager.');
        $add('“public/uploads” folder is writable', is_writable(UPLOADS_DIR), 'Set the “public/uploads” folder permission to 755 in File Manager.');
        $add('Secure connection (HTTPS)', Request::isHttps(), 'Recommended: switch on the free SSL certificate in hPanel → Security → SSL, then reload this page with https://.', false);
        return $checks;
    }

    public static function ready(): bool
    {
        foreach (self::requirements() as $check) {
            if ($check['required'] && !$check['ok']) {
                return false;
            }
        }
        return true;
    }

    /**
     * Install everything. $input: db_driver, db_host, db_port, db_name, db_user, db_pass,
     * owner_name, owner_email, owner_password, site_url.
     * @return array{ok: bool, errors: array<string, string>, existing?: bool}
     */
    public static function install(array $input): array
    {
        $errors = [];
        $driver = ($input['db_driver'] ?? 'mysql') === 'sqlite' ? 'sqlite' : 'mysql';
        $name = Sanitizer::cleanText((string) ($input['owner_name'] ?? ''), false);
        $email = strtolower(trim((string) ($input['owner_email'] ?? '')));
        $password = (string) ($input['owner_password'] ?? '');

        if ($name === '') {
            $errors['owner_name'] = 'Please enter your name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['owner_email'] = 'Please enter a valid email address.';
        }
        if ($problem = Auth::validatePassword($password)) {
            $errors['owner_password'] = $problem;
        }

        $dbConfig = ['driver' => $driver];
        if ($driver === 'mysql') {
            $dbConfig += [
                'host' => trim((string) ($input['db_host'] ?? 'localhost')) ?: 'localhost',
                'port' => (int) ($input['db_port'] ?? 3306) ?: 3306,
                'name' => trim((string) ($input['db_name'] ?? '')),
                'user' => trim((string) ($input['db_user'] ?? '')),
                'pass' => (string) ($input['db_pass'] ?? ''),
            ];
            if ($dbConfig['name'] === '') {
                $errors['db_name'] = 'Please enter the database name from hPanel.';
            }
            if ($dbConfig['user'] === '') {
                $errors['db_user'] = 'Please enter the database username from hPanel.';
            }
        } else {
            $dbConfig['path'] = 'storage/database/site-' . bin2hex(random_bytes(8)) . '.sqlite';
        }
        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }

        try {
            $db = Db::connect($dbConfig);
        } catch (\Throwable $e) {
            Logger::warning('Installer could not connect to the database: ' . $e->getMessage());
            return ['ok' => false, 'errors' => ['db' => 'Could not connect to the database. Please check the database name, username and password in hPanel → Databases → MySQL Databases. (' . self::friendlyDbError($e) . ')']];
        }

        $appKey = bin2hex(random_bytes(32));
        $config = [
            'app_key' => $appKey,
            'db' => $dbConfig,
            'debug' => false,
            'trusted_ip_header' => null,
            'installed_at' => gmdate('c'),
        ];

        // Use the new connection and key for the rest of the setup.
        Config::override($config);
        Db::setInstance($db);

        $existing = false;
        try {
            Migrator::migrate($db);
            $existing = $db->value('SELECT COUNT(*) FROM content') > 0;

            $userId = self::upsertOwner($db, $name, $email, $password);
            if (!$existing) {
                self::seed($userId);
            }
            $siteUrl = trim((string) ($input['site_url'] ?? ''));
            if ($siteUrl === '' || !filter_var($siteUrl, FILTER_VALIDATE_URL)) {
                $siteUrl = Request::origin() . Request::base();
            }
            Settings::forget();
            Settings::set('site_url', rtrim($siteUrl, '/'));
            Config::write($config);
        } catch (\Throwable $e) {
            Config::override([]);
            Db::setInstance(null);
            Logger::error('Installation failed: ' . $e->getMessage());
            return ['ok' => false, 'errors' => ['db' => 'Installation could not finish: ' . $e->getMessage()]];
        }

        @unlink(self::setupCodePath());
        @file_put_contents(STORAGE_DIR . '/cache/schema-version', (string) Migrator::latest());
        return ['ok' => true, 'errors' => [], 'existing' => $existing];
    }

    private static function upsertOwner(Db $db, string $name, string $email, string $password): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $existing = $db->one('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existing) {
            $db->update('users', [
                'name' => $name,
                'password_hash' => $hash,
                'role' => 'owner',
                'is_active' => 1,
                'updated_at' => now_utc(),
            ], 'id = ?', [(int) $existing['id']]);
            return (int) $existing['id'];
        }
        return $db->insert('users', [
            'name' => $name,
            'email' => $email,
            'password_hash' => $hash,
            'role' => 'owner',
            'is_active' => 1,
            'last_login_at' => null,
            'created_at' => now_utc(),
            'updated_at' => now_utc(),
        ]);
    }

    /** Default content, plus the GOT FITNEZZ logo, icon and sharing image. */
    public static function seed(?int $userId): void
    {
        $content = Content::defaults();
        $seedDir = APP_DIR . '/seed/media';
        $assets = [
            'brand.logo' => ['logo.png', 'GOT FITNEZZ — Gears Of Thor logo'],
            'brand.icon' => ['app-icon.png', 'GOT FITNEZZ app icon'],
            'seo.share_image' => ['share-image.jpg', 'GOT FITNEZZ, Palladam — Build strength. Build yourself.'],
        ];
        foreach ($assets as $path => [$file, $alt]) {
            if (!is_file($seedDir . '/' . $file)) {
                continue;
            }
            try {
                $media = Media::importFile($seedDir . '/' . $file, $file, $userId, $alt);
                array_set($content, $path, $media['id']);
            } catch (\Throwable $e) {
                Logger::warning("Could not import {$file}: " . $e->getMessage());
            }
        }
        Content::initialize($content, $userId);
    }

    private static function friendlyDbError(\Throwable $e): string
    {
        $message = $e->getMessage();
        return match (true) {
            str_contains($message, 'Access denied') => 'the username or password was not accepted',
            str_contains($message, 'Unknown database') => 'that database name does not exist',
            str_contains($message, 'getaddrinfo'), str_contains($message, 'No such host') => 'the database host was not found — on Hostinger it is usually “localhost”',
            str_contains($message, 'Connection refused') => 'the database server refused the connection',
            default => 'error: ' . mb_substr($message, 0, 120),
        };
    }
}
