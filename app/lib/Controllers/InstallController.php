<?php
declare(strict_types=1);

namespace Got\Controllers;

use Got\Installer;
use Got\Request;
use Got\Security;
use Got\View;

/** One-time setup page shown until the site is installed. */
final class InstallController
{
    public static function handle(): void
    {
        Security::headers('admin');
        header('Content-Type: text/html; charset=utf-8');

        $code = Installer::setupCode();
        $errors = [];
        $values = [
            'db_driver' => 'mysql',
            'db_host' => 'localhost',
            'db_port' => '3306',
            'db_name' => '',
            'db_user' => '',
            'owner_name' => '',
            'owner_email' => '',
            'site_url' => Request::origin() . Request::base(),
        ];

        if (Request::isPost()) {
            foreach (array_keys($values) as $key) {
                $values[$key] = Request::post($key, $values[$key]);
            }
            $sent = strtoupper(preg_replace('/\s+/', '', Request::post('setup_code')));
            if ($code === null || !hash_equals($code, $sent)) {
                $errors['setup_code'] = 'That setup code does not match. Open storage/setup-code.txt in File Manager and copy the code exactly.';
                usleep(400000);
            } elseif (!Installer::ready()) {
                $errors['requirements'] = 'Please fix the hosting requirements marked in red first.';
            } else {
                $result = Installer::install($_POST + $values);
                if ($result['ok']) {
                    echo View::render('install/done', ['title' => 'Your website is ready', 'existing' => $result['existing'] ?? false], 'install/layout');
                    return;
                }
                $errors = $result['errors'];
            }
        }

        echo View::render('install/form', [
            'title' => 'Set up your website',
            'checks' => Installer::requirements(),
            'ready' => Installer::ready(),
            'hasCode' => $code !== null,
            'values' => $values,
            'errors' => $errors,
            'sqlite' => extension_loaded('pdo_sqlite'),
            'mysql' => extension_loaded('pdo_mysql'),
        ], 'install/layout');
    }
}
