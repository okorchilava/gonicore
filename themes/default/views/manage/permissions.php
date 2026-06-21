<?php
$pageTitle     = t('perms.title');
$activeNav     = 'permissions';
$grouped       = $groupedPerms ?? [];
$permRoles     = $permRoles ?? [];
$topbarActions = '';
?>

<div class="card">
    <div class="card-header">
        <h3><?= e(t('perms.title')) ?></h3>
        <span style="font-size:12px;color:var(--muted)"><?= e(t('perms.subtitle')) ?></span>
    </div>
    <div class="card-body" style="padding:18px">
        <?php foreach ($grouped as $group => $perms): ?>
        <div style="margin-bottom:20px">
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);margin-bottom:8px;border-bottom:1px solid var(--border);padding-bottom:4px">
                <?= e(t('perms.group.' . $group)) ?>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width:30%"><?= e(t('perms.permission')) ?></th>
                            <th style="width:30%"><?= e(t('roles.name')) ?></th>
                            <th><?= e(t('perms.granted_to')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($perms as $p):
                            $pid   = (int)$p['id'];
                            $holders = $permRoles[$pid] ?? [];
                        ?>
                        <tr>
                            <td style="font-weight:600"><?= e((string)$p['label']) ?></td>
                            <td><code style="font-size:11px;color:var(--muted)"><?= e((string)$p['name']) ?></code></td>
                            <td>
                                <?php if (empty($holders)): ?>
                                <span style="color:var(--muted);opacity:.5">—</span>
                                <?php else: foreach ($holders as $h): ?>
                                <span style="display:inline-block;font-size:11px;background:rgba(16,178,124,.12);color:var(--accent);padding:2px 8px;border-radius:4px;margin:2px"><?= e((string)$h) ?></span>
                                <?php endforeach; endif ?>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach ?>
    </div>
</div>
