<?php
defined('GOT_APP') || exit;

use Got\Auth;
use Got\Csrf;
use Got\Enquiries;
use Got\Text;

/** @var array $enquiry */
/** @var array $events */
/** @var string $back */

$e = $enquiry;
$id = (int) $e['id'];
?>
<p class="back-link"><a href="<?= e(url($back)) ?>">← All enquiries</a></p>
<div class="page-head page-head--row">
  <div>
    <h1><?= e($e['name']) ?></h1>
    <p>Received <?= e(ist($e['created_at'])) ?> (<?= e(time_ago($e['created_at'])) ?>)</p>
  </div>
  <div class="actions">
    <a class="btn btn--primary" href="<?= e(Text::telHref((string) $e['phone'])) ?>">Call <?= e($e['phone_display']) ?></a>
    <a class="btn btn--light" href="<?= e(Text::whatsappHref((string) $e['phone'], 'Hi ' . $e['name'] . ', this is GOT FITNEZZ replying to your enquiry.')) ?>" target="_blank" rel="noopener">WhatsApp</a>
  </div>
</div>

<?php if ((int) $e['is_spam'] === 1): ?>
  <div class="notice notice--warn">
    <p><strong>Filtered as possible spam</strong><?= $e['spam_reason'] ? ' (' . e($e['spam_reason']) . ')' : '' ?>. If this is a real person, move it back to your enquiries.</p>
    <form method="post" action="<?= e(url('/admin/enquiries/' . $id . '/spam')) ?>">
      <?= Csrf::field() ?>
      <input type="hidden" name="spam" value="0">
      <button class="btn btn--light btn--sm" type="submit">Not spam</button>
    </form>
  </div>
<?php endif; ?>

<div class="grid-2 grid-2--wide-left">
  <section class="card">
    <h2>Details</h2>
    <dl class="details">
      <div><dt>Name</dt><dd><?= e($e['name']) ?></dd></div>
      <div><dt>Mobile</dt><dd><a href="<?= e(Text::telHref((string) $e['phone'])) ?>"><?= e($e['phone_display']) ?></a></dd></div>
      <div><dt>Fitness goal</dt><dd><?= e($e['goal']) ?></dd></div>
      <div><dt>Preferred contact time</dt><dd><?= e($e['contact_time']) ?></dd></div>
      <div><dt>Message</dt><dd><?= trim((string) $e['message']) !== '' ? nl2br(e($e['message'])) : '<span class="muted">No message</span>' ?></dd></div>
      <div><dt>Consent</dt><dd>Agreed on <?= e(ist($e['consent_at'])) ?>: “<?= e($e['consent_text']) ?>”</dd></div>
    </dl>
  </section>

  <section class="card">
    <h2>Follow-up</h2>
    <form method="post" action="<?= e(url('/admin/enquiries/' . $id)) ?>" class="stack">
      <?= Csrf::field() ?>
      <fieldset class="status-choice">
        <legend>Status</legend>
        <?php foreach (Enquiries::STATUSES as $key => $label): ?>
          <label class="status-choice__opt status-choice__opt--<?= e($key) ?>">
            <input type="radio" name="status" value="<?= e($key) ?>"<?= $e['status'] === $key ? ' checked' : '' ?>>
            <span><?= e($label) ?></span>
          </label>
        <?php endforeach; ?>
      </fieldset>
      <div class="fld">
        <label for="notes">Notes (only visible in the dashboard)</label>
        <textarea id="notes" name="notes" rows="5" maxlength="5000" placeholder="For example: Called on Monday, will visit on Saturday morning."><?= e((string) $e['notes']) ?></textarea>
      </div>
      <button class="btn btn--primary" type="submit">Save</button>
    </form>
  </section>
</div>

<section class="card">
  <h2>History</h2>
  <ol class="timeline">
    <?php foreach ($events as $event): ?>
      <li>
        <span class="timeline__time"><?= e(ist($event['created_at'])) ?></span>
        <span><?= e($event['detail']) ?><?= $event['user_name'] ? ' — ' . e($event['user_name']) : '' ?></span>
      </li>
    <?php endforeach; ?>
  </ol>
</section>

<div class="danger-zone">
  <?php if ((int) $e['is_spam'] === 0): ?>
    <form method="post" action="<?= e(url('/admin/enquiries/' . $id . '/spam')) ?>">
      <?= Csrf::field() ?>
      <input type="hidden" name="spam" value="1">
      <button class="btn-link" type="submit">Move to spam</button>
    </form>
  <?php endif; ?>
  <?php if (Auth::can('enquiries.delete')): ?>
    <form method="post" action="<?= e(url('/admin/enquiries/' . $id . '/delete')) ?>" data-confirm="Delete this enquiry permanently? Do this when the person asks you to remove their details.">
      <?= Csrf::field() ?>
      <button class="btn-link danger" type="submit">Delete permanently</button>
    </form>
  <?php endif; ?>
</div>
