<?php
$pageTitle = 'Translate Post';
$activeNav = 'posts';
ob_start(); ?>
<a href="<?= e($base) ?>/manage/posts/<?= (int)($post['id'] ?? 0) ?>" class="topbar-btn ghost">← Back</a>
<?php $topbarActions = ob_get_clean(); ?>

<div style="max-width:860px">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">
        <span style="font-size:24px"><?= e((string)($lang['flag'] ?? '🌐')) ?></span>
        <div>
            <div style="font-size:13px;color:var(--muted)">Translating to</div>
            <div style="font-size:16px;font-weight:700"><?= e((string)($lang['name'] ?? '')) ?> (<?= e((string)($lang['native'] ?? '')) ?>)</div>
        </div>
    </div>

    <div class="card" style="margin-bottom:14px;padding:14px 20px;background:var(--bg)">
        <div style="font-size:12px;color:var(--muted);margin-bottom:4px">Original post</div>
        <div style="font-weight:600"><?= e((string)($post['title'] ?? '')) ?></div>
    </div>

    <form method="POST" action="<?= e($base) ?>/manage/posts/<?= (int)($post['id'] ?? 0) ?>/translate/<?= e((string)($lang['code'] ?? '')) ?>">
        <div class="card" style="margin-bottom:16px">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-input"
                           value="<?= e((string)($translation['title'] ?? '')) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-input"
                           value="<?= e((string)($translation['slug'] ?? '')) ?>"
                           placeholder="auto-generated">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Content</label>
                    <textarea name="content" class="form-textarea"><?= e((string)($translation['content'] ?? '')) ?></textarea>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><h3>Status</h3></div>
            <div class="card-body">
                <?php $curStatus = $translation['status'] ?? 'draft'; ?>
                <?php foreach (['draft'=>'Draft','published'=>'Published','archived'=>'Archived'] as $val => $label): ?>
                <label style="display:flex;align-items:center;gap:8px;margin-bottom:10px;cursor:pointer;font-size:14px">
                    <input type="radio" name="status" value="<?= $val ?>"
                           <?= $curStatus === $val ? 'checked' : '' ?>
                           style="accent-color:var(--accent)">
                    <?= $label ?>
                </label>
                <?php endforeach ?>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="padding:10px 28px;font-size:14px">Save Translation</button>
    </form>
</div>
