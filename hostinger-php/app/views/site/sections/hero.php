<?php
defined('GOT_APP') || exit;

use Got\Hours;
use Got\Html;
use Got\Media;
use Got\Text;

/** @var Got\Site $site */

$h = $site->c['hero'];
$image = $site->media($h['image'] ?? null);
$mobileImage = $site->media($h['mobile_image'] ?? null);
$video = $site->media($h['video'] ?? null, 'video');
$hasMedia = $image !== null || $video !== null;
$logo = $site->logo();
$overlay = in_array($h['overlay'] ?? '', ['medium', 'strong', 'strongest'], true) ? $h['overlay'] : 'strong';
$showGymLink = $site->isVisible('gym') && s($h['secondary_label'] ?? '') !== '';
$today = $site->hoursConfirmed() ? Hours::today($site->hoursDays()) : null;
$next = null;
foreach ($site->sections as $section) {
    if ($section['visible'] && $section['id'] !== 'hero' && $section['id'] !== 'strip') {
        $next = \Got\Schema::SECTIONS[$section['id']]['anchor'];
        break;
    }
}
?>
<section class="hero <?= $hasMedia ? 'hero--media' : 'hero--brand' ?> hero--overlay-<?= e($overlay) ?>" id="top" aria-labelledby="hero-title" data-hero>
  <?php if ($hasMedia): ?>
    <div class="hero__media" aria-hidden="true">
      <?php if ($image): ?>
        <picture>
          <?php if ($mobileImage): ?>
            <source media="(max-width: 699px)" type="<?= e(Media::variantMime($mobileImage)) ?>" srcset="<?= e(Media::srcset($mobileImage)) ?>" sizes="100vw">
          <?php endif; ?>
          <source type="<?= e(Media::variantMime($image)) ?>" srcset="<?= e(Media::srcset($image)) ?>" sizes="100vw">
          <img src="<?= e(Media::fileUrl($image)) ?>" width="<?= (int) $image['width'] ?>" height="<?= (int) $image['height'] ?>" alt="" fetchpriority="high" decoding="async">
        </picture>
      <?php endif; ?>
      <?php if ($video): ?>
        <video class="hero__video" muted loop playsinline preload="none" data-hero-video data-src="<?= e(Media::fileUrl($video)) ?>"<?= $image ? ' poster="' . e(Media::urlFor($image, 1440)) . '"' : '' ?>></video>
      <?php endif; ?>
    </div>
    <div class="hero__shade" aria-hidden="true"></div>
  <?php else: ?>
    <div class="hero__brand" aria-hidden="true">
      <div class="hero__slab"></div>
      <div class="hero__rule"></div>
      <?php if ($logo): ?>
        <div class="hero__emblem" data-parallax="-0.06">
          <img src="<?= e(Media::urlFor($logo, 960)) ?>" srcset="<?= e(Media::srcset($logo)) ?>" sizes="(min-width: 900px) 46vw, 72vw" width="<?= (int) $logo['width'] ?>" height="<?= (int) $logo['height'] ?>" alt="" fetchpriority="high">
        </div>
      <?php endif; ?>
      <p class="hero__vertical"><?= e($site->str('brand.expanded')) ?></p>
    </div>
  <?php endif; ?>

  <div class="hero__inner container">
    <?php if (s($h['eyebrow'] ?? '') !== ''): ?>
      <p class="eyebrow hero__eyebrow" data-hero-item><?= e($h['eyebrow']) ?></p>
    <?php endif; ?>
    <h1 class="hero__title display" id="hero-title"><?= Text::animatedHeading(s($h['headline'] ?? '')) ?></h1>
    <?php if (s($h['text'] ?? '') !== ''): ?>
      <p class="hero__text" data-hero-item><?= e($h['text']) ?></p>
    <?php endif; ?>
    <div class="hero__actions" data-hero-item>
      <a class="btn btn--accent btn--lg" href="#enquire"><span><?= e($h['primary_label'] ?? '') ?></span><?= Html::icon('arrow') ?></a>
      <?php if ($showGymLink): ?>
        <a class="btn btn--ghost btn--lg" href="#gym"><span><?= e($h['secondary_label']) ?></span></a>
      <?php endif; ?>
    </div>
  </div>

  <div class="hero__bar" data-hero-item>
    <div class="container hero__bar-inner">
      <?php if ($site->addressLines()): ?>
        <a class="hero__fact" href="<?= e($site->directionsUrl()) ?>" target="_blank" rel="noopener">
          <span class="hero__fact-label"><?= Html::icon('pin') ?><?= e($site->str('contact.address_label') ?: 'Address') ?></span>
          <span class="hero__fact-value"><?= e($site->str('business.street')) ?>, <?= e($site->str('business.locality')) ?></span>
        </a>
      <?php endif; ?>
      <?php if ($today): ?>
        <a class="hero__fact" href="#contact" data-open-status data-hours='<?= e(json_encode($site->hoursDays())) ?>'>
          <span class="hero__fact-label"><?= Html::icon('clock') ?>Today</span>
          <span class="hero__fact-value" data-today-text><?= e($today['text']) ?></span>
        </a>
      <?php endif; ?>
      <?php if ($site->phone() !== ''): ?>
        <a class="hero__fact" href="<?= e($site->phoneHref()) ?>">
          <span class="hero__fact-label"><?= Html::icon('phone') ?><?= e($site->str('contact.phone_label') ?: 'Phone') ?></span>
          <span class="hero__fact-value"><?= e($site->phone()) ?></span>
        </a>
      <?php endif; ?>
      <?php if ($next && s($h['scroll_label'] ?? '') !== ''): ?>
        <a class="scroll-cue" href="#<?= e($next) ?>"><span><?= e($h['scroll_label']) ?></span><span class="scroll-cue__line" aria-hidden="true"></span></a>
      <?php endif; ?>
    </div>
  </div>
</section>
