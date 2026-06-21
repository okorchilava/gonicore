<?php
$pageTitle = t('posts.title');
$activeNav = 'posts';
ob_start(); ?>
<a href="<?= e($base) ?>/manage/posts/new" class="topbar-btn">+ <?= e(t('posts.new')) ?></a>
<?php $topbarActions = ob_get_clean(); ?>

<!-- Filters -->
<div style="display:flex;align-items:center;gap:8px;margin-bottom:18px;flex-wrap:wrap">
    <?php foreach (['' => t('admin.all'), 'published' => t('posts.published'), 'draft' => t('posts.draft'), 'archived' => t('posts.archived')] as $val => $label): ?>
    <a href="?status=<?= e($val) ?>" class="btn btn-ghost"
       style="<?= ($status ?? '') === $val ? 'background:var(--accent);color:#fff;border-color:var(--accent)' : '' ?>">
        <?= e($label) ?>
        <?php if ($val === ''): ?><span style="opacity:.6;font-size:11px;margin-left:2px"><?= (int)($total ?? 0) ?></span><?php endif ?>
    </a>
    <?php endforeach ?>
</div>

<div class="card">
    <?php if (!empty($posts)): ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:46%"><?= e(t('posts.post_title')) ?></th>
                    <th><?= e(t('posts.category')) ?></th>
                    <th><?= e(t('admin.status')) ?></th>
                    <th><?= e(t('posts.date')) ?></th>
                    <th style="text-align:right"><?= e(t('admin.actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $p): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <?php if (!empty($p['featured_image'])): ?>
                            <img src="<?= e($p['featured_image']) ?>"
                                 style="width:40px;height:40px;object-fit:cover;border-radius:6px;border:1px solid var(--border);flex-shrink:0"
                                 onerror="this.style.display='none'">
                            <?php else: ?>
                            <div style="width:40px;height:40px;border-radius:6px;background:var(--bg);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--muted)"><span class="material-symbols-outlined mi-sm">article</span></div>
                            <?php endif ?>
                            <div style="min-width:0">
                                <a href="<?= e($base) ?>/manage/posts/<?= (int)$p['id'] ?>"
                                   style="font-weight:600;color:var(--text);text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:320px"
                                   onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text)'">
                                    <?= e($p['title']) ?>
                                </a>
                                <div style="font-size:11px;color:var(--muted);margin-top:2px">/post/<?= e($p['slug']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="color:var(--muted);font-size:13px">
                        <?= isset($p['category_id'], $catMap[$p['category_id']]) ? e($catMap[$p['category_id']]) : '<span style="opacity:.4">—</span>' ?>
                    </td>
                    <td><span class="badge <?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                    <td style="color:var(--muted);font-size:12px;white-space:nowrap"><?= e(fmt_date((string)$p['created_at'])) ?></td>
                    <td style="text-align:right;white-space:nowrap">
                        <a href="<?= e($base) ?>/post/<?= e($p['slug']) ?>" target="_blank"
                           class="btn btn-ghost" style="padding:4px 8px;font-size:12px;margin-right:4px" title="View">↗</a>
                        <a href="<?= e($base) ?>/manage/posts/<?= (int)$p['id'] ?>"
                           class="btn btn-ghost" style="padding:4px 10px;font-size:12px;margin-right:4px"><?= e(t('admin.edit')) ?></a>
                        <form method="POST" action="<?= e($base) ?>/manage/posts/<?= (int)$p['id'] ?>/delete" style="display:inline">
                            <button type="button" class="btn btn-danger" style="padding:4px 10px;font-size:12px"
                                onclick="gcConfirm(this, <?= e(json_encode(t('posts.confirm_delete'), JSON_UNESCAPED_UNICODE)) ?>, <?= e(json_encode('«' . $p['title'] . '» — ' . t('admin.cannot_undo'), JSON_UNESCAPED_UNICODE)) ?>, <?= e(json_encode(t('admin.yes_delete'), JSON_UNESCAPED_UNICODE)) ?>)">
                                <?= e(t('admin.delete')) ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>

    <?php if (($pages ?? 1) > 1): ?>
    <div style="padding:16px 20px;border-top:1px solid var(--border)">
        <nav class="pagination" style="padding-top:0">
            <?php if (($page ?? 1) > 1): ?>
                <a href="?page=<?= ($page - 1) ?>&status=<?= e($status ?? '') ?>">‹</a>
            <?php else: ?>
                <span class="disabled">‹</span>
            <?php endif ?>
            <?php for ($i = max(1, ($page ?? 1) - 2); $i <= min(($pages ?? 1), ($page ?? 1) + 2); $i++): ?>
                <?php if ($i === ($page ?? 1)): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&status=<?= e($status ?? '') ?>"><?= $i ?></a>
                <?php endif ?>
            <?php endfor ?>
            <?php if (($page ?? 1) < ($pages ?? 1)): ?>
                <a href="?page=<?= (($page ?? 1) + 1) ?>&status=<?= e($status ?? '') ?>">›</a>
            <?php else: ?>
                <span class="disabled">›</span>
            <?php endif ?>
        </nav>
    </div>
    <?php endif ?>

    <?php else: ?>
    <div class="empty">
        <div class="empty-icon"><span class="material-symbols-outlined" style="font-size:36px">article</span></div>
        <h3><?= e(t('posts.no_posts')) ?></h3>
        <p style="margin-bottom:20px"><?= e(t('posts.create_first')) ?></p>
        <a href="<?= e($base) ?>/manage/posts/new" class="btn btn-primary">+ <?= e(t('posts.new')) ?></a>
    </div>
    <?php endif ?>
</div>
