<?php
defined('GOT_APP') || exit;

use Got\Auth;
use Got\Csrf;

/** @var array $me */
?>
<div class="page-head">
  <h1>My account</h1>
  <p>You are signed in as <?= e(Auth::ROLES[$me['role']] ?? $me['role']) ?>.</p>
</div>

<section class="card card--narrow">
  <form method="post" action="<?= e(url('/admin/account')) ?>" class="stack">
    <?= Csrf::field() ?>
    <div class="fld">
      <label for="acc-name">Your name</label>
      <input id="acc-name" name="name" type="text" required maxlength="100" value="<?= e($me['name']) ?>" autocomplete="name">
    </div>
    <div class="fld">
      <label for="acc-email">Email (used to sign in)</label>
      <input id="acc-email" name="email" type="email" required value="<?= e($me['email']) ?>" autocomplete="username">
    </div>
    <fieldset class="fieldset">
      <legend>Change password (optional)</legend>
      <div class="fld">
        <label for="acc-new">New password (at least 10 characters)</label>
        <input id="acc-new" name="new_password" type="password" minlength="10" autocomplete="new-password">
      </div>
      <div class="fld">
        <label for="acc-new2">Type the new password again</label>
        <input id="acc-new2" name="new_password_confirm" type="password" minlength="10" autocomplete="new-password">
      </div>
    </fieldset>
    <div class="fld">
      <label for="acc-current">Current password (needed to save any change)</label>
      <input id="acc-current" name="current_password" type="password" required autocomplete="current-password">
    </div>
    <button class="btn btn--primary" type="submit">Save</button>
  </form>
</section>
