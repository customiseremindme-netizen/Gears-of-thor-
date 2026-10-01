<?php
declare(strict_types=1);

/**
 * Loads configuration, the class autoloader and shared helpers.
 * Used by the website (public/index.php), the command-line tools (bin/) and tests.
 */

const GOT_APP = true;
const APP_VERSION = '1.0.0';

define('APP_ROOT', dirname(__DIR__));
define('APP_DIR', __DIR__);
define('STORAGE_DIR', APP_ROOT . '/storage');
define('PUBLIC_DIR', APP_ROOT . '/public');
define('UPLOADS_DIR', PUBLIC_DIR . '/uploads');

spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'Got\\', 4) !== 0) {
        return;
    }
    $file = __DIR__ . '/lib/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/helpers.php';

// All dates are stored in UTC and shown to people in India Standard Time.
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');
