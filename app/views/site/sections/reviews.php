<?php
defined('GOT_APP') || exit;

use Got\Html;
use Got\Text;

/** @var Got\Site $site */

$s = $site->c['reviews'];
$reviews = $site->reviews();
?>
<section class="section reviews" id="reviews" aria-labelledby="reviews-title">
  <div class="container">
    <div class="section-head">
      <div class="section-head__title">
        <?php if (s($s['eyebrow'] ?? '') !== ''): ?>
          <p class="eyebrow reveal"><?= e($s['eyebrow']) ?></p>
        <?php endif; ?>
        <h2 class="display h-xl reveal" id="reviews-title" style="--d:1"><?= Text::heading(s($s['heading'] ?? '')) ?></h2>
      </div>
    </div>

    <ul class="reviews__list reviews__list--<?= min(3, count($reviews)) ?>" role="list">
      <?php foreach ($reviews as $i => $review): ?>
        <li class="review reveal" style="--d:<?= $i % 3 ?>">
          <figure>
            <?= Html::icon('quote', 'review__mark') ?>
            <blockquote class="review__quote"><?= Text::paragraphs(s($review['quote'])) ?></blockquote>
            <figcaption class="review__by">
              <span class="review__name"><?= e($review['name']) ?></span>
              <?php if (s($review['source'] ?? '') !== ''): ?>
                <span class="review__source">
                  <?php if (s($review['link'] ?? '') !== ''): ?>
                    <a href="<?= e($review['link']) ?>" target="_blank" rel="noopener"><?= e($review['source']) ?></a>
                  <?php else: ?>
                    <?= e($review['source']) ?>
                  <?php endif; ?>
                </span>
              <?php endif; ?>
            </figcaption>
          </figure>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
