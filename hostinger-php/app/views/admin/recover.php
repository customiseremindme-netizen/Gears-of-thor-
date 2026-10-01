<?php
defined('GOT_APP') || exit;

use Got\Csrf;

/** @var array $flashes */
/** @var bool $available */
?>
<h1>Reset a password</h1>
<?php foreach ($flashes as $flash): ?>
  <div class="flash flash--<?= e($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>"><?= e($flash['message']) ?></div>
<?php endforeach; ?>
<p>Another owner can reset your password from <strong>Users</strong>. If nobody can sign in, prove you manage the hosting:</p>
<ol class="steps">
  <li>In Hostinger hPanel, open <strong>File Manager</strong> and go to <code>public_html/storage</code>.</li>
  <li>Create a new file named <code>recovery.txt</code>.</li>
  <li>Type a code of at least 12 characters into it (for example <code>my-reset-code-2026</code>) and save.</li>
  <li>Enter the same code below with your email and a new password. The file is deleted automatically afterwards.</li>
</ol>
<?php if (!$available): ?>
  <p class="notice">No <code>storage/recovery.txt</code> file was found yet.</p>
<?php endif; ?>
<form method="post" action="<?= e(url('/admin/recover')) ?>" class="stack">
  <?= Csrf::field() ?>
  <div class="fld">
    <label for="rec-code">Recovery code (from recovery.txt)</label>
    <input id="rec-code" name="code" type="text" required autocomplete="off" spellcheck="false">
  </div>
  <div class="fld">
    <label for="rec-email">Your dashboard email</label>
    <input id="rec-email" name="email" type="email" required autocomplete="username">
  </div>
  <div class="fld">
    <label for="rec-pass">New password (at least 10 characters)</label>
    <input id="rec-pass" name="password" type="password" required minlength="10" autocomplete="new-password">
  </div>
  <div class="fld">
    <label for="rec-pass2">Type the new password again</label>
    <input id="rec-pass2" name="password_confirm" type="password" required minlength="10" autocomplete="new-password">
  </div>
  <button class="btn btn--primary btn--block" type="submit">Set new password</button>
</form>
<p class="small muted"><a href="<?= e(url('/admin/login')) ?>">← Back to sign in</a></p>
