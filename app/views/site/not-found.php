<?php
defined('GOT_APP') || exit;

use Got\Html;

/** @var Got\Site $site */
?>
<section class="page-simple page-simple--center">
  <div class="container container--narrow">
    <p class="eyebrow">404</p>
    <h1 class="display h-xl">Wrong <span class="hl">turn.</span></h1>
    <p class="lead">That page doesn’t exist. Let’s get you back to the gym.</p>
    <a class="btn btn--accent btn--lg" href="<?= e(url('/')) ?>"><span>Back to home</span><?= Html::icon('arrow') ?></a>
  </div>
</section>
