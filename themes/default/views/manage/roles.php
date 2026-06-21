<?php
$pageTitle     = t('roles.title');
$activeNav     = 'roles';
$roleList      = $roles ?? [];
$canManage     = $canManage ?? false;
$topbarActions = '';
?>

<div style="display:grid;grid-template-columns:<?= $canManage ? '340px 1fr' : '1fr' ?>;gap:24px;align-items:start">

    <?php if ($canManage): ?>
    <!-- Create role form -->
    <div class="card" style="position:sticky;top:80px">
        <div class="card-header"><h3><?= e(t('roles.new')) ?></h3></div>
        <div class="card-body" style="padding:18px">
            <form method="POST" action="<?= e($base) ?>/manage/roles">
                <div class="form-group">
                    <label class="form-label"><?= e(t('roles.label')) ?> *</label>
                    <input type="text" name="label" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('roles.name')) ?></label>
                    <input type="text" name="name" class="form-input" placeholder="<?= e(t('roles.name_hint')) ?>">
                    <div style="font-size:11px;color:var(--muted);margin-top:4px"><?= e(t('roles.name_help')) ?></div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('roles.description')) ?></label>
                    <textarea name="description" class="form-input" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                    <?= e(t('roles.create')) ?>
                </button>
            </form>
        </div>
    </div>
    <?php endif ?>

    <!-- Roles list -->
    <div class="card">
        <div class="card-header"><h3><?= count($roleList) ?> <?= e(t('roles.title')) ?></h3></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><?= e(t('roles.label')) ?></th>
                        <th><?= e(t('roles.name')) ?></th>
                        <th style="text-align:center"><?= e(t('roles.users')) ?></th>
                        <th style="text-align:center"><?= e(t('roles.permissions')) ?></th>
                        <th style="text-align:right"><?= e(t('admin.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roleList as $r): ?>
                    <tr>
                        <td>
                            <span style="font-weight:600"><?= e((string)$r['label']) ?></span>
                            <?php if ((int)($r['is_system'] ?? 0) === 1): ?>
                            <span style="font-size:10px;background:var(--border);color:var(--muted);padding:2px 6px;border-radius:4px;margin-left:6px"><?= e(t('roles.system')) ?></span>
                            <?php endif ?>
                            <?php if (!empty($r['description'])): ?>
                            <div style="font-size:12px;color:var(--muted);margin-top:2px"><?= e((string)$r['description']) ?></div>
                            <?php endif ?>
                        </td>
                        <td style="font-family:monospace;font-size:12px;color:var(--muted)"><?= e((string)$r['name']) ?></td>
                        <td style="text-align:center"><?= (int)($r['user_count'] ?? 0) ?></td>
                        <td style="text-align:center"><?= (int)($r['perm_count'] ?? 0) ?></td>
                        <td style="text-align:right;white-space:nowrap">
                            <a href="<?= e($base) ?>/manage/roles/<?= (int)$r['id'] ?>/edit" class="btn btn-ghost" style="font-size:11px;padding:3px 10px;margin-right:4px"><?= e($canManage ? t('admin.edit') : t('roles.view_perms')) ?></a>
                            <?php if ($canManage && (int)($r['is_system'] ?? 0) !== 1): ?>
                            <form method="POST" action="<?= e($base) ?>/manage/roles/<?= (int)$r['id'] ?>/delete" style="display:inline">
                                <button type="button" class="btn btn-danger" style="font-size:11px;padding:3px 10px"
                                    onclick="gcConfirm(this, <?= e(json_encode(t('roles.confirm_delete'), JSON_UNESCAPED_UNICODE)) ?>, <?= e(json_encode('«' . $r['label'] . '» — ' . t('admin.cannot_undo'), JSON_UNESCAPED_UNICODE)) ?>, <?= e(json_encode(t('admin.yes_delete'), JSON_UNESCAPED_UNICODE)) ?>)"><?= e(t('admin.delete')) ?></button>
                            </form>
                            <?php endif ?>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
