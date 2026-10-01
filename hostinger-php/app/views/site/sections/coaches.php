<?php
defined('GOT_APP') || exit;

use Got\Html;
use Got\Text;

/** @var Got\Site $site */

$s = $site->c['coaches'];
$coaches = $site->coaches();
?>
<section class="section coaches" id="coaches" aria-labelledby="coaches-title">
  <div class="container">
    <div class="section-head">
      <div class="section-head__title">
        <?php if (s($s['eyebrow'] ?? '') !== ''): ?>
          <p class="eyebrow reveal"><?= e($s['eyebrow']) ?></p>
        <?php endif; ?>
        <h2 class="display h-xl reveal" id="coaches-title" style="--d:1"><?= Text::heading(s($s['heading'] ?? '')) ?></h2>
      </div>
      <?php if (s($s['intro'] ?? '') !== ''): ?>
        <div class="section-head__intro lead reveal" style="--d:2"><?= Text::paragraphs(s($s['intro']), $site->tokens) ?></div>
      <?php endif; ?>
    </div>

    <ul class="coaches__list" role="list">
      <?php foreach ($coaches as $i => $coach):
          $photo = $site->media($coach['photo'] ?? null);
          $qualifications = !empty($coach['verified']) ? Text::lines(s($coach['qualifications'] ?? '')) : [];
      ?>
        <li class="coach reveal" style="--d:<?= $i % 4 ?>">
          <div class="coach__photo<?= $photo ? '' : ' coach__photo--empty' ?>">
            <?php if ($photo): ?>
              <?= Html::picture($photo, '(min-width: 900px) 25vw, 50vw', 'Photo of ' . s($coach['name'])) ?>
            <?php else: ?>
              <span aria-hidden="true"><?= e(mb_substr(s($coach['name']), 0, 1)) ?></span>
            <?php endif; ?>
          </div>
          <h3 class="coach__name"><?= e($coach['name']) ?></h3>
          <?php if (s($coach['role'] ?? '') !== ''): ?>
            <p class="coach__role"><?= e($coach['role']) ?></p>
          <?php endif; ?>
          <?php if ($qualifications): ?>
            <ul class="coach__quals">
              <?php foreach ($qualifications as $q): ?>
                <li><?= e($q) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
