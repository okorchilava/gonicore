<?php
$pageTitle = 'Edit Language';
$activeNav = 'languages';
ob_start(); ?>
<a href="<?= e($base) ?>/manage/languages" class="topbar-btn ghost">← Back</a>
<?php $topbarActions = ob_get_clean(); ?>

<div style="max-width:480px">
    <form method="POST" action="<?= e($base) ?>/manage/languages/<?= e((string)($lang['code'] ?? '')) ?>/edit">
        <div class="card">
            <div class="card-header">
                <h3><?= e((string)($lang['flag'] ?? '')) ?> <?= e((string)($lang['name'] ?? '')) ?></h3>
                <code style="background:var(--bg);padding:2px 8px;border-radius:4px;font-size:12px"><?= e((string)($lang['code'] ?? '')) ?></code>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Flag emoji</label>
                    <input type="text" name="flag" class="form-input"
                           value="<?= e((string)($lang['flag'] ?? '🌐')) ?>"
                           placeholder="🇬🇧" maxlength="10" required>
                    <div style="font-size:12px;color:var(--muted);margin-top:5px">Paste a flag emoji, e.g. 🇬🇪 🇬🇧 🇺🇸 🇩🇪 🇫🇷</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Name <span style="color:var(--muted);font-weight:400">(English)</span></label>
                    <input type="text" name="name" class="form-input"
                           value="<?= e((string)($lang['name'] ?? '')) ?>" required>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Native name</label>
                    <input type="text" name="native" class="form-input"
                           value="<?= e((string)($lang['native'] ?? '')) ?>" required>
                </div>
            </div>
        </div>
        <div style="margin-top:14px;display:flex;gap:8px">
            <button type="submit" class="btn btn-primary" style="padding:10px 24px;font-size:14px">Save Changes</button>
            <a href="<?= e($base) ?>/manage/languages" class="btn btn-ghost" style="padding:10px 24px;font-size:14px">Cancel</a>
        </div>
    </form>
</div>
