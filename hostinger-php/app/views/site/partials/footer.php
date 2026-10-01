<?php
defined('GOT_APP') || exit;

use Got\Html;
use Got\Media;
use Got\Text;

/** @var Got\Site $site */
/** @var string $page */

$isHome = $page === 'home';
$prefix = $isHome ? '' : url('/');
$logo = $site->logo();
$closing = $site->str('footer.closing');
?>
<footer class="site-footer">
  <div class="container">
    <?php if ($closing !== ''): ?>
      <p class="footer-closing display reveal"><?= Text::heading($closing) ?></p>
    <?php endif; ?>

    <div class="footer-grid">
      <div class="footer-brand">
        <?php if ($logo): ?>
          <img class="footer-brand__logo" src="<?= e(Media::urlFor($logo, 480)) ?>" width="<?= (int) $logo['width'] ?>" height="<?= (int) $logo['height'] ?>" alt="<?= e($site->str('brand.logo_alt') ?: $site->str('brand.name')) ?>" loading="lazy" decoding="async">
        <?php else: ?>
          <span class="brand__wordmark"><?= e($site->str('brand.name')) ?></span>
        <?php endif; ?>
        <?php if ($site->str('brand.expanded') !== ''): ?>
          <p class="footer-brand__name"><?= e($site->str('brand.expanded')) ?></p>
        <?php endif; ?>
      </div>

      <?php if ($site->nav()): ?>
      <nav class="footer-col" aria-label="Footer">
        <h2 class="footer-col__title">Explore</h2>
        <ul>
          <?php foreach ($site->nav() as $link): ?>
            <li><a href="<?= e($prefix . $link['href']) ?>"><?= e($link['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <?php endif; ?>

      <div class="footer-col">
        <h2 class="footer-col__title"><?= e($site->str('contact.eyebrow') ?: 'Visit') ?></h2>
        <?php if ($site->addressLines()): ?>
          <address><?= implode('<br>', array_map('e', $site->addressLines())) ?></address>
        <?php endif; ?>
        <ul>
          <?php if ($site->phone() !== ''): ?>
            <li><a href="<?= e($site->phoneHref()) ?>"><?= e($site->phone()) ?></a></li>
          <?php endif; ?>
          <?php if ($site->str('business.email') !== ''): ?>
            <li><a href="mailto:<?= e($site->str('business.email')) ?>"><?= e($site->str('business.email')) ?></a></li>
          <?php endif; ?>
        </ul>
      </div>

      <?php if ($site->socials()): ?>
      <div class="footer-col">
        <h2 class="footer-col__title"><?= e($site->str('contact.social_label') ?: 'Follow') ?></h2>
        <ul class="footer-social">
          <?php foreach ($site->socials() as $social): ?>
            <li><a href="<?= e($social['url']) ?>" target="_blank" rel="noopener"><?= Html::icon($social['key']) ?><span><?= e($social['handle']) ?></span></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>

    <div class="footer-bottom">
      <p>© <?= date('Y') ?> <?= e($site->str('footer.copyright')) ?>. All rights reserved.</p>
      <ul>
        <li><a href="<?= e(url('/privacy')) ?>"><?= e($site->str('footer.privacy_label')) ?></a></li>
        <li><a href="<?= e($isHome ? '#top' : url('/')) ?>" class="footer-top"><?= $isHome ? 'Back to top' : 'Home' ?> <?= Html::icon($isHome ? 'arrow-up' : 'arrow') ?></a></li>
      </ul>
    </div>
  </div>
</footer>
