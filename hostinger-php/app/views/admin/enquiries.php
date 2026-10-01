<?php
defined('GOT_APP') || exit;

use Got\Auth;
use Got\Csrf;
use Got\Enquiries;
use Got\Text;

/** @var array $rows */
/** @var int $total */
/** @var int $page */
/** @var int $pages */
/** @var array $filters */
/** @var array $counts */
/** @var string $returnTo */

$isSpam = $filters['view'] === 'spam';
$query = static function (array $changes) use ($filters): string {
    $params = array_filter(array_merge($filters, $changes), static fn ($v) => $v !== '' && $v !== null);
    return url('/admin/enquiries') . ($params ? '?' . http_build_query($params) : '');
};
$tabs = ['' => 'All'] + Enquiries::STATUSES;
?>
<div class="page-head page-head--row">
  <div>
    <h1>Enquiries</h1>
    <p>Everyone who sent the callback form. Update the status as you follow up.</p>
  </div>
  <?php if (Auth::can('enquiries.export')): ?>
    <a class="btn btn--light" href="<?= e(url('/admin/enquiries/export') . '?' . http_build_query(array_filter($filters))) ?>">Download CSV</a>
  <?php endif; ?>
</div>

<nav class="tabs" aria-label="Filter by status">
  <?php foreach ($tabs as $key => $label):
      $active = !$isSpam && $filters['status'] === $key;
      $count = $key === '' ? $counts['all'] : $counts[$key];
  ?>
    <a href="<?= e($query(['status' => $key, 'view' => '', 'page' => ''])) ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= e($label) ?> <span><?= (int) $count ?></span></a>
  <?php endforeach; ?>
  <a href="<?= e($query(['status' => '', 'view' => 'spam', 'page' => ''])) ?>"<?= $isSpam ? ' aria-current="page"' : '' ?> class="tabs__spam">Spam <span><?= (int) $counts['spam'] ?></span></a>
</nav>

<form class="filters" method="get" action="<?= e(url('/admin/enquiries')) ?>">
  <?php if ($filters['status'] !== ''): ?><input type="hidden" name="status" value="<?= e($filters['status']) ?>"><?php endif; ?>
  <?php if ($isSpam): ?><input type="hidden" name="view" value="spam"><?php endif; ?>
  <div class="fld fld--inline">
    <label for="f-q">Search</label>
    <input id="f-q" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Name, mobile or note">
  </div>
  <div class="fld fld--inline">
    <label for="f-from">From</label>
    <input id="f-from" type="date" name="from" value="<?= e($filters['from']) ?>">
  </div>
  <div class="fld fld--inline">
    <label for="f-to">To</label>
    <input id="f-to" type="date" name="to" value="<?= e($filters['to']) ?>">
  </div>
  <button class="btn btn--light" type="submit">Apply</button>
  <?php if ($filters['q'] !== '' || $filters['from'] !== '' || $filters['to'] !== ''): ?>
    <a class="btn-link" href="<?= e($query(['q' => '', 'from' => '', 'to' => '', 'page' => ''])) ?>">Clear</a>
  <?php endif; ?>
</form>

<?php if ($isSpam): ?>
  <div class="notice">
    <p>These were filtered automatically (for example the form was sent by a robot, or contained many links). Open one and choose “Not spam” if it is genuine. Spam older than 30 days is deleted automatically.</p>
    <?php if ($counts['spam'] > 0 && Auth::can('enquiries.delete')): ?>
      <form method="post" action="<?= e(url('/admin/enquiries/purge-spam')) ?>" data-confirm="Permanently delete all spam enquiries?">
        <?= Csrf::field() ?>
        <button class="btn btn--light btn--sm" type="submit">Delete all spam now</button>
      </form>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if (!$rows): ?>
  <div class="empty">
    <p><strong>No enquiries here yet.</strong></p>
    <p class="muted">When someone sends the “Request a Callback” form on your website, it appears here straight away.</p>
  </div>
<?php else: ?>
  <p class="muted small"><?= plural($total, 'enquiry', 'enquiries') ?></p>
  <div class="table-wrap">
    <table class="table table--enquiries">
      <thead>
        <tr>
          <th scope="col">Received</th>
          <th scope="col">Name</th>
          <th scope="col">Mobile</th>
          <th scope="col">Goal</th>
          <th scope="col">Best time</th>
          <th scope="col">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row):
            $detail = url('/admin/enquiries/' . (int) $row['id']) . '?return=' . rawurlencode($returnTo);
        ?>
          <tr class="status-row--<?= e($row['status']) ?>">
            <td data-label="Received"><span class="nowrap"><?= e(ist($row['created_at'], 'j M, g:i A')) ?></span><br><span class="muted small"><?= e(time_ago($row['created_at'])) ?></span></td>
            <td data-label="Name">
              <a class="strong-link" href="<?= e($detail) ?>"><?= e($row['name']) ?></a>
              <?php if (trim((string) $row['message']) !== ''): ?>
                <span class="table__excerpt"><?= e(mb_strimwidth((string) $row['message'], 0, 90, '…')) ?></span>
              <?php endif; ?>
            </td>
            <td data-label="Mobile">
              <a class="nowrap" href="<?= e(Text::telHref((string) $row['phone'])) ?>"><?= e($row['phone_display']) ?></a><br>
              <a class="small" href="<?= e(Text::whatsappHref((string) $row['phone'])) ?>" target="_blank" rel="noopener">WhatsApp</a>
            </td>
            <td data-label="Goal"><?= e($row['goal']) ?></td>
            <td data-label="Best time"><?= e($row['contact_time']) ?></td>
            <td data-label="Status">
              <?php if ($isSpam): ?>
                <a class="btn btn--light btn--sm" href="<?= e($detail) ?>">Review</a>
              <?php else: ?>
                <form method="post" action="<?= e(url('/admin/enquiries/' . (int) $row['id'] . '/status')) ?>" class="status-form" data-autosubmit>
                  <?= Csrf::field() ?>
                  <input type="hidden" name="return" value="<?= e($returnTo) ?>">
                  <label class="visually-hidden" for="st-<?= (int) $row['id'] ?>">Status for <?= e($row['name']) ?></label>
                  <select id="st-<?= (int) $row['id'] ?>" name="status" class="status-select status-select--<?= e($row['status']) ?>">
                    <?php foreach (Enquiries::STATUSES as $key => $label): ?>
                      <option value="<?= e($key) ?>"<?= $row['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn--light btn--sm" type="submit" data-autosubmit-button>Save</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="Pages">
      <?php if ($page > 1): ?><a href="<?= e($query(['page' => (string) ($page - 1)])) ?>">← Newer</a><?php endif; ?>
      <span>Page <?= $page ?> of <?= $pages ?></span>
      <?php if ($page < $pages): ?><a href="<?= e($query(['page' => (string) ($page + 1)])) ?>">Older →</a><?php endif; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>
