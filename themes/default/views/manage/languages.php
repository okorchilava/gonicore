<?php
$pageTitle = 'Languages';
$activeNav = 'languages';
$topbarActions = '';
?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">

    <!-- Language list -->
    <div class="card">
        <div class="card-header"><h3>Active Languages</h3></div>
        <div class="table-wrap">
            <?php if (!empty($languages)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Language</th>
                        <th>Code</th>
                        <th>Status</th>
                        <th>Default</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($languages as $lang): ?>
                    <tr>
                        <td>
                            <span style="font-size:20px;vertical-align:middle;margin-right:4px"><?= e((string)($lang['flag'] ?? '🌐')) ?></span>
                            <strong><?= e((string)$lang['name']) ?></strong>
                            <span style="color:var(--muted);font-size:12px;margin-left:6px"><?= e((string)$lang['native']) ?></span>
                        </td>
                        <td><code style="background:var(--bg);padding:2px 7px;border-radius:4px;font-size:12px"><?= e((string)$lang['code']) ?></code></td>
                        <td>
                            <span class="badge <?= $lang['is_active'] ? 'published' : 'archived' ?>">
                                <?= $lang['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($lang['is_default']): ?>
                            <span class="badge admin">Default</span>
                            <?php else: ?>
                            <form method="POST" action="<?= e($base) ?>/manage/languages/<?= e((string)$lang['code']) ?>/default" style="display:inline">
                                <button type="submit" class="btn btn-ghost" style="font-size:11px;padding:3px 8px">Set default</button>
                            </form>
                            <?php endif ?>
                        </td>
                        <td style="text-align:right;white-space:nowrap">
                            <a href="<?= e($base) ?>/manage/languages/<?= e((string)$lang['code']) ?>/edit"
                               class="btn btn-ghost" style="font-size:11px;padding:3px 8px;margin-right:4px">Edit</a>
                            <?php if (!$lang['is_default']): ?>
                            <a href="<?= e($base) ?>/manage/languages/<?= e((string)$lang['code']) ?>/file"
                               class="btn btn-ghost" style="font-size:11px;padding:3px 8px;margin-right:4px;border-color:var(--accent);color:var(--accent)">🌐 Translate</a>
                            <?php endif ?>
                            <form method="POST" action="<?= e($base) ?>/manage/languages/<?= e((string)$lang['code']) ?>/toggle" style="display:inline">
                                <button type="submit" class="btn btn-ghost" style="font-size:11px;padding:3px 8px;margin-right:4px">
                                    <?= $lang['is_active'] ? 'Disable' : 'Enable' ?>
                                </button>
                            </form>
                            <?php if (!$lang['is_default']): ?>
                            <form method="POST" action="<?= e($base) ?>/manage/languages/<?= e((string)$lang['code']) ?>/delete" style="display:inline">
                                <button type="button" class="btn btn-danger" style="font-size:11px;padding:3px 8px"
                                    onclick="gcConfirm(this,'Delete language?','All translations for &quot;<?= e((string)$lang['name']) ?>&quot; will be permanently lost.','Delete')">
                                    Delete
                                </button>
                            </form>
                            <?php endif ?>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty"><div class="empty-icon">🌐</div><h3>No languages</h3></div>
            <?php endif ?>
        </div>
    </div>

    <!-- Add language -->
    <div class="card">
        <div class="card-header"><h3>Add Language</h3></div>
        <div class="card-body">
            <form method="POST" action="<?= e($base) ?>/manage/languages">
                <div class="form-group">
                    <label class="form-label">Code <span style="color:var(--muted);font-weight:400">(e.g. fr, de, ru)</span></label>
                    <input type="text" name="code" class="form-input" placeholder="en" maxlength="10" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Name <span style="color:var(--muted);font-weight:400">(English)</span></label>
                    <input type="text" name="name" class="form-input" placeholder="French" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Native name</label>
                    <input type="text" name="native" class="form-input" placeholder="Français" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Flag emoji</label>
                    <input type="text" name="flag" class="form-input" placeholder="🇫🇷" maxlength="10" value="🌐">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;padding:10px;font-size:14px">Add Language</button>
            </form>
        </div>
    </div>

</div>
