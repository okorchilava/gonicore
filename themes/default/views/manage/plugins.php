<?php
$pageTitle = 'Plugins';
$activeNav = 'plugins';
ob_start(); ?>
<label for="pluginUpload" class="topbar-btn" style="cursor:pointer">⬆ Upload Plugin</label>
<?php $topbarActions = ob_get_clean(); ?>

<!-- Upload form (hidden, triggered by label) -->
<form method="POST" action="<?= e($base) ?>/manage/plugins/upload"
      enctype="multipart/form-data" id="uploadForm" style="display:none">
    <input type="file" id="pluginUpload" name="plugin_zip" accept=".zip"
           onchange="document.getElementById('uploadForm').submit()">
</form>

<?php if (!empty($uploadError ?? null)): ?>
<div id="gc-flash" data-msg="<?= e($uploadError) ?>" data-icon="error" style="display:none"></div>
<?php endif ?>
<?php if (!empty($uploadSuccess ?? null)): ?>
<div id="gc-flash" data-msg="Plugin uploaded successfully." data-icon="success" style="display:none"></div>
<?php endif ?>

<div class="card">
    <?php if (!empty($plugins)): ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:35%">Plugin</th>
                    <th>Version</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th style="text-align:right">Actions</th>
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
                        <span class="badge archived">No bootstrap</span>
                        <?php elseif ($pl['active']): ?>
                        <span class="badge published">Active</span>
                        <?php else: ?>
                        <span class="badge draft">Inactive</span>
                        <?php endif ?>
                    </td>
                    <td style="text-align:right;white-space:nowrap">
                        <?php if ($pl['has_bootstrap']): ?>
                        <form method="POST" action="<?= e($base) ?>/manage/plugins/<?= e($pl['slug']) ?>/<?= $pl['active'] ? 'deactivate' : 'activate' ?>" style="display:inline">
                            <button type="submit" class="btn btn-ghost" style="font-size:11px;padding:4px 10px;margin-right:4px">
                                <?= $pl['active'] ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                        <?php endif ?>
                        <form method="POST" action="<?= e($base) ?>/manage/plugins/<?= e($pl['slug']) ?>/delete" style="display:inline">
                            <button type="button" class="btn btn-danger" style="font-size:11px;padding:4px 10px"
                                onclick="gcConfirm(this,'Delete plugin?','Plugin &quot;<?= e(addslashes($pl['name'])) ?>&quot; and all its files will be permanently removed.','Delete')">
                                Delete
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
        <div class="empty-icon">🧩</div>
        <h3>No plugins installed</h3>
        <p style="margin-bottom:20px">Upload a plugin ZIP to extend GoniCore.</p>
        <label for="pluginUpload" class="btn btn-primary" style="cursor:pointer">⬆ Upload Plugin</label>
    </div>
    <?php endif ?>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-header"><h3>📦 Plugin Structure</h3></div>
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
