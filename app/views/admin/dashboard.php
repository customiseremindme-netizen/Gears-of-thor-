<?php
defined('GOT_APP') || exit;

use Got\Auth;
use Got\Enquiries;

/** @var array $counts */
/** @var array $recent */
/** @var ?array $published */
/** @var array $checklist */
/** @var ?array $progress */
/** @var string $firstName */
/** @var bool $hasChanges */

$todo = array_values(array_filter($checklist, static fn ($i) => !$i['done'] && $i['level'] !== 'optional'));
?>
<div class="page-head">
  <h1>Hello, <?= e($firstName) ?></h1>
  <p>Here’s what’s happening with your website.</p>
</div>

<div class="cards cards--3">
  <a class="stat" href="<?= e(url('/admin/enquiries?status=new')) ?>">
    <span class="stat__label">New enquiries</span>
    <span class="stat__value"><?= (int) $counts['new'] ?></span>
    <span class="stat__hint">Waiting for a call back</span>
  </a>
  <a class="stat" href="<?= e(url('/admin/enquiries?status=follow_up')) ?>">
    <span class="stat__label">Follow-up</span>
    <span class="stat__value"><?= (int) $counts['follow_up'] ?></span>
    <span class="stat__hint">Need another call</span>
  </a>
  <a class="stat" href="<?= e(url('/admin/enquiries?status=joined')) ?>">
    <span class="stat__label">Joined</span>
    <span class="stat__value"><?= (int) $counts['joined'] ?></span>
    <span class="stat__hint">From <?= (int) $counts['all'] ?> enquiries in total</span>
  </a>
</div>

<div class="grid-2">
  <section class="card">
    <div class="card__head">
      <h2>Newest enquiries</h2>
      <a href="<?= e(url('/admin/enquiries')) ?>">See all</a>
    </div>
    <?php if (!$recent): ?>
      <p class="muted">No new enquiries right now. New ones from the website form appear here.</p>
    <?php else: ?>
      <ul class="mini-list">
        <?php foreach ($recent as $row): ?>
          <li>
            <a href="<?= e(url('/admin/enquiries/' . (int) $row['id'])) ?>">
              <strong><?= e($row['name']) ?></strong>
              <span><?= e($row['goal']) ?> · <?= e($row['contact_time']) ?></span>
            </a>
            <span class="mini-list__meta"><?= e(time_ago($row['created_at'])) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <?php if (Auth::can('content')): ?>
  <section class="card">
    <div class="card__head">
      <h2>Your website</h2>
      <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener">Open ↗</a>
    </div>
    <?php if ($hasChanges): ?>
      <p><strong>You have unpublished changes.</strong> Visitors still see the previous version.</p>
      <p><a class="btn btn--primary" href="<?= e(url('/admin/preview')) ?>">Preview and publish</a></p>
    <?php else: ?>
      <p>Everything you have saved is live.</p>
    <?php endif; ?>
    <?php if ($published): ?>
      <p class="muted small">Last published <?= e(ist($published['updated_at'])) ?><?= $published['user_name'] ? ' by ' . e($published['user_name']) : '' ?>.</p>
    <?php endif; ?>
    <div class="quick-links">
      <a href="<?= e(url('/admin/content/hero')) ?>">Edit the hero</a>
      <a href="<?= e(url('/admin/content/gym')) ?>">Add gym photos</a>
      <a href="<?= e(url('/admin/content/business')) ?>">Opening hours</a>
      <a href="<?= e(url('/admin/content/training')) ?>">Training options</a>
      <a href="<?= e(url('/admin/content/membership')) ?>">Membership</a>
      <a href="<?= e(url('/admin/content/faq')) ?>">FAQs</a>
    </div>
  </section>
  <?php endif; ?>
</div>

<?php if ($progress): ?>
<section class="card">
  <div class="card__head">
    <h2>Owner checklist</h2>
    <a href="<?= e(url('/admin/checklist')) ?>">Open checklist</a>
  </div>
  <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="<?= (int) $progress['total'] ?>" aria-valuenow="<?= (int) $progress['done'] ?>" aria-label="Checklist progress">
    <span style="width: <?= $progress['total'] ? round($progress['done'] / $progress['total'] * 100) : 0 ?>%"></span>
  </div>
  <p class="muted small"><?= (int) $progress['done'] ?> of <?= (int) $progress['total'] ?> launch items done.</p>
  <?php if ($todo): ?>
    <ul class="todo-list">
      <?php foreach (array_slice($todo, 0, 4) as $item): ?>
        <li>
          <span class="todo-list__dot todo-list__dot--<?= e($item['level']) ?>" aria-hidden="true"></span>
          <span><?= e($item['title']) ?></span>
          <a href="<?= e(url($item['link'])) ?>"><?= e($item['action']) ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
<?php endif; ?>
