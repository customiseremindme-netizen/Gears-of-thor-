<?php
defined('GOT_APP') || exit;

use Got\Csrf;
use Got\Media;

/** @var array $media */
/** @var array $usage */
/** @var int $maxUpload */

$m = $media;
$isImage = $m['kind'] === 'image';
?>
<p class="back-link"><a href="<?= e(url('/admin/media')) ?>">← Photos & videos</a></p>
<div class="page-head">
  <h1><?= e($m['original_name'] ?: 'File ' . $m['id']) ?></h1>
  <p><?= $isImage ? 'Photo' : 'Video' ?> · <?= $m['width'] ? (int) $m['width'] . '×' . (int) $m['height'] . ' pixels · ' : '' ?><?= e(human_bytes((int) $m['bytes'])) ?> · uploaded <?= e(ist($m['created_at'])) ?></p>
</div>

<div class="grid-2 grid-2--wide-left">
  <section class="card media-preview">
    <?php if ($isImage): ?>
      <img src="<?= e(Media::urlFor($m, 1440)) ?>" alt="<?= e($m['alt']) ?>">
    <?php else: ?>
      <video src="<?= e(Media::fileUrl($m)) ?>" controls muted playsinline preload="metadata"></video>
    <?php endif; ?>
  </section>

  <div class="stack-lg">
    <section class="card">
      <h2>Description</h2>
      <form method="post" action="<?= e(url('/admin/media/' . $m['id'])) ?>" class="stack">
        <?= Csrf::field() ?>
        <div class="fld">
          <label for="alt">Describe what the <?= $isImage ? 'photo' : 'video' ?> shows</label>
          <p class="fld__help" id="alt-help">Read aloud to people who cannot see it, and helps Google. For example: “Dumbbell rack and benches at GOT FITNEZZ”.</p>
          <textarea id="alt" name="alt" rows="3" maxlength="300" aria-describedby="alt-help"><?= e($m['alt']) ?></textarea>
        </div>
        <button class="btn btn--primary" type="submit">Save description</button>
      </form>
    </section>

    <section class="card">
      <h2>Where it is used</h2>
      <?php if ($usage): ?>
        <ul class="plain-list">
          <?php foreach ($usage as $place): ?><li><?= e($place) ?></li><?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="muted">Not used anywhere yet. Choose it in a section to show it on the website.</p>
      <?php endif; ?>
    </section>

    <section class="card">
      <h2>Replace this file</h2>
      <p class="muted small">The new file takes its place everywhere in your draft. Publish to show it on the website.</p>
      <form method="post" action="<?= e(url('/admin/media/' . $m['id'] . '/replace')) ?>" enctype="multipart/form-data" class="stack">
        <?= Csrf::field() ?>
        <input type="file" name="file" required accept="<?= $isImage ? 'image/jpeg,image/png,image/webp' : 'video/mp4,video/webm' ?>">
        <button class="btn btn--light" type="submit">Upload replacement</button>
      </form>
    </section>

    <section class="card">
      <h2>Delete</h2>
      <?php if ($usage): ?>
        <p class="muted small">Remove it from the places above (and publish) before deleting.</p>
      <?php else: ?>
        <form method="post" action="<?= e(url('/admin/media/' . $m['id'] . '/delete')) ?>" data-confirm="Delete this file permanently?">
          <?= Csrf::field() ?>
          <button class="btn btn--danger" type="submit">Delete file</button>
        </form>
      <?php endif; ?>
    </section>
  </div>
</div>
