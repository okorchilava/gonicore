<?php
$isEdit    = !empty($editUser);
$pageTitle = $isEdit ? 'Edit User' : 'New User';
$activeNav = 'users';
ob_start(); ?>
<a href="<?= e($base) ?>/manage/users" class="topbar-btn ghost">← Users</a>
<?php $topbarActions = ob_get_clean();
$formAction = $isEdit
    ? e($base) . '/manage/users/' . (int)$editUser['id'] . '/edit'
    : e($base) . '/manage/users/new';
$u = $editUser ?? [];
?>

<?php if (!empty($error ?? null)): ?>
<div id="gc-flash" data-msg="<?= e($error) ?>" data-icon="error" style="display:none"></div>
<?php endif ?>

<div style="max-width:680px">
<form method="POST" action="<?= $formAction ?>">

    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><h3>Profile</h3></div>
        <div class="card-body" style="padding:20px;display:flex;flex-direction:column;gap:14px">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-input"
                           value="<?= e((string)($u['name'] ?? '')) ?>" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-input" style="font-family:monospace"
                           value="<?= e((string)($u['username'] ?? '')) ?>" placeholder="Optional">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-input"
                           value="<?= e((string)($u['email'] ?? '')) ?>" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-input"
                           value="<?= e((string)($u['phone'] ?? '')) ?>" placeholder="Optional">
                </div>
            </div>

            <div class="form-group" style="margin:0">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <?php foreach (['admin'=>'Admin','editor'=>'Editor','viewer'=>'Viewer'] as $val => $lbl): ?>
                    <option value="<?= $val ?>" <?= (($u['role'] ?? 'viewer') === $val) ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:20px">
        <div class="card-header">
            <h3><?= $isEdit ? 'Change Password' : 'Password' ?></h3>
            <?php if ($isEdit): ?><span style="font-size:12px;color:var(--muted)">Leave blank to keep current</span><?php endif ?>
        </div>
        <div class="card-body" style="padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="form-group" style="margin:0">
                <label class="form-label"><?= $isEdit ? 'New Password' : 'Password *' ?></label>
                <input type="password" name="password" class="form-input"
                       <?= $isEdit ? '' : 'required' ?> autocomplete="new-password"
                       placeholder="Min 8 characters">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirm" class="form-input"
                       autocomplete="new-password" placeholder="Repeat password">
            </div>
        </div>
    </div>

    <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary" style="padding:10px 28px">
            <?= $isEdit ? 'Save Changes' : 'Create User' ?>
        </button>
        <a href="<?= e($base) ?>/manage/users" class="btn btn-ghost" style="padding:10px 20px">Cancel</a>
    </div>

</form>
</div>
