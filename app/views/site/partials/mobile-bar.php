<?php
defined('GOT_APP') || exit;

use Got\Html;

/** @var Got\Site $site */
?>
<nav class="mobile-bar" data-mobile-bar aria-label="Quick actions">
  <?php if ($site->phone() !== ''): ?>
    <a class="mobile-bar__btn" href="<?= e($site->phoneHref()) ?>"><?= Html::icon('phone') ?><span><?= e($site->str('nav.mobile_call')) ?></span></a>
  <?php endif; ?>
  <a class="mobile-bar__btn mobile-bar__btn--accent" href="#enquire"><span><?= e($site->str('nav.mobile_enquire')) ?></span><?= Html::icon('arrow') ?></a>
</nav>
