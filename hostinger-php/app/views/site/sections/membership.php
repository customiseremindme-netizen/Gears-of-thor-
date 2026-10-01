<?php
defined('GOT_APP') || exit;

use Got\Html;
use Got\Text;

/** @var Got\Site $site */

$m = $site->c['membership'];
$image = $site->media($m['image'] ?? null);
$plans = $site->plans();
?>
<section class="section membership" id="membership" aria-labelledby="membership-title">
  <div class="container">
    <div class="membership__panel<?= $image ? ' membership__panel--image' : '' ?> reveal">
      <?php if ($image): ?>
        <div class="membership__bg" aria-hidden="true"><?= Html::picture($image, '100vw', '') ?></div>
      <?php else: ?>
        <span class="membership__watermark" aria-hidden="true"><?= e($site->str('brand.name')) ?></span>
      <?php endif; ?>
      <span class="corner corner--tl" aria-hidden="true"></span>
      <span class="corner corner--br" aria-hidden="true"></span>

      <div class="membership__content">
        <?php if (s($m['eyebrow'] ?? '') !== ''): ?>
          <p class="eyebrow"><?= e($m['eyebrow']) ?></p>
        <?php endif; ?>
        <h2 class="display h-xl" id="membership-title"><?= Text::heading(s($m['heading'] ?? '')) ?></h2>
      </div>
      <div class="membership__aside">
        <?php if (s($m['text'] ?? '') !== ''): ?>
          <div class="lead"><?= Text::paragraphs(s($m['text']), $site->tokens) ?></div>
        <?php endif; ?>
        <div class="membership__actions">
          <?php if (s($m['cta_label'] ?? '') !== ''): ?>
            <a class="btn btn--accent btn--lg" href="#enquire"><span><?= e($m['cta_label']) ?></span><?= Html::icon('arrow') ?></a>
          <?php endif; ?>
          <?php if ($site->phone() !== ''): ?>
            <a class="link-arrow" href="<?= e($site->phoneHref()) ?>"><?= Html::icon('phone') ?><span><?= e($site->phone()) ?></span></a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if ($plans): ?>
      <ul class="plans plans--<?= min(4, count($plans)) ?>" role="list">
        <?php foreach ($plans as $i => $plan):
            $features = Text::lines(s($plan['features'] ?? ''));
        ?>
          <li class="plan<?= !empty($plan['featured']) ? ' plan--featured' : '' ?> reveal" style="--d:<?= $i % 4 ?>">
            <h3 class="plan__name"><?= e($plan['name']) ?></h3>
            <?php if (s($plan['price'] ?? '') !== ''): ?>
              <p class="plan__price"><span class="plan__amount"><?= e($plan['price']) ?></span>
                <?php if (s($plan['period'] ?? '') !== ''): ?><span class="plan__period"><?= e($plan['period']) ?></span><?php endif; ?>
              </p>
            <?php endif; ?>
            <?php if ($features): ?>
              <ul class="plan__features">
                <?php foreach ($features as $feature): ?>
                  <li><?= Html::icon('check') ?><span><?= e($feature) ?></span></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <?php if (s($m['plan_cta_label'] ?? '') !== ''): ?>
              <a class="btn <?= !empty($plan['featured']) ? 'btn--accent' : 'btn--outline' ?> btn--block" href="#enquire" data-enquire-plan="<?= e($plan['name']) ?>"><span><?= e($m['plan_cta_label']) ?></span></a>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php if (s($m['plans_note'] ?? '') !== ''): ?>
        <p class="plans__note"><?= e($m['plans_note']) ?></p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
