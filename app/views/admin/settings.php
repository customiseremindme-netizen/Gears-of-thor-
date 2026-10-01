<?php
defined('GOT_APP') || exit;

use Got\Csrf;

/** @var array $s */
?>
<div class="page-head">
  <h1>Settings</h1>
  <p>Only owners can see this page.</p>
</div>

<form method="post" action="<?= e(url('/admin/settings')) ?>" class="stack-lg" data-dirty-warn>
  <?= Csrf::field() ?>

  <section class="card">
    <h2>Website address</h2>
    <div class="fld">
      <label for="site_url">Your website address</label>
      <p class="fld__help" id="site_url-help">Used for Google, sharing previews and links in emails. Update it when you move from a temporary address to your real domain.</p>
      <input id="site_url" name="site_url" type="text" inputmode="url" value="<?= e($s['site_url']) ?>" aria-describedby="site_url-help">
    </div>
  </section>

  <section class="card">
    <h2>Email alerts for new enquiries</h2>
    <p class="card__intro">Optional and free. Enquiries are always saved in the dashboard, even if an email fails.</p>
    <div class="fld">
      <label class="switch" for="notify_enabled">
        <input type="checkbox" id="notify_enabled" name="notify_enabled" value="1"<?= $s['notify_enabled'] ? ' checked' : '' ?>>
        <span class="switch__track" aria-hidden="true"></span>
        <span class="switch__label">Email me when a new enquiry arrives</span>
      </label>
    </div>
    <div class="fld">
      <label for="notify_email">Send alerts to</label>
      <input id="notify_email" name="notify_email" type="email" value="<?= e($s['notify_email']) ?>" autocomplete="email">
    </div>
    <div class="fld">
      <label for="notify_from">Send from (optional)</label>
      <p class="fld__help" id="notify_from-help">An address on your own domain, such as website@yourdomain.com, reaches inboxes more reliably. Create it first in hPanel → Emails.</p>
      <input id="notify_from" name="notify_from" type="email" value="<?= e($s['notify_from']) ?>" aria-describedby="notify_from-help">
    </div>
  </section>

  <section class="card">
    <h2>Spam protection</h2>
    <p class="card__intro">Built-in protection is always on: a hidden trap field, a timing check, a limit on repeated sends and link filtering. Suspicious enquiries go to the Spam tab instead of being lost.</p>
    <div class="fld">
      <label class="switch" for="spam_auto_delete">
        <input type="checkbox" id="spam_auto_delete" name="spam_auto_delete" value="1"<?= $s['spam_auto_delete'] ? ' checked' : '' ?>>
        <span class="switch__track" aria-hidden="true"></span>
        <span class="switch__label">Automatically delete spam older than 30 days</span>
      </label>
    </div>
    <details class="details-box"<?= $s['turnstile_enabled'] ? ' open' : '' ?>>
      <summary>Extra protection with Cloudflare Turnstile (optional)</summary>
      <p class="muted small">Only needed if spam still gets through. Create a free Cloudflare account, add a Turnstile widget for your domain, then paste the two keys here.</p>
      <div class="fld">
        <label class="switch" for="turnstile_enabled">
          <input type="checkbox" id="turnstile_enabled" name="turnstile_enabled" value="1"<?= $s['turnstile_enabled'] ? ' checked' : '' ?>>
          <span class="switch__track" aria-hidden="true"></span>
          <span class="switch__label">Use Cloudflare Turnstile on the callback form</span>
        </label>
      </div>
      <div class="fld">
        <label for="turnstile_site_key">Site key</label>
        <input id="turnstile_site_key" name="turnstile_site_key" type="text" value="<?= e($s['turnstile_site_key']) ?>" autocomplete="off" spellcheck="false">
      </div>
      <div class="fld">
        <label for="turnstile_secret_key">Secret key</label>
        <p class="fld__help" id="ts-secret-help"><?= $s['turnstile_secret_set'] ? 'A secret key is saved (hidden for security). Leave empty to keep it.' : 'Kept private on the server and never shown again.' ?></p>
        <input id="turnstile_secret_key" name="turnstile_secret_key" type="password" value="" autocomplete="new-password" aria-describedby="ts-secret-help">
      </div>
      <?php if ($s['turnstile_secret_set']): ?>
        <label class="check"><input type="checkbox" name="turnstile_clear" value="1"> Remove the saved keys</label>
      <?php endif; ?>
    </details>
  </section>

  <div class="save-bar">
    <button class="btn btn--primary" type="submit">Save settings</button>
  </div>
</form>

<section class="card">
  <h2>Test email alerts</h2>
  <p class="card__intro">Sends a test message to the alert address saved above.</p>
  <form method="post" action="<?= e(url('/admin/settings/test-email')) ?>">
    <?= Csrf::field() ?>
    <button class="btn btn--light" type="submit">Send a test email</button>
  </form>
</section>
