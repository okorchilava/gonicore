<?php
/**
 * Maintenance Mode — admin settings.
 * Vars: $base, $enabled (bool), $title, $message
 */
$pageTitle = 'Maintenance Mode';
$activeNav = 'maintenance';
?>
<div style="max-width:720px">
    <div class="card">
        <div class="card-header"><h3><span class="material-symbols-outlined mi-sm" style="vertical-align:-3px">construction</span> Maintenance Mode</h3></div>
        <div class="card-body">
            <form method="POST" action="<?= e($base) ?>/manage/maintenance">

                <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:6px 0 16px;border-bottom:1px solid var(--border)">
                    <div>
                        <div style="font-size:14px;font-weight:700">Enable maintenance mode</div>
                        <div style="font-size:12.5px;color:var(--muted);margin-top:3px;max-width:440px">
                            Visitors see a 503 “under maintenance” page. Logged-in admins, the admin
                            panel (<code>/manage</code>) and the login page stay accessible.
                        </div>
                    </div>
                    <label class="gc-toggle" style="flex-shrink:0;margin-left:16px">
                        <input type="checkbox" name="maintenance_enabled" value="1" <?= $enabled ? 'checked' : '' ?>>
                        <span class="gc-toggle-track"><span class="gc-toggle-thumb"></span></span>
                    </label>
                </div>

                <div class="form-group" style="margin-top:16px">
                    <label class="form-label">Page title</label>
                    <input type="text" name="maintenance_title" class="form-input" maxlength="255"
                           value="<?= e($title) ?>"
                           placeholder="<?= e(\GCMaintenance\MaintenanceAdmin::DEFAULT_TITLE) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Message</label>
                    <textarea name="maintenance_message" class="form-textarea" style="min-height:120px"
                              placeholder="<?= e(\GCMaintenance\MaintenanceAdmin::DEFAULT_MESSAGE) ?>"><?= e($message) ?></textarea>
                    <div style="font-size:12px;color:var(--muted);margin-top:5px">Shown on the public page. Line breaks are preserved.</div>
                </div>

                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                    <button type="submit" class="btn btn-primary" style="padding:10px 26px">Save</button>
                    <?php if ($enabled): ?>
                    <span style="color:#ef4444;font-weight:700;font-size:13px">● Maintenance mode is ON</span>
                    <?php else: ?>
                    <span style="color:#10b981;font-weight:700;font-size:13px">● Site is live</span>
                    <?php endif ?>
                    <a href="<?= e($base) ?>/" target="_blank" class="btn btn-ghost" style="margin-left:auto">Preview site ↗</a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.gc-toggle{position:relative;display:inline-flex;align-items:center;cursor:pointer}
.gc-toggle input[type=checkbox]{position:absolute;opacity:0;width:0;height:0}
.gc-toggle-track{width:44px;height:24px;background:#cbd5e1;border-radius:12px;transition:background .2s;position:relative}
.gc-toggle input[type=checkbox]:checked~.gc-toggle-track{background:var(--accent)}
.gc-toggle-thumb{position:absolute;top:3px;left:3px;width:18px;height:18px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.gc-toggle input[type=checkbox]:checked~.gc-toggle-track .gc-toggle-thumb{transform:translateX(20px)}
</style>
