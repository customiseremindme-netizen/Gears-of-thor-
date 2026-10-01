<?php
defined('GOT_APP') || exit;

use Got\Auth;
use Got\Csrf;

/** @var array $users */
/** @var array $user */
?>
<div class="page-head">
  <h1>Users</h1>
  <p>Give each person their own sign-in. Never share passwords.</p>
</div>

<div class="notice">
  <p><strong>Owner</strong>: everything, including users and settings. <strong>Editor</strong>: website content, photos, publishing and enquiries. <strong>Staff</strong>: enquiries only (update status and notes).</p>
</div>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th scope="col">Name</th><th scope="col">Role</th><th scope="col">Last sign-in</th><th scope="col">Manage</th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr<?= (int) $u['is_active'] ? '' : ' class="is-muted"' ?>>
          <td data-label="Name"><strong><?= e($u['name']) ?></strong><br><span class="muted small"><?= e($u['email']) ?></span><?= (int) $u['id'] === (int) $user['id'] ? ' <span class="pill pill--ok">You</span>' : '' ?></td>
          <td data-label="Role"><?= e(Auth::ROLES[$u['role']] ?? $u['role']) ?><?= (int) $u['is_active'] ? '' : ' <span class="pill pill--off">Switched off</span>' ?></td>
          <td data-label="Last sign-in"><?= $u['last_login_at'] ? e(ist($u['last_login_at'])) : '<span class="muted">Never</span>' ?></td>
          <td data-label="Manage">
            <details class="details-box details-box--inline">
              <summary>Edit</summary>
              <form method="post" action="<?= e(url('/admin/users/' . (int) $u['id'])) ?>" class="stack">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="save">
                <div class="fld">
                  <label for="role-<?= (int) $u['id'] ?>">Role</label>
                  <select id="role-<?= (int) $u['id'] ?>" name="role">
                    <?php foreach (Auth::ROLES as $key => $label): ?>
                      <option value="<?= e($key) ?>"<?= $u['role'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <label class="check"><input type="checkbox" name="is_active" value="1"<?= (int) $u['is_active'] ? ' checked' : '' ?>> Account is switched on</label>
                <button class="btn btn--light btn--sm" type="submit">Save</button>
              </form>
              <form method="post" action="<?= e(url('/admin/users/' . (int) $u['id'])) ?>" class="stack">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="password">
                <div class="fld">
                  <label for="pw-<?= (int) $u['id'] ?>">Set a new password</label>
                  <input id="pw-<?= (int) $u['id'] ?>" name="password" type="password" minlength="10" autocomplete="new-password">
                </div>
                <button class="btn btn--light btn--sm" type="submit">Change password</button>
              </form>
            </details>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<section class="card">
  <h2>Add a person</h2>
  <form method="post" action="<?= e(url('/admin/users')) ?>" class="form-grid">
    <?= Csrf::field() ?>
    <div class="fld">
      <label for="new-name">Name</label>
      <input id="new-name" name="name" type="text" required maxlength="100">
    </div>
    <div class="fld">
      <label for="new-email">Email (used to sign in)</label>
      <input id="new-email" name="email" type="email" required>
    </div>
    <div class="fld">
      <label for="new-role">Role</label>
      <select id="new-role" name="role">
        <option value="staff">Staff — enquiries only</option>
        <option value="editor">Editor — website and enquiries</option>
        <option value="owner">Owner — everything</option>
      </select>
    </div>
    <div class="fld">
      <label for="new-password">Temporary password (at least 10 characters)</label>
      <input id="new-password" name="password" type="password" required minlength="10" autocomplete="new-password">
    </div>
    <div><button class="btn btn--primary" type="submit">Add person</button></div>
  </form>
</section>
