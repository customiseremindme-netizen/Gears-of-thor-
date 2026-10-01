<?php
defined('GOT_APP') || exit;

/** @var string $html */
/** @var array $toc */
?>
<div class="page-head">
  <h1>How-to guide</h1>
  <p>Everyday tasks, step by step. This guide is also in the project files as docs/OWNER-GUIDE.md.</p>
</div>
<div class="help-layout">
  <?php if ($toc): ?>
    <nav class="help-toc" aria-label="Guide contents">
      <p class="sidebar__label">Contents</p>
      <ol>
        <?php foreach ($toc as $heading): ?>
          <li><a href="#<?= e($heading['id']) ?>"><?= e($heading['text']) ?></a></li>
        <?php endforeach; ?>
      </ol>
    </nav>
  <?php endif; ?>
  <article class="card prose-admin">
    <?= $html ?>
  </article>
</div>
