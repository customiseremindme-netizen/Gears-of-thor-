<?php
defined('GOT_APP') || exit;

use Got\Html;
use Got\Text;

/** @var Got\Site $site */

$t = $site->c['training'];
$items = $site->trainingItems();
$count = count($items);
?>
<section class="section training" id="training" aria-labelledby="training-title">
  <div class="container">
    <div class="section-head">
      <div class="section-head__title">
        <?php if (s($t['eyebrow'] ?? '') !== ''): ?>
          <p class="eyebrow reveal"><?= e($t['eyebrow']) ?></p>
        <?php endif; ?>
        <h2 class="display h-xl reveal" id="training-title" style="--d:1"><?= Text::heading(s($t['heading'] ?? '')) ?></h2>
      </div>
      <?php if (s($t['intro'] ?? '') !== ''): ?>
        <div class="section-head__intro lead reveal" style="--d:2"><?= Text::paragraphs(s($t['intro']), $site->tokens) ?></div>
      <?php endif; ?>
    </div>

    <ol class="training__list training__list--<?= $count ?>" role="list">
      <?php foreach ($items as $i => $item):
          $image = $site->media($item['image'] ?? null);
      ?>
        <li class="training-card reveal<?= $image ? ' training-card--image' : '' ?>" style="--d:<?= $i % 3 ?>">
          <?php if ($image): ?>
            <div class="training-card__media" aria-hidden="true">
              <?= Html::picture($image, '(min-width: 900px) 33vw, 100vw', '') ?>
            </div>
          <?php endif; ?>
          <span class="training-card__index" aria-hidden="true"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="training-card__body">
            <h3 class="training-card__title"><?= e($item['title']) ?></h3>
            <?php if (s($item['text'] ?? '') !== ''): ?>
              <p class="training-card__text"><?= e($item['text']) ?></p>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>

    <?php if (s($t['cta_label'] ?? '') !== ''): ?>
      <div class="section-cta reveal">
        <a class="btn btn--outline btn--lg" href="#enquire"><span><?= e($t['cta_label']) ?></span><?= Html::icon('arrow') ?></a>
      </div>
    <?php endif; ?>
  </div>
</section>
