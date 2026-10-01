<?php
declare(strict_types=1);

namespace Got;

/**
 * A deliberately small Markdown renderer for the privacy notice and the
 * dashboard help page: headings, paragraphs, lists, quotes, bold, italics,
 * inline code and links. All text is escaped first, so it is always safe.
 */
final class Markdown
{
    /** @var array<int, array{level: int, text: string, id: string}> */
    public static array $headings = [];

    public static function render(string $markdown, array $tokens = []): string
    {
        self::$headings = [];
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $markdown));
        $html = '';
        $paragraph = [];
        $list = null; // ['type' => 'ul'|'ol', 'items' => []]
        $quote = [];

        $flushParagraph = static function () use (&$paragraph, &$html, $tokens): void {
            if ($paragraph) {
                $html .= '<p>' . self::inline(implode(' ', $paragraph), $tokens) . "</p>\n";
                $paragraph = [];
            }
        };
        $flushList = static function () use (&$list, &$html, $tokens): void {
            if ($list) {
                $html .= '<' . $list['type'] . ">\n";
                foreach ($list['items'] as $item) {
                    $html .= '<li>' . self::inline($item, $tokens) . "</li>\n";
                }
                $html .= '</' . $list['type'] . ">\n";
                $list = null;
            }
        };
        $flushQuote = static function () use (&$quote, &$html, $tokens): void {
            if ($quote) {
                $html .= '<blockquote><p>' . self::inline(implode(' ', $quote), $tokens) . "</p></blockquote>\n";
                $quote = [];
            }
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $flushParagraph();
                $flushList();
                $flushQuote();
                continue;
            }
            if (preg_match('/^(#{1,4})\s+(.+)$/', $trimmed, $m)) {
                $flushParagraph();
                $flushList();
                $flushQuote();
                $level = max(2, min(4, strlen($m[1]))); // "#" and "##" become <h2>: the page already has an <h1>
                $text = trim($m[2]);
                $id = Text::slug($text);
                self::$headings[] = ['level' => $level, 'text' => $text, 'id' => $id];
                $html .= "<h{$level} id=\"{$id}\">" . self::inline($text, $tokens) . "</h{$level}>\n";
                continue;
            }
            if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $m)) {
                $flushParagraph();
                $flushQuote();
                if (!$list || $list['type'] !== 'ul') {
                    $flushList();
                    $list = ['type' => 'ul', 'items' => []];
                }
                $list['items'][] = $m[1];
                continue;
            }
            if (preg_match('/^\d+[.)]\s+(.+)$/', $trimmed, $m)) {
                $flushParagraph();
                $flushQuote();
                if (!$list || $list['type'] !== 'ol') {
                    $flushList();
                    $list = ['type' => 'ol', 'items' => []];
                }
                $list['items'][] = $m[1];
                continue;
            }
            if (preg_match('/^>\s?(.*)$/', $trimmed, $m)) {
                $flushParagraph();
                $flushList();
                $quote[] = $m[1];
                continue;
            }
            if ($list && preg_match('/^\s{2,}\S/', $line)) {
                // continuation of the previous list item
                $list['items'][count($list['items']) - 1] .= ' ' . $trimmed;
                continue;
            }
            $flushList();
            $flushQuote();
            $paragraph[] = $trimmed;
        }
        $flushParagraph();
        $flushList();
        $flushQuote();
        return $html;
    }

    public static function inline(string $text, array $tokens = []): string
    {
        // Protect `code` first so its contents are shown exactly as typed.
        $codes = [];
        $text = (string) preg_replace_callback('/`([^`]+)`/', static function ($m) use (&$codes) {
            $codes[] = '<code>' . e($m[1]) . '</code>';
            return "\x1A" . (count($codes) - 1) . "\x1A";
        }, $text);
        $html = e($text);
        // Links: [text](https://…), (/path), (#anchor), (mailto:…), (tel:…)
        $html = (string) preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', static function ($m) {
            $href = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
            if (!preg_match('#^(https?://|mailto:|tel:|/|\#)#i', $href)) {
                return $m[0];
            }
            if (str_starts_with($href, '/')) {
                $href = url($href);
            }
            $external = preg_match('#^https?://#i', $href) ? ' rel="noopener" target="_blank"' : '';
            return '<a href="' . e($href) . '"' . $external . '>' . $m[1] . '</a>';
        }, $html);
        $html = (string) preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $html);
        $html = (string) preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/u', '<em>$1</em>', $html);
        $html = Text::tokens($html, $tokens);
        return (string) preg_replace_callback("/\x1A(\d+)\x1A/", static fn ($m) => $codes[(int) $m[1]] ?? '', $html);
    }
}
