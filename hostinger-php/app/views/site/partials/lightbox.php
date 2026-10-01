<?php
defined('GOT_APP') || exit;

use Got\Html;
?>
<dialog class="lightbox" data-lightbox aria-label="Photo viewer">
  <div class="lightbox__frame">
    <p class="lightbox__count" data-lightbox-count aria-live="polite"></p>
    <button class="lightbox__btn lightbox__close" type="button" data-lightbox-close aria-label="Close photo viewer"><?= Html::icon('close') ?></button>
    <figure class="lightbox__figure">
      <img class="lightbox__img" data-lightbox-img alt="">
      <figcaption class="lightbox__caption" data-lightbox-caption></figcaption>
    </figure>
    <button class="lightbox__btn lightbox__prev" type="button" data-lightbox-prev aria-label="Previous photo"><?= Html::icon('chevron-left') ?></button>
    <button class="lightbox__btn lightbox__next" type="button" data-lightbox-next aria-label="Next photo"><?= Html::icon('chevron-right') ?></button>
  </div>
</dialog>
