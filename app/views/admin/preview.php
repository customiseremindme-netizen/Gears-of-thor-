<?php
defined('GOT_APP') || exit;

use Got\AdminView;
use Got\Auth;
use Got\Csrf;

/** @var string $device */
/** @var bool $hasChanges */
/** @var array $changedKeys */
?>
<div class="page-head page-head--row">
  <div>
    <h1>Preview & publish</h1>
    <p><?= $hasChanges
        ? 'This is your draft. Visitors still see the previous version until you publish.'
        : 'Your draft matches the live website.' ?></p>
  </div>
  <div class="actions">
    <?php if ($hasChanges && Auth::can('publish')): ?>
      <form method="post" action="<?= e(url('/admin/discard')) ?>" data-confirm="Throw away all unpublished changes? This cannot be undone.">
        <?= Csrf::field() ?>
        <button class="btn btn--light" type="submit">Discard changes</button>
      </form>
      <form method="post" action="<?= e(url('/admin/publish')) ?>" data-confirm="Publish your changes to the live website?">
        <?= Csrf::field() ?>
        <input type="hidden" name="return" value="/admin/preview">
        <button class="btn btn--primary" type="submit">Publish now</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if ($hasChanges): ?>
  <div class="notice">
    <p><strong>Changed since the last publish:</strong> <?= e(implode(', ', array_map([AdminView::class, 'areaLabel'], $changedKeys))) ?>.</p>
  </div>
<?php endif; ?>

<div class="preview-tools" role="group" aria-label="Screen size">
  <?php foreach (['desktop' => 'Desktop', 'tablet' => 'Tablet', 'phone' => 'Phone'] as $key => $label): ?>
    <button type="button" class="btn btn--light btn--sm" data-device="<?= e($key) ?>" aria-pressed="<?= $device === $key ? 'true' : 'false' ?>"><?= e($label) ?></button>
  <?php endforeach; ?>
  <a class="btn-link" href="<?= e(url('/?preview=1')) ?>" target="_blank" rel="noopener">Open preview in a new tab ↗</a>
</div>

<div class="preview-stage preview-stage--<?= e($device) ?>" data-preview-stage data-device-current="<?= e($device) ?>">
  <div class="preview-box" data-preview-box>
    <iframe src="<?= e(url('/?preview=1')) ?>" title="Preview of your website draft" data-preview-frame></iframe>
  </div>
</div>
<p class="muted small">Sections that are hidden on the live site are marked with a dashed box in the preview. The callback form is switched off in preview.</p>
