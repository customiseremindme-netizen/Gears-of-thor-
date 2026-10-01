<?php
defined('GOT_APP') || exit;

use Got\Markdown;

/** @var Got\Site $site */
$row = \Got\Content::row($site->preview ? 'draft' : 'published');
?>
<section class="page-simple">
  <div class="container container--narrow">
    <p class="eyebrow"><?= e($site->str('brand.name')) ?></p>
    <h1 class="display h-xl"><?= e($site->str('privacy.title')) ?></h1>
    <?php if ($row): ?>
      <p class="page-simple__meta">Last updated <?= e(ist($row['updated_at'], 'j F Y')) ?></p>
    <?php endif; ?>
    <div class="prose">
      <?= Markdown::render($site->str('privacy.body'), $site->tokens) ?>
    </div>
  </div>
</section>
