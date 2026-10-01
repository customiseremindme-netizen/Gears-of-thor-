<?php
declare(strict_types=1);

namespace Got;

/** Renders PHP templates from app/views. */
final class View
{
    public static function render(string $template, array $vars = [], ?string $layout = null): string
    {
        $content = self::capture($template, $vars);
        if ($layout === null) {
            return $content;
        }
        return self::capture($layout, $vars + ['content' => $content]);
    }

    private static function capture(string $template, array $vars): string
    {
        $file = APP_DIR . '/views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("Template not found: {$template}");
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }

    /** Render a partial template inside another template. */
    public static function partial(string $template, array $vars = []): void
    {
        echo self::capture($template, $vars);
    }
}
