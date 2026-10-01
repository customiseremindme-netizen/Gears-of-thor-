<?php
defined('GOT_APP') || exit;

use Got\Text;

/** @var Got\Site $site */

$s = $site->c['faq'];
$items = $site->faqItems();
?>
<section class="section faq" id="faq" aria-labelledby="faq-title">
  <div class="container faq__grid">
    <div class="faq__head">
      <?php if (s($s['eyebrow'] ?? '') !== ''): ?>
        <p class="eyebrow reveal"><?= e($s['eyebrow']) ?></p>
      <?php endif; ?>
      <h2 class="display h-lg reveal" id="faq-title" style="--d:1"><?= Text::heading(s($s['heading'] ?? '')) ?></h2>
    </div>
    <div class="faq__list reveal" style="--d:1">
      <?php foreach ($items as $i => $item): ?>
        <details class="faq-item"<?= $i === 0 ? ' open' : '' ?>>
          <summary>
            <span class="faq-item__q"><?= e($item['q']) ?></span>
            <span class="faq-item__icon" aria-hidden="true"></span>
          </summary>
          <div class="faq-item__a"><?= Text::paragraphs(s($item['a']), $site->tokens) ?></div>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
