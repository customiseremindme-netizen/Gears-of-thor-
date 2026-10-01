<?php
defined('GOT_APP') || exit;

use Got\Csrf;

/** @var string $email */
/** @var array $flashes */
?>
<h1>Sign in</h1>
<p class="muted">Website dashboard for GOT FITNEZZ.</p>
<?php foreach ($flashes as $flash): ?>
  <div class="flash flash--<?= e($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>"><?= e($flash['message']) ?></div>
<?php endforeach; ?>
<form method="post" action="<?= e(url('/admin/login')) ?>" class="stack">
  <?= Csrf::field() ?>
  <div class="fld">
    <label for="login-email">Email</label>
    <input id="login-email" name="email" type="email" autocomplete="username" required value="<?= e($email) ?>" autofocus>
  </div>
  <div class="fld">
    <label for="login-password">Password</label>
    <input id="login-password" name="password" type="password" autocomplete="current-password" required>
  </div>
  <button class="btn btn--primary btn--block" type="submit">Sign in</button>
</form>
<p class="small muted"><a href="<?= e(url('/admin/recover')) ?>">Forgotten your password?</a></p>
