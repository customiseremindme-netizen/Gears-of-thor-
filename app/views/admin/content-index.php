<?php
defined('GOT_APP') || exit;

use Got\Csrf;
use Got\Schema;

/** @var Got\Site $site */
/** @var array $pages */

$editSlug = [
    'hero' => 'hero', 'statement' => 'statement', 'strip' => 'strip', 'training' => 'training', 'gym' => 'gym',
    'coaches' => 'coaches', 'reviews' => 'reviews', 'stories' => 'stories', 'membership' => 'membership',
    'contact' => 'contact', 'faq' => 'faq',
];
$emptyHints = [
    'gym' => 'Hidden until you add gym photos',
    'coaches' => 'Hidden until you add a coach',
    'reviews' => 'Hidden until you add a review with permission',
    'stories' => 'Hidden until you add a story with consent',
];
?>
<div class="page-head">
  <h1>Page sections</h1>
  <p>Your website is one long page. Drag sections (or use the arrows) to change their order, switch sections off, and open any section to edit its text and photos.</p>
</div>

<form method="post" action="<?= e(url('/admin/content/sections')) ?>" data-dirty-warn>
  <?= Csrf::field() ?>
  <ol class="section-list" data-sortable>
    <?php foreach ($site->sections as $section):
        $id = $section['id'];
        $meta = Schema::SECTIONS[$id];
        $locked = !empty($meta['locked']);
        $status = $section['visible'] ? ['Showing', 'ok'] : ($section['reason'] === 'off' ? ['Switched off', 'off'] : [$emptyHints[$id] ?? 'Hidden — no content yet', 'empty']);
    ?>
      <li class="section-row" data-sortable-item<?= $id === 'hero' ? ' data-fixed' : '' ?>>
        <span class="section-row__handle" aria-hidden="true"<?= $id === 'hero' ? ' hidden' : '' ?>>⋮⋮</span>
        <input type="hidden" name="order[]" value="<?= e($id) ?>">
        <div class="section-row__main">
          <strong><?= e($meta['label']) ?></strong>
          <span class="pill pill--<?= e($status[1]) ?>"><?= e($status[0]) ?></span>
        </div>
        <div class="section-row__tools">
          <?php if ($locked): ?>
            <span class="muted small"><?= $id === 'hero' ? 'Always first' : 'Always shown' ?></span>
          <?php else: ?>
            <label class="switch switch--compact">
              <input type="checkbox" name="on[<?= e($id) ?>]" value="1"<?= $section['reason'] !== 'off' ? ' checked' : '' ?>>
              <span class="switch__track" aria-hidden="true"></span>
              <span class="switch__label">Show<span class="visually-hidden"> <?= e($meta['label']) ?></span></span>
            </label>
          <?php endif; ?>
          <?php if ($id !== 'hero'): ?>
            <button type="button" class="icon-btn" data-move="up" aria-label="Move <?= e($meta['label']) ?> up">↑</button>
            <button type="button" class="icon-btn" data-move="down" aria-label="Move <?= e($meta['label']) ?> down">↓</button>
          <?php endif; ?>
          <a class="btn btn--light btn--sm" href="<?= e(url('/admin/content/' . $editSlug[$id])) ?>">Edit</a>
        </div>
      </li>
    <?php endforeach; ?>
  </ol>
  <div class="save-bar">
    <button class="btn btn--primary" type="submit">Save order</button>
    <span class="muted small">Saved as a draft — preview, then publish.</span>
  </div>
</form>

<section class="card">
  <h2>Other parts of the website</h2>
  <ul class="link-grid">
    <li><a href="<?= e(url('/admin/content/footer')) ?>"><strong>Menu & footer</strong><span>Menu labels, phone buttons, closing line</span></a></li>
    <li><a href="<?= e(url('/admin/content/business')) ?>"><strong>Business info & hours</strong><span>Phone, address, WhatsApp, social links</span></a></li>
    <li><a href="<?= e(url('/admin/content/brand')) ?>"><strong>Logo & colours</strong><span>Logo, icon, name and colours</span></a></li>
    <li><a href="<?= e(url('/admin/content/seo')) ?>"><strong>Search & sharing</strong><span>Google title, description, sharing image</span></a></li>
    <li><a href="<?= e(url('/admin/content/privacy')) ?>"><strong>Privacy notice</strong><span>The page linked from the form and footer</span></a></li>
  </ul>
</section>
