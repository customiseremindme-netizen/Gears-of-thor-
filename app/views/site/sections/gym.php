<?php
defined('GOT_APP') || exit;

use Got\Html;
use Got\Media;
use Got\Text;

/** @var Got\Site $site */

$g = $site->c['gym'];
$photos = $site->galleryPhotos();
$facilities = $site->facilities();
?>
<section class="section gym" id="gym" aria-labelledby="gym-title">
  <div class="container">
    <div class="section-head">
      <div class="section-head__title">
        <?php if (s($g['eyebrow'] ?? '') !== ''): ?>
          <p class="eyebrow reveal"><?= e($g['eyebrow']) ?></p>
        <?php endif; ?>
        <h2 class="display h-xl reveal" id="gym-title" style="--d:1"><?= Text::heading(s($g['heading'] ?? '')) ?></h2>
      </div>
      <?php if (s($g['intro'] ?? '') !== ''): ?>
        <div class="section-head__intro lead reveal" style="--d:2"><?= Text::paragraphs(s($g['intro']), $site->tokens) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($photos): ?>
    <div class="gallery container container--wide" data-gallery>
      <?php foreach ($photos as $i => $photo):
          $m = $photo['media'];
          $layout = $photo['layout'];
          if ($layout === 'auto' || $layout === '') {
              $ratio = $m['width'] && $m['height'] ? $m['width'] / $m['height'] : 1.5;
              $layout = $ratio >= 1.45 ? 'wide' : ($ratio <= 0.85 ? 'tall' : 'standard');
          }
          $alt = $photo['caption'] !== '' ? $photo['caption'] : (string) $m['alt'];
          $largest = Media::urlFor($m, 2048);
      ?>
        <figure class="gallery__item gallery__item--<?= e($layout) ?> reveal-img">
          <button class="gallery__open" type="button"
                  data-lightbox-item="<?= $i ?>"
                  data-full="<?= e($largest) ?>"
                  data-srcset="<?= e(Media::srcset($m)) ?>"
                  data-caption="<?= e($photo['caption']) ?>"
                  data-alt="<?= e($alt) ?>"
                  aria-label="<?= e('Open photo ' . ($i + 1) . ' of ' . count($photos) . ($alt !== '' ? ': ' . $alt : '')) ?>">
            <?= Html::picture($m, $layout === 'wide' ? '(min-width: 900px) 66vw, 100vw' : '(min-width: 900px) 33vw, 50vw', $alt) ?>
            <span class="gallery__zoom" aria-hidden="true"><?= Html::icon('expand') ?></span>
          </button>
          <?php if ($photo['caption'] !== ''): ?>
            <figcaption class="gallery__caption"><?= e($photo['caption']) ?></figcaption>
          <?php endif; ?>
        </figure>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($facilities): ?>
    <div class="container facilities reveal">
      <?php if (s($g['facilities_heading'] ?? '') !== ''): ?>
        <h3 class="facilities__title"><?= e($g['facilities_heading']) ?></h3>
      <?php endif; ?>
      <ul class="facilities__list">
        <?php foreach ($facilities as $facility): ?>
          <li><?= Html::icon('check') ?><?= e($facility['label']) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</section>
