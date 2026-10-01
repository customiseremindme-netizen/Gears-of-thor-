<?php
defined('GOT_APP') || exit;

use Got\Hours;
use Got\Html;
use Got\Media;

/** @var Got\Site $site */
/** @var string $page */

$isHome = $page === 'home';
$prefix = $isHome ? '' : url('/');
$logo = $site->logo();
$nav = $site->nav();
$homeHref = $isHome ? '#top' : url('/');
$enquireHref = $prefix . '#enquire';
$today = $site->hoursConfirmed() ? Hours::today($site->hoursDays()) : null;
?>
<header class="site-header<?= $isHome ? '' : ' is-solid is-static' ?>" data-header>
  <div class="site-header__inner">
    <a class="brand" href="<?= e($homeHref) ?>">
      <?php if ($logo): ?>
        <img class="brand__logo" src="<?= e(Media::urlFor($logo, 480)) ?>" width="<?= (int) $logo['width'] ?>" height="<?= (int) $logo['height'] ?>" alt="<?= e($site->str('brand.logo_alt') ?: $site->str('brand.name')) ?>">
      <?php else: ?>
        <span class="brand__wordmark"><?= e($site->str('brand.name')) ?></span>
      <?php endif; ?>
    </a>

    <?php if ($nav): ?>
    <nav class="site-nav" aria-label="Main">
      <ul>
        <?php foreach ($nav as $link): ?>
          <li><a href="<?= e($prefix . $link['href']) ?>" data-nav-link><?= e($link['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <?php endif; ?>

    <a class="btn btn--accent btn--sm site-header__cta" href="<?= e($enquireHref) ?>"><?= e($site->str('nav.cta')) ?></a>

    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu" data-menu-toggle>
      <span class="menu-toggle__bars" aria-hidden="true"><span></span><span></span></span>
      <span class="menu-toggle__label">Menu</span>
    </button>
  </div>
</header>

<div class="mobile-menu" id="mobile-menu" data-menu inert>
  <div class="mobile-menu__inner">
    <nav aria-label="Mobile">
      <ul class="mobile-menu__links">
        <?php foreach ($nav as $i => $link): ?>
          <li style="--i:<?= $i ?>"><a href="<?= e($prefix . $link['href']) ?>" data-menu-link><?= e($link['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="mobile-menu__info">
      <?php if ($site->addressLines()): ?>
        <a class="mobile-menu__row" href="<?= e($site->directionsUrl()) ?>" target="_blank" rel="noopener">
          <?= Html::icon('pin') ?>
          <span><?= e(implode(', ', $site->addressLines())) ?></span>
        </a>
      <?php endif; ?>
      <?php if ($today): ?>
        <a class="mobile-menu__row" href="<?= e($prefix) ?>#contact" data-menu-link>
          <?= Html::icon('clock') ?>
          <span>Today: <?= e($today['text']) ?></span>
        </a>
      <?php endif; ?>
      <?php if ($site->phone() !== ''): ?>
        <a class="mobile-menu__row" href="<?= e($site->phoneHref()) ?>">
          <?= Html::icon('phone') ?>
          <span><?= e($site->phone()) ?></span>
        </a>
      <?php endif; ?>
    </div>
    <a class="btn btn--accent btn--lg btn--block" href="<?= e($enquireHref) ?>" data-menu-link><?= e($site->str('nav.cta')) ?></a>
  </div>
</div>
