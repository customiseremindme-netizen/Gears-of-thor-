<?php
defined('GOT_APP') || exit;

/** @var array $checks */
/** @var bool $ready */
/** @var bool $hasCode */
/** @var array $values */
/** @var array $errors */
/** @var bool $sqlite */
/** @var bool $mysql */

$err = static fn (string $key): string => isset($errors[$key]) ? '<p class="fld__error">' . e($errors[$key]) . '</p>' : '';
$val = static fn (string $key): string => e($values[$key] ?? '');
?>
<h1>Set up your website</h1>
<p class="muted">This one-time setup connects the website to its database and creates your dashboard login. It takes about five minutes.</p>

<?php if (isset($errors['db'])): ?><div class="flash flash--error" role="alert"><?= e($errors['db']) ?></div><?php endif; ?>
<?php if (isset($errors['requirements'])): ?><div class="flash flash--error" role="alert"><?= e($errors['requirements']) ?></div><?php endif; ?>

<section class="install-step">
  <h2><span class="step-no">1</span> Hosting check</h2>
  <ul class="checks">
    <?php foreach ($checks as $check): ?>
      <li class="<?= $check['ok'] ? 'is-ok' : ($check['required'] ? 'is-bad' : 'is-warn') ?>">
        <span class="checks__icon" aria-hidden="true"><?= $check['ok'] ? '✓' : ($check['required'] ? '✕' : '!') ?></span>
        <div>
          <strong><?= e($check['label']) ?></strong>
          <span class="visually-hidden"><?= $check['ok'] ? 'OK' : ($check['required'] ? 'Problem' : 'Recommended') ?></span>
          <?php if (!$check['ok']): ?><span class="small"><?= e($check['detail']) ?></span><?php endif; ?>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php if ($ready): ?>
    <p class="ok-text">Your hosting has everything this website needs.</p>
  <?php else: ?>
    <p class="bad-text">Please fix the items marked ✕, then reload this page.</p>
  <?php endif; ?>
</section>

<form method="post" action="<?= e(url('/install')) ?>" class="stack-lg" autocomplete="off">
  <section class="install-step">
    <h2><span class="step-no">2</span> Prove you own this website</h2>
    <?php if ($hasCode): ?>
      <p>For security, open <strong>hPanel → Files → File Manager</strong>, go to <code>public_html/storage</code> and open <code>setup-code.txt</code>. Copy the code inside and paste it here.</p>
    <?php else: ?>
      <p class="bad-text">The setup code could not be created because the “storage” folder is not writable.</p>
    <?php endif; ?>
    <div class="fld<?= isset($errors['setup_code']) ? ' has-error' : '' ?>">
      <label for="setup_code">Setup code</label>
      <input id="setup_code" name="setup_code" type="text" required spellcheck="false" autocapitalize="characters">
      <?= $err('setup_code') ?>
    </div>
  </section>

  <section class="install-step">
    <h2><span class="step-no">3</span> Database</h2>
    <p class="muted small">In hPanel open <strong>Databases → MySQL Databases</strong>, create a database and user, then copy the details here. Hostinger names look like <code>u123456789_gym</code>.</p>
    <?php if ($mysql && $sqlite): ?>
      <div class="fld">
        <label for="db_driver">Database type</label>
        <select id="db_driver" name="db_driver" data-db-driver>
          <option value="mysql"<?= $values['db_driver'] === 'mysql' ? ' selected' : '' ?>>MySQL (recommended for Hostinger)</option>
          <option value="sqlite"<?= $values['db_driver'] === 'sqlite' ? ' selected' : '' ?>>SQLite file (for testing only)</option>
        </select>
      </div>
    <?php else: ?>
      <input type="hidden" name="db_driver" value="<?= $mysql ? 'mysql' : 'sqlite' ?>">
    <?php endif; ?>
    <div class="form-grid" data-mysql-fields>
      <div class="fld<?= isset($errors['db_name']) ? ' has-error' : '' ?>">
        <label for="db_name">Database name</label>
        <input id="db_name" name="db_name" type="text" value="<?= $val('db_name') ?>" spellcheck="false">
        <?= $err('db_name') ?>
      </div>
      <div class="fld<?= isset($errors['db_user']) ? ' has-error' : '' ?>">
        <label for="db_user">Database username</label>
        <input id="db_user" name="db_user" type="text" value="<?= $val('db_user') ?>" spellcheck="false">
        <?= $err('db_user') ?>
      </div>
      <div class="fld">
        <label for="db_pass">Database password</label>
        <input id="db_pass" name="db_pass" type="password" autocomplete="new-password">
      </div>
      <div class="fld">
        <label for="db_host">Database host</label>
        <input id="db_host" name="db_host" type="text" value="<?= $val('db_host') ?>" spellcheck="false">
        <p class="fld__help">Usually <code>localhost</code> on Hostinger.</p>
      </div>
    </div>
  </section>

  <section class="install-step">
    <h2><span class="step-no">4</span> Your dashboard login</h2>
    <div class="form-grid">
      <div class="fld<?= isset($errors['owner_name']) ? ' has-error' : '' ?>">
        <label for="owner_name">Your name</label>
        <input id="owner_name" name="owner_name" type="text" value="<?= $val('owner_name') ?>" autocomplete="name">
        <?= $err('owner_name') ?>
      </div>
      <div class="fld<?= isset($errors['owner_email']) ? ' has-error' : '' ?>">
        <label for="owner_email">Email (to sign in)</label>
        <input id="owner_email" name="owner_email" type="email" value="<?= $val('owner_email') ?>" autocomplete="username">
        <?= $err('owner_email') ?>
      </div>
      <div class="fld<?= isset($errors['owner_password']) ? ' has-error' : '' ?>">
        <label for="owner_password">Password (at least 10 characters)</label>
        <input id="owner_password" name="owner_password" type="password" minlength="10" autocomplete="new-password">
        <?= $err('owner_password') ?>
      </div>
      <div class="fld">
        <label for="site_url">Website address</label>
        <input id="site_url" name="site_url" type="text" value="<?= $val('site_url') ?>" spellcheck="false">
      </div>
    </div>
  </section>

  <button class="btn btn--primary btn--block" type="submit"<?= $ready ? '' : ' disabled' ?>>Install website</button>
</form>
