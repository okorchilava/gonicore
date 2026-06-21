<?php
$pageTitle = t('plugins.title');
$activeNav = 'plugins';
ob_start(); ?>
<label for="pluginUpload" class="topbar-btn" style="cursor:pointer"><?= e(t('plugins.upload')) ?></label>
<?php $topbarActions = ob_get_clean(); ?>

<!-- Upload form (hidden, triggered by label) -->
<form method="POST" action="<?= e($base) ?>/manage/plugins/upload"
      enctype="multipart/form-data" id="uploadForm" style="display:none">
    <input type="file" id="pluginUpload" name="plugin_zip" accept=".zip"
           onchange="document.getElementById('uploadForm').submit()">
</form>

<div class="card">
    <?php if (!empty($plugins)): ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:35%"><?= e(t('plugins.plugin_col')) ?></th>
                    <th><?= e(t('plugins.version')) ?></th>
                    <th><?= e(t('plugins.author')) ?></th>
                    <th><?= e(t('plugins.status')) ?></th>
                    <th style="text-align:right"><?= e(t('plugins.actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plugins as $pl): ?>
                <tr>
                    <td>
                        <div style="font-weight:600"><?= e($pl['name']) ?></div>
                        <?php if ($pl['description']): ?>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px"><?= e($pl['description']) ?></div>
                        <?php endif ?>
                        <div style="font-size:11px;color:var(--muted);margin-top:2px;font-family:monospace"><?= e($pl['slug']) ?></div>
                    </td>
                    <td style="font-size:13px;color:var(--muted)"><?= e($pl['version']) ?></td>
                    <td style="font-size:13px;color:var(--muted)"><?= e($pl['author']) ?></td>
                    <td>
                        <?php if (!$pl['has_bootstrap']): ?>
                        <span class="badge archived"><?= e(t('plugins.no_bootstrap')) ?></span>
                        <?php elseif ($pl['active']): ?>
                        <span class="badge published"><?= e(t('plugins.active')) ?></span>
                        <?php else: ?>
                        <span class="badge draft"><?= e(t('plugins.inactive')) ?></span>
                        <?php endif ?>
                    </td>
                    <td style="text-align:right;white-space:nowrap">
                        <?php if ($pl['has_bootstrap']): ?>
                        <form method="POST" action="<?= e($base) ?>/manage/plugins/<?= e($pl['slug']) ?>/<?= $pl['active'] ? 'deactivate' : 'activate' ?>" style="display:inline">
                            <button type="submit" class="btn btn-ghost" style="font-size:11px;padding:4px 10px;margin-right:4px">
                                <?= $pl['active'] ? e(t('plugins.deactivate')) : e(t('plugins.activate')) ?>
                            </button>
                        </form>
                        <?php endif ?>
                        <form method="POST" action="<?= e($base) ?>/manage/plugins/<?= e($pl['slug']) ?>/delete" style="display:inline">
                            <button type="button" class="btn btn-danger" style="font-size:11px;padding:4px 10px"
                                onclick="gcConfirm(this, <?= e(json_encode(t('plugins.confirm_delete'), JSON_UNESCAPED_UNICODE)) ?>, <?= e(json_encode('«' . $pl['name'] . '» — ' . t('plugins.confirm_delete_msg') . ' ' . t('plugins.confirm_delete_data'), JSON_UNESCAPED_UNICODE)) ?>, <?= e(json_encode(t('admin.yes_delete'), JSON_UNESCAPED_UNICODE)) ?>)">
                                <?= e(t('plugins.delete')) ?>
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
        <div class="empty-icon"><span class="material-symbols-outlined" style="font-size:36px">extension</span></div>
        <h3><?= e(t('plugins.no_plugins')) ?></h3>
        <p style="margin-bottom:20px"><?= e(t('plugins.no_plugins_sub')) ?></p>
        <label for="pluginUpload" class="btn btn-primary" style="cursor:pointer"><?= e(t('plugins.upload')) ?></label>
    </div>
    <?php endif ?>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-header"><h3><span class="material-symbols-outlined mi-sm">inventory_2</span> <?= e(t('plugins.structure')) ?></h3></div>
    <div class="card-body">
        <p style="font-size:13px;color:var(--muted);margin-bottom:12px">Each plugin is a ZIP containing a folder with the following structure:</p>
        <pre style="background:var(--bg);border-radius:8px;padding:14px 16px;font-size:13px;color:var(--text);overflow-x:auto">my-plugin/
├── bootstrap.php   <span style="color:var(--accent)">← Required. Auto-loaded on every request.</span>
├── plugin.json     <span style="color:var(--muted)">← Optional metadata</span>
└── src/</pre>
        <p style="font-size:12px;color:var(--muted);margin-top:10px">
            <strong>plugin.json</strong> example:
            <code style="background:var(--bg);padding:2px 6px;border-radius:4px;font-size:11px">{"name":"My Plugin","description":"...","version":"1.0.0","author":"You"}</code>
        </p>
        <p style="font-size:12px;color:var(--muted);margin-top:6px">
            Deactivated plugins won't load but files remain. Changes take effect on next request.
        </p>
    </div>
</div>
