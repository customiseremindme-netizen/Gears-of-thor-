<?php
defined('GOT_APP') || exit;

use Got\Auth;
use Got\Checklist;
use Got\Csrf;
use Got\Settings;

/** @var array $items */
/** @var array $progress */

$groups = [
    'required' => ['Before launch', 'Confirm these so the website only shows accurate information.'],
    'recommended' => ['Recommended', 'These make the website feel like your gym.'],
    'optional' => ['When you have real information', 'Each of these stays hidden on the website until you add it.'],
];
?>
<div class="page-head">
  <h1>Owner checklist</h1>
  <p>Business details still to confirm and content still to add. Nothing unconfirmed is shown to visitors.</p>
</div>

<div class="progress progress--lg" role="progressbar" aria-valuemin="0" aria-valuemax="<?= (int) $progress['total'] ?>" aria-valuenow="<?= (int) $progress['done'] ?>" aria-label="Checklist progress">
  <span style="width: <?= $progress['total'] ? round($progress['done'] / $progress['total'] * 100) : 0 ?>%"></span>
</div>
<p class="muted small"><?= (int) $progress['done'] ?> of <?= (int) $progress['total'] ?> launch and recommended items done.</p>

<?php foreach ($groups as $level => [$heading, $intro]): ?>
  <section class="card">
    <h2><?= e($heading) ?></h2>
    <p class="card__intro"><?= e($intro) ?></p>
    <ul class="checklist">
      <?php foreach ($items as $item):
          if ($item['level'] !== $level || (!empty($item['owner']) && !Auth::can('settings'))) {
              continue;
          }
      ?>
        <li class="checklist__item<?= $item['done'] ? ' is-done' : '' ?>">
          <span class="checklist__icon" aria-hidden="true"><?= $item['done'] ? '✓' : '' ?></span>
          <div class="checklist__text">
            <strong><?= e($item['title']) ?></strong><span class="visually-hidden"><?= $item['done'] ? ' (done)' : ' (to do)' ?></span>
            <span><?= e($item['detail']) ?></span>
          </div>
          <div class="checklist__actions">
            <a class="btn btn--light btn--sm" href="<?= e(url($item['link'])) ?>"><?= e($item['action']) ?></a>
            <?php if (!empty($item['tick'])):
                $ticked = (bool) Settings::get($item['tick'], false);
                if ($ticked || !$item['done']):
                    $label = $ticked ? 'Undo' : (str_ends_with($item['tick'], '_skip') ? Checklist::MANUAL[$item['tick']] : 'Mark as done');
            ?>
              <form method="post" action="<?= e(url('/admin/checklist')) ?>">
                <?= Csrf::field() ?>
                <input type="hidden" name="key" value="<?= e($item['tick']) ?>">
                <input type="hidden" name="done" value="<?= $ticked ? '0' : '1' ?>">
                <button class="btn-link" type="submit"><?= e($label) ?></button>
              </form>
            <?php endif; endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endforeach; ?>
