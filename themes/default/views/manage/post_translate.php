<?php
$pageTitle = t('posts.translate');
$activeNav = 'posts';
ob_start(); ?>
<a href="<?= e($base) ?>/manage/posts/<?= (int)($post['id'] ?? 0) ?>" class="topbar-btn ghost">← <?= e(t('posts.title')) ?></a>
<?php $topbarActions = ob_get_clean(); ?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">
    <span style="font-size:24px"><?= e((string)($lang['flag'] ?? '🌐')) ?></span>
    <div>
        <div style="font-size:13px;color:var(--muted)"><?= e(t('posts.translating_to')) ?></div>
        <div style="font-size:16px;font-weight:700"><?= e((string)($lang['name'] ?? '')) ?> (<?= e((string)($lang['native'] ?? '')) ?>)</div>
    </div>
</div>

<form method="POST" action="<?= e($base) ?>/manage/posts/<?= (int)($post['id'] ?? 0) ?>/translate/<?= e((string)($lang['code'] ?? '')) ?>">
<div class="translate-grid">

    <!-- Main -->
    <div style="min-width:0">
        <div class="card" style="margin-bottom:14px;padding:14px 20px;background:var(--bg)">
            <div style="font-size:12px;color:var(--muted);margin-bottom:4px"><?= e(t('posts.original')) ?></div>
            <div style="font-weight:600"><?= e((string)($post['title'] ?? '')) ?></div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label"><?= e(t('posts.post_title')) ?></label>
                    <input type="text" name="title" class="form-input"
                           value="<?= e((string)($translation['title'] ?? '')) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('posts.slug')) ?></label>
                    <input type="text" name="slug" class="form-input"
                           value="<?= e((string)($translation['slug'] ?? '')) ?>"
                           placeholder="auto">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label"><?= e(t('posts.content')) ?></label>
                    <textarea name="content" class="form-textarea"><?= e((string)($translation['content'] ?? '')) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Side -->
    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><h3><?= e(t('admin.status')) ?></h3></div>
            <div class="card-body">
                <?php $curStatus = $translation['status'] ?? 'draft'; ?>
                <?php foreach (['draft'=>t('posts.draft'),'published'=>t('posts.published'),'archived'=>t('posts.archived')] as $val => $label): ?>
                <label style="display:flex;align-items:center;gap:8px;margin-bottom:10px;cursor:pointer;font-size:14px">
                    <input type="radio" name="status" value="<?= $val ?>"
                           <?= $curStatus === $val ? 'checked' : '' ?>
                           style="accent-color:var(--accent)">
                    <?= e($label) ?>
                </label>
                <?php endforeach ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px 28px;font-size:14px"><?= e(t('posts.save_translation')) ?></button>
    </div>

</div>
</form>

<style>
.translate-grid { display:grid; grid-template-columns:1fr 320px; gap:20px; align-items:start; }
@media (max-width: 900px) { .translate-grid { grid-template-columns:1fr; } }
</style>
