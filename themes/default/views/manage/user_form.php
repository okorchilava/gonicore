<?php
$isEdit    = !empty($editUser);
$pageTitle = $isEdit ? t('users.edit') : t('users.new');
$activeNav = 'users';
ob_start(); ?>
<a href="<?= e($base) ?>/manage/users" class="topbar-btn ghost">← <?= e(t('users.title')) ?></a>
<?php $topbarActions = ob_get_clean();
$formAction = $isEdit
    ? e($base) . '/manage/users/' . (int)$editUser['id'] . '/edit'
    : e($base) . '/manage/users/new';
$u = $editUser ?? [];
?>

<form method="POST" action="<?= $formAction ?>">

    <div class="user-form-grid">
    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><h3><?= e(t('profile.info')) ?></h3></div>
        <div class="card-body" style="padding:20px;display:flex;flex-direction:column;gap:14px">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group" style="margin:0">
                    <label class="form-label"><?= e(t('users.name')) ?> *</label>
                    <input type="text" name="name" class="form-input"
                           value="<?= e((string)($u['name'] ?? '')) ?>" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label"><?= e(t('users.username')) ?></label>
                    <input type="text" name="username" class="form-input" style="font-family:monospace"
                           value="<?= e((string)($u['username'] ?? '')) ?>">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group" style="margin:0">
                    <label class="form-label"><?= e(t('users.email')) ?> *</label>
                    <input type="email" name="email" class="form-input"
                           value="<?= e((string)($u['email'] ?? '')) ?>" required>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label"><?= e(t('users.phone')) ?></label>
                    <input type="text" name="phone" class="form-input"
                           value="<?= e((string)($u['phone'] ?? '')) ?>">
                </div>
            </div>

            <div class="form-group" style="margin:0">
                <label class="form-label"><?= e(t('users.role')) ?></label>
                <select name="role" class="form-select">
                    <?php foreach (['admin'=>t('users.role_admin'),'editor'=>t('users.role_editor'),'viewer'=>t('users.role_viewer')] as $val => $lbl): ?>
                    <option value="<?= $val ?>" <?= (($u['role'] ?? 'viewer') === $val) ? 'selected' : '' ?>><?= e($lbl) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:20px">
        <div class="card-header">
            <h3><?= e(t('users.password')) ?></h3>
        </div>
        <div class="card-body" style="padding:20px;display:flex;flex-direction:column;gap:14px">
            <div class="form-group" style="margin:0">
                <label class="form-label"><?= e(t('users.password')) ?><?= $isEdit ? '' : ' *' ?></label>
                <input type="password" name="password" class="form-input"
                       <?= $isEdit ? '' : 'required' ?> autocomplete="new-password">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label"><?= e(t('users.confirm_pass')) ?></label>
                <input type="password" name="password_confirm" class="form-input"
                       autocomplete="new-password">
            </div>
        </div>
    </div>
    </div><!-- /.user-form-grid -->

    <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary" style="padding:10px 28px">
            <?= $isEdit ? e(t('admin.save')) : e(t('admin.create')) ?>
        </button>
        <a href="<?= e($base) ?>/manage/users" class="btn btn-ghost" style="padding:10px 20px"><?= e(t('admin.cancel')) ?></a>
    </div>

</form>

<style>
.user-form-grid { display:grid; grid-template-columns:2fr 1fr; gap:16px; align-items:start; }
@media (max-width: 900px) { .user-form-grid { grid-template-columns:1fr; } }
</style>
