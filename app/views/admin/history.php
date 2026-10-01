<?php
defined('GOT_APP') || exit;

use Got\Csrf;

/** @var array $entries */
?>
<div class="page-head">
  <h1>Publish history</h1>
  <p>Every time you publish, a copy is kept. Restoring a version puts it into your draft so you can check it before publishing again.</p>
</div>

<?php if (!$entries): ?>
  <div class="empty"><p>Nothing has been published from the dashboard yet.</p></div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th scope="col">Published</th><th scope="col">By</th><th scope="col">What changed</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
      <tbody>
        <?php foreach ($entries as $i => $entry): ?>
          <tr>
            <td data-label="Published"><?= e(ist($entry['created_at'])) ?><?= $i === 0 ? ' <span class="pill pill--ok">Live now</span>' : '' ?></td>
            <td data-label="By"><?= e($entry['user_name'] ?? '—') ?></td>
            <td data-label="What changed"><?= e($entry['note'] ?? '') ?></td>
            <td data-label="">
              <?php if ($i > 0): ?>
                <form method="post" action="<?= e(url('/admin/history/' . (int) $entry['id'] . '/restore')) ?>" data-confirm="Copy this version into your draft? Your current unpublished changes will be replaced.">
                  <?= Csrf::field() ?>
                  <button class="btn btn--light btn--sm" type="submit">Restore to draft</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
