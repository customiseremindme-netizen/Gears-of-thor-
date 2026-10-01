<?php
declare(strict_types=1);

namespace Got;

use Got\Controllers\Admin;
use Got\Controllers\EnquiryController;
use Got\Controllers\InstallController;
use Got\Controllers\SiteController;

/** Starts the application and sends each request to the right page. */
final class App
{
    public static function run(): void
    {
        self::handleErrors();

        $path = Request::path();

        if (!Config::installed()) {
            if ($path === '/install') {
                InstallController::handle();
                return;
            }
            redirect('/install');
        }

        Migrator::ensureCurrent();
        self::routes()->dispatch(Request::method(), $path);
    }

    public static function routes(): Router
    {
        $r = new Router();

        // Public website
        $r->get('/', [SiteController::class, 'home']);
        $r->get('/privacy', [SiteController::class, 'privacy']);
        $r->get('/robots.txt', [SiteController::class, 'robots']);
        $r->get('/sitemap.xml', [SiteController::class, 'sitemap']);
        $r->get('/site.webmanifest', [SiteController::class, 'manifest']);
        $r->post('/enquire', [EnquiryController::class, 'submit']);
        $r->get('/enquire', static fn () => redirect('/#enquire'));
        $r->get('/api/form-token', [EnquiryController::class, 'token']);
        $r->get('/install', static fn () => redirect('/admin'));

        // Dashboard: sign in
        $r->get('/admin/login', [Admin\AuthController::class, 'loginForm']);
        $r->post('/admin/login', [Admin\AuthController::class, 'login']);
        $r->post('/admin/logout', [Admin\AuthController::class, 'logout']);
        $r->get('/admin/recover', [Admin\AuthController::class, 'recoverForm']);
        $r->post('/admin/recover', [Admin\AuthController::class, 'recover']);

        // Dashboard: overview and enquiries
        $r->get('/admin', [Admin\DashboardController::class, 'index']);
        $r->get('/admin/enquiries', [Admin\EnquiriesController::class, 'index']);
        $r->get('/admin/enquiries/export', [Admin\EnquiriesController::class, 'export']);
        $r->post('/admin/enquiries/purge-spam', [Admin\EnquiriesController::class, 'purgeSpam']);
        $r->get('/admin/enquiries/{id}', [Admin\EnquiriesController::class, 'show']);
        $r->post('/admin/enquiries/{id}', [Admin\EnquiriesController::class, 'update']);
        $r->post('/admin/enquiries/{id}/status', [Admin\EnquiriesController::class, 'status']);
        $r->post('/admin/enquiries/{id}/spam', [Admin\EnquiriesController::class, 'spam']);
        $r->post('/admin/enquiries/{id}/delete', [Admin\EnquiriesController::class, 'delete']);

        // Dashboard: website content
        $r->get('/admin/content', [Admin\ContentController::class, 'index']);
        $r->post('/admin/content/sections', [Admin\ContentController::class, 'saveSections']);
        $r->get('/admin/content/{slug}', [Admin\ContentController::class, 'edit']);
        $r->post('/admin/content/{slug}', [Admin\ContentController::class, 'save']);

        // Dashboard: photos and videos
        $r->get('/admin/media', [Admin\MediaController::class, 'index']);
        $r->get('/admin/media/list', [Admin\MediaController::class, 'list']);
        $r->post('/admin/media/upload', [Admin\MediaController::class, 'upload']);
        $r->get('/admin/media/{id}', [Admin\MediaController::class, 'show']);
        $r->post('/admin/media/{id}', [Admin\MediaController::class, 'update']);
        $r->post('/admin/media/{id}/replace', [Admin\MediaController::class, 'replace']);
        $r->post('/admin/media/{id}/delete', [Admin\MediaController::class, 'delete']);

        // Dashboard: preview and publishing
        $r->get('/admin/preview', [Admin\PublishController::class, 'preview']);
        $r->post('/admin/publish', [Admin\PublishController::class, 'publish']);
        $r->post('/admin/discard', [Admin\PublishController::class, 'discard']);
        $r->get('/admin/history', [Admin\PublishController::class, 'history']);
        $r->post('/admin/history/{id}/restore', [Admin\PublishController::class, 'restore']);

        // Dashboard: checklist, settings, people, help
        $r->get('/admin/checklist', [Admin\DashboardController::class, 'checklist']);
        $r->post('/admin/checklist', [Admin\DashboardController::class, 'saveChecklist']);
        $r->get('/admin/settings', [Admin\SettingsController::class, 'index']);
        $r->post('/admin/settings', [Admin\SettingsController::class, 'save']);
        $r->post('/admin/settings/test-email', [Admin\SettingsController::class, 'testEmail']);
        $r->get('/admin/users', [Admin\UsersController::class, 'index']);
        $r->post('/admin/users', [Admin\UsersController::class, 'create']);
        $r->post('/admin/users/{id}', [Admin\UsersController::class, 'update']);
        $r->get('/admin/account', [Admin\UsersController::class, 'account']);
        $r->post('/admin/account', [Admin\UsersController::class, 'saveAccount']);
        $r->get('/admin/help', [Admin\DashboardController::class, 'help']);

        return $r;
    }

    private static function handleErrors(): void
    {
        $debug = (bool) Config::get('debug', false);
        ini_set('display_errors', $debug ? '1' : '0');
        error_reporting(E_ALL);

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            Logger::warning($message, ['file' => str_replace(APP_ROOT, '', $file), 'line' => $line]);
            return !Config::get('debug', false);
        });

        set_exception_handler(static function (\Throwable $e) use ($debug): void {
            Logger::error(get_class($e) . ': ' . $e->getMessage(), [
                'file' => str_replace(APP_ROOT, '', $e->getFile()),
                'line' => $e->getLine(),
                'path' => Request::path(),
            ]);
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/html; charset=utf-8');
                header('Cache-Control: no-store');
            }
            if (Request::wantsJson()) {
                echo json_encode(['ok' => false, 'error' => 'Something went wrong on the server. Please try again.']);
                return;
            }
            echo View::render('error', [
                'title' => 'Something went wrong',
                'message' => 'Sorry — something went wrong on our side. Please try again in a moment.',
                'detail' => $debug ? $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')' : null,
            ]);
        });
    }
}
