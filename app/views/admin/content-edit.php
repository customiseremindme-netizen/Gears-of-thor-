<?php
defined('GOT_APP') || exit;

use Got\Csrf;
use Got\FormRenderer;
use Got\Text;

/** @var string $slug */
/** @var array $page */
/** @var array $values */
/** @var array $errors */

$isSection = $page['menu'] === 'sections';
?>
<?php if ($isSection): ?>
  <p class="back-link"><a href="<?= e(url('/admin/content')) ?>">← Page sections</a></p>
<?php endif; ?>
<div class="page-head">
  <h1><?= e($page['title']) ?></h1>
  <?php if (!empty($page['intro'])): ?><p><?= e($page['intro']) ?></p><?php endif; ?>
</div>
<?php if (!empty($page['notice'])): ?>
  <div class="notice"><p><?= e($page['notice']) ?></p></div>
<?php endif; ?>

<?php if (count($page['groups']) > 2): ?>
  <nav class="jump" aria-label="On this page">
    <?php foreach ($page['groups'] as $group): ?>
      <a href="#group-<?= e(Text::slug($group['title'])) ?>"><?= e($group['title']) ?></a>
    <?php endforeach; ?>
  </nav>
<?php endif; ?>

<form method="post" action="<?= e(url('/admin/content/' . $slug)) ?>" class="content-form" novalidate data-dirty-warn data-content-form>
  <?= Csrf::field() ?>
  <?php foreach ($page['groups'] as $group): ?>
    <section class="card" id="group-<?= e(Text::slug($group['title'])) ?>">
      <h2><?= e($group['title']) ?></h2>
      <?php if (!empty($group['intro'])): ?><p class="card__intro"><?= e($group['intro']) ?></p><?php endif; ?>
      <?php foreach ($group['fields'] as $field): ?>
        <?= FormRenderer::field($field, $values, $errors) ?>
      <?php endforeach; ?>
      <?php if ($slug === 'brand' && str_contains($group['title'], 'Colour')): ?>
        <div class="contrast-check" data-contrast-check aria-live="polite"></div>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>

  <div class="save-bar">
    <button class="btn btn--primary" type="submit">Save draft</button>
    <button class="btn btn--light" type="submit" name="then" value="preview">Save and preview</button>
    <span class="muted small">Saving does not change the live website until you publish.</span>
  </div>
</form>
