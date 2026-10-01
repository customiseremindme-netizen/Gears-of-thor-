<?php
defined('GOT_APP') || exit;

use Got\Csrf;
use Got\Media;

/** @var array $items */
/** @var string $kind */
/** @var array $used */
/** @var int $maxUpload */

$limit = min($maxUpload, Media::MAX_VIDEO_BYTES);
?>
<div class="page-head">
  <h1>Photos & videos</h1>
  <p>Upload your own gym photos and videos here, then choose them in any section. Photos are resized automatically and camera location data is removed.</p>
</div>

<section class="card upload-card">
  <form method="post" action="<?= e(url('/admin/media/upload')) ?>" enctype="multipart/form-data" class="dropzone" data-dropzone>
    <?= Csrf::field() ?>
    <p class="dropzone__title"><strong>Drag photos or a video here</strong> or</p>
    <label class="btn btn--primary file-btn">Choose files<input type="file" name="files[]" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" data-dropzone-input></label>
    <noscript><button class="btn btn--light" type="submit">Upload</button></noscript>
    <p class="muted small">JPG, PNG or WebP photos (up to 20 MB each) · MP4 videos (keep under 15 MB for fast loading). Your hosting accepts files up to <?= e(human_bytes($maxUpload)) ?> per upload.</p>
    <ul class="upload-progress" data-upload-list aria-live="polite"></ul>
  </form>
</section>

<nav class="tabs" aria-label="Filter">
  <a href="<?= e(url('/admin/media')) ?>"<?= $kind === '' ? ' aria-current="page"' : '' ?>>All</a>
  <a href="<?= e(url('/admin/media?kind=image')) ?>"<?= $kind === 'image' ? ' aria-current="page"' : '' ?>>Photos</a>
  <a href="<?= e(url('/admin/media?kind=video')) ?>"<?= $kind === 'video' ? ' aria-current="page"' : '' ?>>Videos</a>
</nav>

<?php if (!$items): ?>
  <div class="empty"><p>Nothing uploaded yet.</p></div>
<?php else: ?>
  <ul class="media-grid">
    <?php foreach ($items as $m): ?>
      <li class="media-tile">
        <a href="<?= e(url('/admin/media/' . $m['id'])) ?>">
          <span class="media-tile__thumb">
            <?php if ($m['kind'] === 'image'): ?>
              <img src="<?= e(Media::urlFor($m, 480)) ?>" alt="" loading="lazy">
            <?php else: ?>
              <span class="media-tile__video">▶ Video</span>
            <?php endif; ?>
          </span>
          <span class="media-tile__name"><?= e($m['original_name'] ?: 'File ' . $m['id']) ?></span>
          <span class="media-tile__meta">
            <?= $m['width'] ? (int) $m['width'] . '×' . (int) $m['height'] . ' · ' : '' ?><?= e(human_bytes((int) $m['bytes'])) ?>
          </span>
          <span class="media-tile__tags">
            <?php if (!empty($used[$m['id']])): ?><span class="pill pill--ok">In use</span><?php else: ?><span class="pill pill--off">Not used</span><?php endif; ?>
            <?php if ($m['kind'] === 'image' && $m['alt'] === ''): ?><span class="pill pill--warn">No description</span><?php endif; ?>
          </span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
