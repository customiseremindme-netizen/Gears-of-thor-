<?php
declare(strict_types=1);

/**
 * Fast checks of the core rules (no web server or database needed).
 * Run with:  php tests/unit.php
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use Got\Config;
use Got\Content;
use Got\Csv;
use Got\EnquiryForm;
use Got\FormToken;
use Got\Hours;
use Got\Installer;
use Got\Markdown;
use Got\Sanitizer;
use Got\Schema;
use Got\Text;

Config::override(['app_key' => str_repeat('k', 64)]);

$passed = 0;
$failed = 0;
function check(string $name, bool $condition, string $detail = ''): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        return;
    }
    $failed++;
    echo "FAIL: {$name}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
}

// Phone numbers ------------------------------------------------------------
$cases = [
    '8608611123' => '+918608611123',
    '+91 86086 11123' => '+918608611123',
    '091-86086-11123' => null,
    '08608611123' => '+918608611123',
    '91 8608611123' => '+918608611123',
    '(860) 861-1123' => '+918608611123',
    '+44 7700 900123' => '+447700900123',
    '12345' => null,
    '5608611123' => null,
    '+91 12345 67890' => null,
    'abc' => null,
];
foreach ($cases as $input => $expected) {
    $result = EnquiryForm::normalizePhone((string) $input);
    check("phone {$input}", ($result['e164'] ?? null) === $expected, var_export($result, true));
}
check('phone display', EnquiryForm::normalizePhone('8608611123')['display'] === '+91 86086 11123');

// Enquiry validation -----------------------------------------------------------
[$values, $errors] = EnquiryForm::validate(['name' => 'Arun Kumar', 'phone' => '98430 12345', 'goal' => 'Build strength', 'time' => 'Morning', 'message' => '', 'consent' => '1']);
check('valid enquiry has no errors', $errors === [], json_encode($errors));
[, $errors] = EnquiryForm::validate(['name' => '', 'phone' => '123', 'goal' => '', 'time' => '', 'consent' => '']);
check('invalid enquiry flags every field', array_keys($errors) === ['name', 'phone', 'goal', 'time', 'consent'], json_encode(array_keys($errors)));
[, $errors] = EnquiryForm::validate(['name' => 'அருண் குமார்', 'phone' => '9843012345', 'goal' => 'x', 'time' => 'y', 'consent' => '1']);
check('Tamil names are accepted', !isset($errors['name']), json_encode($errors));
[, $errors] = EnquiryForm::validate(['name' => '<script>', 'phone' => '9843012345', 'goal' => 'x', 'time' => 'y', 'consent' => '1']);
check('markup in names is rejected', isset($errors['name']));
[, $errors] = EnquiryForm::validate(['name' => 'Ravi', 'phone' => '9843012345', 'goal' => 'x', 'time' => 'y', 'consent' => '1', 'message' => str_repeat('a', 1001)]);
check('long messages are rejected', isset($errors['message']));
check('link spam detected', EnquiryForm::looksLikeSpam(['name' => 'x', 'message' => 'see http://a.com and https://b.com']) === 'links');
check('normal message not spam', EnquiryForm::looksLikeSpam(['name' => 'Ravi', 'message' => 'What are the fees?']) === null);

// Form token -------------------------------------------------------------------
$now = time();
$token = FormToken::issue($now - 10);
check('token ok after 10s', FormToken::check($token, $now) === 'ok');
check('token too fast', FormToken::check(FormToken::issue($now), $now + 1) === 'too_fast');
check('token expired', FormToken::check(FormToken::issue($now - FormToken::MAX_AGE - 5), $now) === 'expired');
check('token tampered', FormToken::check(substr($token, 0, -1) . (substr($token, -1) === 'a' ? 'b' : 'a'), $now) === 'invalid');
check('token garbage', FormToken::check('nonsense', $now) === 'invalid');

// Setup code ----------------------------------------------------------------------
check('setup code exact', Installer::normalizeCode('7F3A-09C2-B4E1') === '7F3A09C2B4E1');
check('setup code forgiving', Installer::normalizeCode(" 7f3a–o9c2 b4e1\n") === '7F3A09C2B4E1');
check('setup code hidden characters', Installer::normalizeCode("\u{FEFF}7F3A\u{00A0}09C2\u{200B}-B4E1") === '7F3A09C2B4E1');
check('setup code empty', Installer::normalizeCode('  -- ') === '');

// Hours -------------------------------------------------------------------------
$days = Content::defaults()['hours']['days'];
check('12h format', Hours::format12('17:30') === '5:30 PM' && Hours::format12('00:15') === '12:15 AM' && Hours::format12('12:00') === '12:00 PM');
check('range same period', Hours::range(['open' => '05:30', 'close' => '09:30']) === '5:30–9:30 AM');
check('range across noon', Hours::range(['open' => '11:00', 'close' => '14:00']) === '11:00 AM–2:00 PM');
$groups = Hours::grouped($days);
check('grouped Monday–Saturday', $groups[0]['label'] === 'Monday–Saturday' && count($groups) === 2, json_encode(array_column($groups, 'label')));
check('summary text', Hours::summary($days) === 'Monday–Saturday: 5:30–9:30 AM and 5:30–9:30 PM. Sunday: 7:00–9:30 AM and 6:00–8:30 PM.', Hours::summary($days));
$sunday = Hours::today($days, new DateTimeImmutable('2026-10-04 08:00', new DateTimeZone('Asia/Kolkata')));
check('today is Sunday', $sunday['key'] === 'sun' && $sunday['text'] === '7:00–9:30 AM and 6:00–8:30 PM');
$spec = Hours::schemaOrg($days);
check('schema.org hours', count($spec) === 4 && $spec[0]['opens'] === '05:30' && count($spec[0]['dayOfWeek']) === 6, json_encode($spec));

// Text ------------------------------------------------------------------------------
check('accent markup', Text::inline('BUILD *YOURSELF.*') === 'BUILD <span class="hl">YOURSELF.</span>');
check('accent escapes html', Text::inline('<b>*x*</b>') === '&lt;b&gt;<span class="hl">x</span>&lt;/b&gt;');
check('plain strips stars', Text::plain("BUILD\n*YOURSELF.*") === 'BUILD YOURSELF.');
check('tel href', Text::telHref('+91 86086 11123') === 'tel:+918608611123');
check('whatsapp href', Text::whatsappHref('86086 11123') === 'https://wa.me/918608611123');
check('instagram handle', Text::instagramHandle('https://www.instagram.com/got_fitnezz/') === '@got_fitnezz');
check('paragraph tokens', Text::paragraphs("Call [phone].", ['phone' => '<a>x</a>']) === '<p>Call <a>x</a>.</p>');
check('paragraphs escape', !str_contains(Text::paragraphs('<img src=x onerror=alert(1)>'), '<img'));
check('listing', Text::listing(['A', 'B', 'C']) === 'A, B and C' && Text::listing(['A']) === 'A');

// Markdown ----------------------------------------------------------------------------
$html = Markdown::render("## Title\n\nHello **world** and [link](https://example.com).\n\n- one\n- two\n\n<script>alert(1)</script>\n[bad](javascript:alert(1))");
check('markdown heading', str_contains($html, '<h2 id="title">Title</h2>'));
check('markdown bold', str_contains($html, '<strong>world</strong>'));
check('markdown link', str_contains($html, 'href="https://example.com"'));
check('markdown list', str_contains($html, '<li>one</li>'));
check('markdown escapes script', !str_contains($html, '<script>'));
check('markdown blocks javascript links', !str_contains($html, 'href="javascript'));

// CSV --------------------------------------------------------------------------------------
check('csv formula guard', Csv::cell('=SUM(A1)') === "'=SUM(A1)" && Csv::cell('+44') === "'+44" && Csv::cell('Ravi') === 'Ravi');

// Content merge ---------------------------------------------------------------------------
$merged = Content::mergeDefaults(['hero' => ['headline' => 'X'], 'faq' => ['items' => []]], Content::defaults());
check('merge keeps saved values', $merged['hero']['headline'] === 'X');
check('merge fills missing keys', $merged['hero']['eyebrow'] === 'GOT FITNEZZ · PALLADAM');
check('merge never refills lists', $merged['faq']['items'] === []);
check('merge fills objects', isset($merged['business']['phone']));

// Default content respects the brief ---------------------------------------------------------
$defaults = Content::defaults();
check('hours start unconfirmed', $defaults['hours']['confirmed'] === false);
check('whatsapp starts off', $defaults['business']['whatsapp_confirmed'] === false && $defaults['business']['whatsapp'] === '');
check('no plans invented', $defaults['membership']['plans'] === []);
check('no reviews invented', $defaults['reviews']['items'] === [] && $defaults['stories']['items'] === [] && $defaults['coaches']['items'] === []);
check('facilities start hidden', array_filter($defaults['gym']['facilities'], static fn ($f) => $f['visible']) === []);
check('only the three requested training options are on', array_column(array_filter($defaults['training']['items'], static fn ($i) => $i['visible']), 'title') === ['Strength Training', 'Cardio', 'Gym Training']);
check('no CrossFit claims', !str_contains(strtolower(json_encode($defaults)), 'crossfit'));
check('no free trial claims', !str_contains(strtolower(json_encode($defaults)), 'free trial'));

// Schema --------------------------------------------------------------------------------------
check('media path list item', Schema::isMediaPath('gym.photos.3.image') && Schema::isMediaPath('hero.image') && !Schema::isMediaPath('hero.headline'));
foreach (Schema::pages() as $slug => $page) {
    foreach (Schema::fields($page) as $field) {
        $path = $field['path'];
        check("schema path exists in defaults: {$slug} {$path}", array_get($defaults, $path, '__missing__') !== '__missing__');
    }
}

// Sanitizer ------------------------------------------------------------------------------------
[$v, $err] = Sanitizer::value(['type' => 'url'], 'instagram.com/got_fitnezz');
check('url gets https', $v === 'https://instagram.com/got_fitnezz' && $err === null, (string) $v);
[$v, $err] = Sanitizer::value(['type' => 'url'], 'javascript:alert(1)');
check('javascript url rejected', $err !== null);
[$v, $err] = Sanitizer::value(['type' => 'color'], 'd8e12a');
check('colour normalised', $v === '#D8E12A' && $err === null);
[$v, $err] = Sanitizer::value(['type' => 'text', 'max' => 5], 'abcdefgh');
check('text max length', $err !== null && $v === 'abcde');
[$v, $err] = Sanitizer::value(['type' => 'text', 'required' => true], "   ");
check('required text', $err !== null);
[$v, $err] = Sanitizer::value(['type' => 'tel'], '+91 86086 11123');
check('tel ok', $err === null && $v === '+91 86086 11123');
[$v, $err] = Sanitizer::value(['type' => 'textarea'], "a\r\n\r\n\r\n\r\nb\x07");
check('textarea cleaned', $v === "a\n\nb", json_encode($v));
[$v, $err] = Sanitizer::value(['type' => 'hours'], ['mon' => [['open' => '09:00', 'close' => '08:00']]]);
check('hours close before open rejected', $err !== null);
[$v, $err] = Sanitizer::value(['type' => 'hours'], ['mon' => [['open' => '17:00', 'close' => '21:00'], ['open' => '06:00', 'close' => '09:00']]]);
check('hours sorted', $err === null && $v['mon'][0]['open'] === '06:00' && $v['tue'] === []);
[$v, $err] = Sanitizer::value(['type' => 'select', 'options' => ['a' => 'A', 'b' => 'B']], 'zzz');
check('select falls back', $v === 'a');

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
