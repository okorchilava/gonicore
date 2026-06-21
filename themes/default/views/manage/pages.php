<?php
$pageTitle = t('pages.title');
$activeNav = 'pages';
ob_start(); ?>
<a href="<?= e($base) ?>/manage/pages/new" class="topbar-btn">+ <?= e(t('pages.new')) ?></a>
<?php $topbarActions = ob_get_clean(); ?>

<div class="card">
    <?php if (!empty($pages ?? [])): ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:50%"><?= e(t('posts.post_title')) ?></th>
                    <th><?= e(t('pages.parent')) ?></th>
                    <th><?= e(t('admin.status')) ?></th>
                    <th><?= e(t('posts.date')) ?></th>
                    <th style="text-align:right"><?= e(t('admin.actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Build parent map
                $parentMap = [];
                foreach (($pages ?? []) as $pg) { $parentMap[(int)$pg['id']] = $pg['title']; }
                foreach (($pages ?? []) as $pg):
                    $indent = !empty($pg['parent_id']) ? 'padding-left:22px;border-left:2px solid var(--border);' : '';
                ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;<?= $indent ?>">
                            <?php if (!empty($pg['featured_image'])): ?>
                            <img src="<?= e($pg['featured_image']) ?>"
                                 style="width:36px;height:36px;object-fit:cover;border-radius:5px;border:1px solid var(--border);flex-shrink:0"
                                 onerror="this.style.display='none'">
                            <?php else: ?>
                            <div style="width:36px;height:36px;border-radius:5px;background:var(--bg);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--muted)"><span class="material-symbols-outlined mi-sm">description</span></div>
                            <?php endif ?>
                            <div>
                                <a href="<?= e($base) ?>/manage/pages/<?= (int)$pg['id'] ?>"
                                   style="font-weight:600;color:var(--text);text-decoration:none"
                                   onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text)'">
                                    <?= e($pg['title']) ?>
                                </a>
                                <div style="font-size:11px;color:var(--muted);margin-top:2px">/page/<?= e($pg['slug']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="color:var(--muted);font-size:13px">
                        <?= !empty($pg['parent_id']) && isset($parentMap[$pg['parent_id']])
                            ? e($parentMap[$pg['parent_id']]) : '<span style="opacity:.4">—</span>' ?>
                    </td>
                    <td><span class="badge <?= e($pg['status']) ?>"><?= e($pg['status']) ?></span></td>
                    <td style="color:var(--muted);font-size:12px;white-space:nowrap"><?= e(fmt_date((string)$pg['updated_at'])) ?></td>
                    <td style="text-align:right;white-space:nowrap">
                        <a href="<?= e($base) ?>/page/<?= e($pg['slug']) ?>" target="_blank"
                           class="btn btn-ghost" style="padding:4px 8px;font-size:12px;margin-right:4px" title="View">↗</a>
                        <a href="<?= e($base) ?>/manage/pages/<?= (int)$pg['id'] ?>"
                           class="btn btn-ghost" style="padding:4px 10px;font-size:12px;margin-right:4px"><?= e(t('admin.edit')) ?></a>
                        <form method="POST" action="<?= e($base) ?>/manage/pages/<?= (int)$pg['id'] ?>/delete" style="display:inline">
                            <button type="button" class="btn btn-danger" style="padding:4px 10px;font-size:12px"
                                onclick="gcConfirm(this, <?= e(json_encode(t('pages.confirm_delete'), JSON_UNESCAPED_UNICODE)) ?>, <?= e(json_encode('«' . $pg['title'] . '» — ' . t('admin.cannot_undo'), JSON_UNESCAPED_UNICODE)) ?>, <?= e(json_encode(t('admin.yes_delete'), JSON_UNESCAPED_UNICODE)) ?>)">
                                <?= e(t('admin.delete')) ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty">
        <div class="empty-icon"><span class="material-symbols-outlined" style="font-size:36px">description</span></div>
        <h3><?= e(t('pages.no_pages')) ?></h3>
        <a href="<?= e($base) ?>/manage/pages/new" class="btn btn-primary" style="margin-top:12px">+ <?= e(t('pages.new')) ?></a>
    </div>
    <?php endif ?>
</div>
