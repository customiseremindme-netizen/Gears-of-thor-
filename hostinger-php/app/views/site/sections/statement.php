<?php
defined('GOT_APP') || exit;

use Got\Html;
use Got\Text;

/** @var Got\Site $site */

$s = $site->c['statement'];
$image = $site->media($s['image'] ?? null);
$detail = s($s['detail'] ?? '');
$typeWords = Text::lines(str_replace(' ', "\n", $site->str('brand.expanded') ?: $site->str('brand.name')));
?>
<section class="section statement<?= $image ? ' statement--image' : ' statement--type' ?>" id="about" aria-labelledby="about-title">
  <div class="container statement__grid">
    <?php if ($image): ?>
      <div class="statement__media reveal-img" data-parallax-frame>
        <div class="statement__media-inner" data-parallax="0.08">
          <?= Html::picture($image, '(min-width: 900px) 55vw, 100vw') ?>
        </div>
        <?php if ($detail !== ''): ?>
          <span class="statement__tag" aria-hidden="true"><?= e($detail) ?></span>
        <?php endif; ?>
      </div>
    <?php elseif ($typeWords): ?>
      <div class="statement__type" aria-hidden="true">
        <?php foreach ($typeWords as $i => $word): ?>
          <span class="statement__type-word reveal" style="--d:<?= $i ?>"><?= e($word) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="statement__body">
      <?php if (s($s['eyebrow'] ?? '') !== ''): ?>
        <p class="eyebrow reveal"><?= e($s['eyebrow']) ?></p>
      <?php endif; ?>
      <h2 class="display h-xl reveal" id="about-title" style="--d:1"><?= Text::heading(s($s['heading'] ?? '')) ?></h2>
      <?php if (s($s['text'] ?? '') !== ''): ?>
        <div class="lead reveal" style="--d:2"><?= Text::paragraphs(s($s['text']), $site->tokens) ?></div>
      <?php endif; ?>
      <?php if ($detail !== ''): ?>
        <p class="signature reveal" style="--d:3"><span class="signature__rule" aria-hidden="true"></span><?= e($detail) ?></p>
      <?php endif; ?>
    </div>
  </div>
</section>
