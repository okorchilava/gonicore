<?php
$pageTitle = t('languages.title');
$activeNav = 'languages';
ob_start(); ?>
<a href="<?= e($base) ?>/manage/languages" class="topbar-btn ghost">← <?= e(t('languages.title')) ?></a>
<?php $topbarActions = ob_get_clean(); ?>

<div>
    <form method="POST" action="<?= e($base) ?>/manage/languages/<?= e((string)($lang['code'] ?? '')) ?>/edit">
        <div class="card">
            <div class="card-header">
                <h3><?= e((string)($lang['flag'] ?? '')) ?> <?= e((string)($lang['name'] ?? '')) ?></h3>
                <code style="background:var(--bg);padding:2px 8px;border-radius:4px;font-size:12px"><?= e((string)($lang['code'] ?? '')) ?></code>
            </div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label"><?= e(t('languages.flag')) ?></label>
                        <input type="text" name="flag" class="form-input"
                               value="<?= e((string)($lang['flag'] ?? '🌐')) ?>"
                               placeholder="🇬🇧" maxlength="10" required>
                        <div style="font-size:12px;color:var(--muted);margin-top:5px">🇬🇪 🇬🇧 🇺🇸 🇩🇪 🇫🇷</div>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label"><?= e(t('languages.name')) ?></label>
                        <input type="text" name="name" class="form-input"
                               value="<?= e((string)($lang['name'] ?? '')) ?>" required>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label"><?= e(t('languages.native')) ?></label>
                        <input type="text" name="native" class="form-input"
                               value="<?= e((string)($lang['native'] ?? '')) ?>" required>
                    </div>
                </div>
            </div>
        </div>
        <div style="margin-top:14px;display:flex;gap:8px">
            <button type="submit" class="btn btn-primary" style="padding:10px 24px;font-size:14px"><?= e(t('admin.save')) ?></button>
            <a href="<?= e($base) ?>/manage/languages" class="btn btn-ghost" style="padding:10px 24px;font-size:14px"><?= e(t('admin.cancel')) ?></a>
        </div>
    </form>
</div>
