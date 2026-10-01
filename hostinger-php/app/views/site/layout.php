<?php
defined('GOT_APP') || exit;

use Got\Media;
use Got\Security;
use Got\Seo;
use Got\Text;
use Got\Turnstile;
use Got\View;

/** @var Got\Site $site */
/** @var string $content */
/** @var string $page */

$c = $site->c;
$brandName = $site->str('brand.name');
$title = match ($page) {
    'privacy' => $site->str('privacy.title') . ' | ' . $brandName,
    '404' => 'Page not found | ' . $brandName,
    default => $site->str('seo.title'),
};
$description = $site->str('seo.description');
$canonical = abs_url($page === 'privacy' ? '/privacy' : '/');
$share = Seo::shareImage($site);
$icon = $site->media($c['brand']['icon'] ?? null);
$icon32 = $icon ? Media::iconUrl($icon, 32) : null;
$icon180 = $icon ? Media::iconUrl($icon, 180) : null;
$noindex = $site->preview || !empty($c['seo']['hide_from_search']) || $page === '404';
$isHome = $page === 'home';
$nonce = Security::nonce();
?><!doctype html>
<html lang="en-IN" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<?php if ($noindex): ?>
<meta name="robots" content="noindex, nofollow">
<?php endif; ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="theme-color" content="<?= e($site->str('theme.bg')) ?>">
<meta name="format-detection" content="telephone=no">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($brandName) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:locale" content="en_IN">
<?php if ($share): ?>
<meta property="og:image" content="<?= e($share['url']) ?>">
<?php if ($share['height']): ?>
<meta property="og:image:width" content="<?= (int) $share['width'] ?>">
<meta property="og:image:height" content="<?= (int) $share['height'] ?>">
<?php endif; ?>
<meta property="og:image:alt" content="<?= e($share['alt'] ?: $brandName) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php endif; ?>
<link rel="icon" type="image/png" sizes="32x32" href="<?= e($icon32 ?? asset('img/favicon-32.png')) ?>">
<link rel="apple-touch-icon" href="<?= e($icon180 ?? asset('img/apple-touch-icon.png')) ?>">
<link rel="manifest" href="<?= e(url('/site.webmanifest')) ?>">
<link rel="preload" href="<?= e(url('/assets/fonts/archivo-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<style><?= $site->themeCss() ?></style>
<script nonce="<?= e($nonce) ?>">
document.documentElement.className = document.documentElement.className.replace('no-js', 'js');
// If the site script cannot run, never leave content hidden.
setTimeout(function () { if (!window.gotReady) document.documentElement.classList.add('reveal-all'); }, 2500);
</script>
<script src="<?= e(asset('js/site.js')) ?>" defer></script>
<?php if ($isHome && Turnstile::enabled() && !$site->preview): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
<?= $isHome ? Seo::jsonLd($site) : '' ?>
</head>
<body class="page-<?= e($page) ?><?= $site->preview ? ' is-preview' : '' ?>">
<a class="skip-link" href="#main">Skip to content</a>
<?php View::partial('site/partials/header', ['site' => $site, 'page' => $page]); ?>
<main id="main" tabindex="-1">
<?= $content ?>
</main>
<?php View::partial('site/partials/footer', ['site' => $site, 'page' => $page]); ?>
<?php if ($isHome): ?>
<?php View::partial('site/partials/mobile-bar', ['site' => $site]); ?>
<?php View::partial('site/partials/lightbox'); ?>
<?php endif; ?>
<?php if ($site->preview): ?>
<div class="preview-badge" role="status">
  <strong>Preview</strong> <span>Unpublished changes — not live yet.</span>
  <a href="<?= e(url('/admin/preview')) ?>" target="_top">Back to dashboard</a>
</div>
<?php endif; ?>
</body>
</html>
