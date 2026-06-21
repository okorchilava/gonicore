<?php
/** @var array $user */
$pageTitle = 'Profile';
$u = $user ?? [];
?>

<div class="up-card">
  <h2>Account details</h2>
  <p class="sub">Update your name and email address.</p>
  <form method="post" action="<?= e($base) ?>/users/profile">
    <div class="up-grid2">
      <div class="up-field">
        <label>Full name</label>
        <input type="text" name="name" value="<?= e((string)($u['name'] ?? '')) ?>" required>
      </div>
      <div class="up-field">
        <label>Email</label>
        <input type="email" name="email" value="<?= e((string)($u['email'] ?? '')) ?>" required>
      </div>
    </div>
    <?php if (!empty($u['username'])): ?>
    <div class="up-field">
      <label>Username</label>
      <input type="text" value="<?= e((string)$u['username']) ?>" disabled style="background:var(--bg);color:var(--muted)">
    </div>
    <?php endif ?>
    <button type="submit" class="up-btn">Save changes</button>
  </form>
</div>

<div class="up-card">
  <h2>Change password</h2>
  <p class="sub">Enter your current password, then choose a new one (min 8 characters).</p>
  <form method="post" action="<?= e($base) ?>/users/profile/password" autocomplete="off">
    <div class="up-field">
      <label>Current password</label>
      <input type="password" name="current_password" autocomplete="current-password" required>
    </div>
    <div class="up-grid2">
      <div class="up-field">
        <label>New password</label>
        <input type="password" name="new_password" autocomplete="new-password" required>
      </div>
      <div class="up-field">
        <label>Confirm new password</label>
        <input type="password" name="confirm_password" autocomplete="new-password" required>
      </div>
    </div>
    <button type="submit" class="up-btn">Update password</button>
  </form>
</div>

