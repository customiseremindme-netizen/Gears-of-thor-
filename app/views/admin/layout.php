<?php
defined('GOT_APP') || exit;

use Got\AdminView;
use Got\Auth;
use Got\Csrf;
use Got\Html;

/** @var string $content */
/** @var string $title */
/** @var ?array $user */
/** @var array $flashes */
/** @var int $newEnquiries */
/** @var bool $hasChanges */
/** @var array $changedKeys */
/** @var int $checklistOpen */

$nav = $nav ?? '';
$link = static function (string $key, string $href, string $label, string $badge = '') use ($nav): string {
    $current = $nav === $key ? ' aria-current="page"' : '';
    return '<a href="' . e(url($href)) . '"' . $current . '><span>' . e($label) . '</span>' . ($badge !== '' ? '<span class="nav-badge">' . e($badge) . '</span>' : '') . '</a>';
};
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
<title><?= e($title) ?> · GOT FITNEZZ dashboard</title>
<link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset('img/favicon-32.png')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</head>
<body class="admin">
<a class="skip-link" href="#admin-main">Skip to content</a>
<div class="shell">
  <aside class="sidebar" id="admin-sidebar" data-sidebar>
    <div class="sidebar__brand">
      <img src="<?= e(asset('img/logo-480.png')) ?>" alt="" width="58" height="42">
      <div><strong>GOT FITNEZZ</strong><span>Website dashboard</span></div>
    </div>
    <nav class="sidebar__nav" aria-label="Dashboard">
      <?= $link('overview', '/admin', 'Overview') ?>
      <?= $link('enquiries', '/admin/enquiries', 'Enquiries', $newEnquiries > 0 ? (string) $newEnquiries : '') ?>
      <?php if (Auth::can('content')): ?>
        <p class="sidebar__label">Website</p>
        <?= $link('content', '/admin/content', 'Page sections') ?>
        <?= $link('business', '/admin/content/business', 'Business info & hours') ?>
        <?= $link('media', '/admin/media', 'Photos & videos') ?>
        <?= $link('brand', '/admin/content/brand', 'Logo & colours') ?>
        <?= $link('seo', '/admin/content/seo', 'Search & sharing') ?>
        <?= $link('preview', '/admin/preview', 'Preview & publish', $hasChanges ? '•' : '') ?>
        <?= $link('history', '/admin/history', 'Publish history') ?>
        <p class="sidebar__label">Getting ready</p>
        <?= $link('checklist', '/admin/checklist', 'Owner checklist', $checklistOpen > 0 ? (string) $checklistOpen : '') ?>
      <?php endif; ?>
      <?= $link('help', '/admin/help', 'How-to guide') ?>
      <p class="sidebar__label">Account</p>
      <?php if (Auth::can('settings')): ?>
        <?= $link('settings', '/admin/settings', 'Settings') ?>
        <?= $link('users', '/admin/users', 'Users') ?>
      <?php endif; ?>
      <?= $link('account', '/admin/account', 'My account') ?>
    </nav>
    <form class="sidebar__logout" method="post" action="<?= e(url('/admin/logout')) ?>">
      <?= Csrf::field() ?>
      <span><?= e($user['name'] ?? '') ?><small><?= e(Auth::ROLES[$user['role'] ?? 'staff'] ?? '') ?></small></span>
      <button type="submit" class="btn-link">Sign out</button>
    </form>
  </aside>

  <div class="main">
    <header class="topbar">
      <button class="topbar__menu" type="button" aria-expanded="false" aria-controls="admin-sidebar" data-sidebar-toggle>
        <span aria-hidden="true">☰</span> Menu
      </button>
      <?php if (Auth::can('content')): ?>
        <div class="publish-state<?= $hasChanges ? ' has-changes' : '' ?>">
          <?php if ($hasChanges): ?>
            <span class="publish-state__dot" aria-hidden="true"></span>
            <span class="publish-state__text">Unpublished changes: <?= e(implode(', ', array_map([AdminView::class, 'areaLabel'], array_slice($changedKeys, 0, 3)))) ?><?= count($changedKeys) > 3 ? ' +' . (count($changedKeys) - 3) : '' ?></span>
            <a class="btn btn--light btn--sm" href="<?= e(url('/admin/preview')) ?>">Preview</a>
            <?php if (Auth::can('publish')): ?>
              <form method="post" action="<?= e(url('/admin/publish')) ?>" data-confirm="Publish your changes to the live website?">
                <?= Csrf::field() ?>
                <input type="hidden" name="return" value="<?= e(\Got\Request::path()) ?>">
                <button class="btn btn--primary btn--sm" type="submit">Publish</button>
              </form>
            <?php endif; ?>
          <?php else: ?>
            <span class="publish-state__ok">✓ Website is up to date</span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <a class="topbar__site" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">View website <span aria-hidden="true">↗</span></a>
    </header>

    <main class="content" id="admin-main" tabindex="-1">
      <?php foreach ($flashes as $flash): ?>
        <div class="flash flash--<?= e($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>"><?= e($flash['message']) ?></div>
      <?php endforeach; ?>
      <?= $content ?>
    </main>
  </div>
</div>
<div class="modal" data-media-modal hidden>
  <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="media-modal-title">
    <div class="modal__head">
      <h2 id="media-modal-title">Choose a file</h2>
      <button type="button" class="icon-btn" data-modal-close aria-label="Close">✕</button>
    </div>
    <div class="modal__body">
      <p class="modal__hint">Click a file to use it. To add new files, close this window and use “Upload new”.</p>
      <div class="picker-grid" data-picker-grid></div>
    </div>
  </div>
</div>
</body>
</html>
