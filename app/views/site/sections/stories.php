<?php
defined('GOT_APP') || exit;

use Got\Html;
use Got\Text;

/** @var Got\Site $site */

$s = $site->c['stories'];
$stories = $site->stories();
?>
<section class="section stories" id="stories" aria-labelledby="stories-title">
  <div class="container">
    <div class="section-head">
      <div class="section-head__title">
        <?php if (s($s['eyebrow'] ?? '') !== ''): ?>
          <p class="eyebrow reveal"><?= e($s['eyebrow']) ?></p>
        <?php endif; ?>
        <h2 class="display h-xl reveal" id="stories-title" style="--d:1"><?= Text::heading(s($s['heading'] ?? '')) ?></h2>
      </div>
      <?php if (s($s['intro'] ?? '') !== ''): ?>
        <div class="section-head__intro lead reveal" style="--d:2"><?= Text::paragraphs(s($s['intro']), $site->tokens) ?></div>
      <?php endif; ?>
    </div>

    <div class="stories__list">
      <?php foreach ($stories as $story):
          $before = $site->media($story['before'] ?? null);
          $after = $site->media($story['after'] ?? null);
          $name = s($story['name']);
      ?>
        <article class="story reveal">
          <?php if ($before || $after): ?>
            <div class="story__photos<?= $before && $after ? ' story__photos--pair' : '' ?>">
              <?php if ($before): ?>
                <figure class="story__photo">
                  <?= Html::picture($before, '(min-width: 900px) 25vw, 50vw', $name . ' — before') ?>
                  <figcaption>Before</figcaption>
                </figure>
              <?php endif; ?>
              <?php if ($after): ?>
                <figure class="story__photo">
                  <?= Html::picture($after, '(min-width: 900px) 25vw, 50vw', $name . ' — after') ?>
                  <figcaption>After</figcaption>
                </figure>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <div class="story__body">
            <?php if (s($story['title'] ?? '') !== ''): ?>
              <h3 class="story__title"><?= e($story['title']) ?></h3>
            <?php endif; ?>
            <?php if (s($story['text'] ?? '') !== ''): ?>
              <div class="story__text"><?= Text::paragraphs(s($story['text'])) ?></div>
            <?php endif; ?>
            <p class="story__name"><?= e($name) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?php if (s($s['disclaimer'] ?? '') !== ''): ?>
      <p class="stories__note"><?= e($s['disclaimer']) ?></p>
    <?php endif; ?>
  </div>
</section>
