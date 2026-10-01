<?php
/**
 * GOT FITNEZZ — Gears Of Thor
 * Front controller: every page and form submission starts here.
 */

if (PHP_VERSION_ID < 80100) {
    header('Content-Type: text/plain; charset=utf-8', true, 500);
    echo "This website needs PHP 8.1 or newer.\n";
    echo "In Hostinger hPanel open Advanced > PHP Configuration and choose PHP 8.2 or 8.3.\n";
    exit;
}

// PHP's built-in development server: serve real files (CSS, JS, images) directly.
if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $requestPath = rawurldecode($requestPath);
    $file = realpath(__DIR__ . $requestPath);
    if ($file !== false
        && is_file($file)
        && strpos($file, __DIR__ . DIRECTORY_SEPARATOR) === 0
        && strpos($requestPath, '/.') === false
        && !preg_match('/\.(php|phtml|htaccess)$/i', $file)
    ) {
        return false;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';

Got\App::run();
