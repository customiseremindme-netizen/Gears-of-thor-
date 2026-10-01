<?php
declare(strict_types=1);

/**
 * Command-line installer (for developers and automated tests).
 * The owner normally uses the web installer at /install instead.
 *
 *   php bin/install.php --db=sqlite --email=owner@example.com --password="a long password"
 *   php bin/install.php --db=mysql --db-host=localhost --db-name=gym --db-user=gym --db-pass=secret \
 *                       --email=owner@example.com --password="a long password" --url=https://example.com
 */

if (PHP_SAPI !== 'cli') {
    exit("Run this from the command line.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

use Got\Config;
use Got\Installer;

$options = getopt('', ['db:', 'db-host:', 'db-port:', 'db-name:', 'db-user:', 'db-pass:', 'email:', 'password:', 'name:', 'url:', 'force']);

if (Config::installed() && !isset($options['force'])) {
    fwrite(STDERR, "Already installed (storage/config.php exists). Use --force to reinstall.\n");
    exit(1);
}

$result = Installer::install([
    'db_driver' => $options['db'] ?? 'sqlite',
    'db_host' => $options['db-host'] ?? 'localhost',
    'db_port' => $options['db-port'] ?? '3306',
    'db_name' => $options['db-name'] ?? '',
    'db_user' => $options['db-user'] ?? '',
    'db_pass' => $options['db-pass'] ?? '',
    'owner_name' => $options['name'] ?? 'Owner',
    'owner_email' => $options['email'] ?? '',
    'owner_password' => $options['password'] ?? '',
    'site_url' => $options['url'] ?? 'http://localhost:8000',
]);

if (!$result['ok']) {
    foreach ($result['errors'] as $field => $message) {
        fwrite(STDERR, "{$field}: {$message}\n");
    }
    exit(1);
}

echo "Installed" . (!empty($result['existing']) ? ' (existing data kept)' : '') . ".\n";
echo "Sign in at " . rtrim($options['url'] ?? 'http://localhost:8000', '/') . "/admin\n";
