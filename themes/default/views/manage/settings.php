<?php
$pageTitle = t('settings.title');
$activeNav = 'settings';
$topbarActions = '';

$s = $settings ?? [];
$logo    = (string)($s['site_logo'] ?? '');
$favicon = (string)($s['site_favicon'] ?? '');
$mediaUrl = static fn(string $path): string => e($base) . '/storage/media/' . e($path);
?>

<style>
/* ── Settings tabs ─────────────────────────────────── */
.settings-tabs { display:flex; gap:2px; border-bottom:1px solid var(--border); margin-bottom:22px; overflow-x:auto; scrollbar-width:none; }
.settings-tabs::-webkit-scrollbar { display:none; }
.stab {
    display:inline-flex; align-items:center; gap:7px;
    padding:11px 16px; background:none; border:none; border-bottom:2px solid transparent;
    font-family:var(--font); font-size:13.5px; font-weight:600; color:var(--muted);
    cursor:pointer; white-space:nowrap; margin-bottom:-1px;
    transition:color .15s, border-color .15s;
}
.stab:hover { color:var(--text); }
.stab.active { color:var(--accent); border-bottom-color:var(--accent); }
.stab .material-symbols-outlined { font-size:18px; }
.stab-panel { display:none; }
.stab-panel.active { display:block; animation:stabfade .2s ease; }
@keyframes stabfade { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:none; } }
.settings-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media (max-width:720px){ .settings-grid-2 { grid-template-columns:1fr; } }

/* ── Branding (logo/favicon) ───────────────────────── */
.brand-row { display:flex; align-items:flex-start; gap:18px; flex-wrap:wrap; }
.brand-field { flex:1; min-width:240px; }
.brand-preview {
    display:flex; align-items:center; justify-content:center;
    min-width:96px; min-height:64px; padding:8px;
    border:1px solid var(--border); border-radius:10px; background:var(--bg);
}
.brand-preview img { max-height:48px; max-width:160px; object-fit:contain; display:block; }
.brand-preview.fav img { max-height:40px; max-width:40px; }
.brand-preview.empty { color:var(--muted); font-size:11px; text-align:center; }
.file-input {
    display:block; width:100%; font-size:13px; padding:9px 10px;
    border:1.5px dashed var(--border); border-radius:8px; background:var(--bg);
    cursor:pointer; color:var(--text);
}
.file-input::file-selector-button {
    border:none; background:var(--accent); color:#fff; font-weight:600;
    padding:6px 12px; border-radius:6px; margin-right:10px; cursor:pointer; font-family:var(--font);
}
.brand-remove { display:inline-flex; align-items:center; gap:7px; font-size:12.5px; color:var(--muted); margin-top:9px; cursor:pointer; }
.brand-remove input { accent-color:var(--danger); }

/* ── Toggles (reused) ──────────────────────────────── */
.gc-toggle{position:relative;display:inline-flex;align-items:center;cursor:pointer}
.gc-toggle input[type=checkbox]{position:absolute;opacity:0;width:0;height:0}
.gc-toggle-track{width:40px;height:22px;background:#cbd5e1;border-radius:11px;transition:background .2s;position:relative}
.gc-toggle input[type=checkbox]:checked~.gc-toggle-track{background:var(--accent)}
.gc-toggle-thumb{position:absolute;top:3px;left:3px;width:16px;height:16px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.gc-toggle input[type=checkbox]:checked~.gc-toggle-track .gc-toggle-thumb{transform:translateX(18px)}
</style>

<!-- ── Tab navigation ──────────────────────────────── -->
<div class="settings-tabs" role="tablist">
    <button type="button" class="stab active" data-tab="general"><span class="material-symbols-outlined">tune</span> General</button>
    <button type="button" class="stab" data-tab="reading"><span class="material-symbols-outlined">menu_book</span> Reading</button>
    <button type="button" class="stab" data-tab="datetime"><span class="material-symbols-outlined">schedule</span> Date &amp; Time</button>
    <button type="button" class="stab" data-tab="email"><span class="material-symbols-outlined">mail</span> Email</button>
    <button type="button" class="stab" data-tab="notifications"><span class="material-symbols-outlined">notifications</span> <?= e(t('settings.notifications')) ?></button>
    <button type="button" class="stab" data-tab="security"><span class="material-symbols-outlined">lock</span> Security</button>
</div>

<form method="POST" action="<?= e($base) ?>/manage/settings" enctype="multipart/form-data">

    <!-- ═══ GENERAL ═══ -->
    <div class="stab-panel active" id="tab-general">
        <div class="card">
            <div class="card-header"><h3><span class="material-symbols-outlined mi-sm">tune</span> General</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label"><?= e(t('settings.site_name')) ?></label>
                    <input type="text" name="site_name" class="form-input"
                           value="<?= e((string)($s['site_name'] ?? 'GoniCore')) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('settings.site_tagline')) ?></label>
                    <input type="text" name="site_tagline" class="form-input"
                           value="<?= e((string)($s['site_tagline'] ?? '')) ?>">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label"><?= e(t('settings.site_url')) ?></label>
                    <input type="url" name="site_url" class="form-input"
                           value="<?= e((string)($s['site_url'] ?? '')) ?>"
                           placeholder="https://example.com">
                    <div style="font-size:12px;color:var(--muted);margin-top:5px">Used for canonical links and feeds.</div>
                </div>
            </div>
        </div>

        <!-- Branding: Logo & Favicon -->
        <div class="card" style="margin-top:18px">
            <div class="card-header"><h3><span class="material-symbols-outlined mi-sm">image</span> Logo &amp; Favicon</h3></div>
            <div class="card-body">
                <div class="brand-row">
                    <!-- Logo -->
                    <div class="brand-field">
                        <label class="form-label">Site logo</label>
                        <div style="display:flex;align-items:center;gap:14px">
                            <div class="brand-preview <?= $logo === '' ? 'empty' : '' ?>">
                                <?php if ($logo !== ''): ?>
                                    <img src="<?= $mediaUrl($logo) ?>" alt="Logo">
                                <?php else: ?>None<?php endif ?>
                            </div>
                            <div style="flex:1">
                                <input type="file" name="site_logo" class="file-input"
                                       accept="image/png,image/jpeg,image/gif,image/webp">
                                <div style="font-size:12px;color:var(--muted);margin-top:6px">PNG, JPG, GIF or WebP. Transparent PNG recommended. Shown in the site header.</div>
                                <?php if ($logo !== ''): ?>
                                <label class="brand-remove"><input type="checkbox" name="remove_logo" value="1"> Remove current logo</label>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>

                    <!-- Favicon -->
                    <div class="brand-field">
                        <label class="form-label">Favicon</label>
                        <div style="display:flex;align-items:center;gap:14px">
                            <div class="brand-preview fav <?= $favicon === '' ? 'empty' : '' ?>">
                                <?php if ($favicon !== ''): ?>
                                    <img src="<?= $mediaUrl($favicon) ?>" alt="Favicon">
                                <?php else: ?>None<?php endif ?>
                            </div>
                            <div style="flex:1">
                                <input type="file" name="site_favicon" class="file-input"
                                       accept="image/png,image/jpeg,image/gif,image/webp">
                                <div style="font-size:12px;color:var(--muted);margin-top:6px">Square PNG recommended (e.g. 32×32 or 64×64). <code>.ico</code> is not supported — use PNG.</div>
                                <?php if ($favicon !== ''): ?>
                                <label class="brand-remove"><input type="checkbox" name="remove_favicon" value="1"> Remove current favicon</label>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ READING ═══ -->
    <div class="stab-panel" id="tab-reading">
        <div class="card">
            <div class="card-header"><h3><span class="material-symbols-outlined mi-sm">menu_book</span> Reading</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Homepage shows</label>
                    <div style="display:flex;flex-direction:column;gap:10px;margin-top:4px">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:14px">
                            <input type="radio" name="homepage_type" value="posts"
                                   <?= (($s['homepage_type'] ?? 'posts') === 'posts') ? 'checked' : '' ?>
                                   id="hp-posts" onchange="togglePageSelect()"
                                   style="accent-color:var(--accent)">
                            <div>
                                <div style="font-weight:500">Latest posts</div>
                                <div style="font-size:12px;color:var(--muted)">Show the blog post list</div>
                            </div>
                        </label>
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:14px">
                            <input type="radio" name="homepage_type" value="page"
                                   <?= (($s['homepage_type'] ?? 'posts') === 'page') ? 'checked' : '' ?>
                                   id="hp-page" onchange="togglePageSelect()"
                                   style="accent-color:var(--accent)">
                            <div>
                                <div style="font-weight:500">A static page</div>
                                <div style="font-size:12px;color:var(--muted)">Show a specific post/page</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div id="page-select-group" style="<?= (($s['homepage_type'] ?? 'posts') !== 'page') ? 'display:none' : '' ?>">
                    <div class="form-group">
                        <label class="form-label">Homepage page</label>
                        <select name="homepage_page_id" class="form-select">
                            <option value="">— Select a page —</option>
                            <?php foreach ($allPosts ?? [] as $p): ?>
                            <option value="<?= (int)$p['id'] ?>"
                                <?= ((string)($s['homepage_page_id'] ?? '') === (string)$p['id']) ? 'selected' : '' ?>>
                                <?= e($p['title']) ?>
                            </option>
                            <?php endforeach ?>
                        </select>
                        <div style="font-size:12px;color:var(--muted);margin-top:5px">Shown at the root URL <code>/</code></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Posts page</label>
                        <select name="posts_page_id" class="form-select">
                            <option value="">— None —</option>
                            <?php foreach ($allPosts ?? [] as $p): ?>
                            <option value="<?= (int)$p['id'] ?>"
                                <?= ((string)($s['posts_page_id'] ?? '') === (string)$p['id']) ? 'selected' : '' ?>>
                                <?= e($p['title']) ?>
                            </option>
                            <?php endforeach ?>
                        </select>
                        <div style="font-size:12px;color:var(--muted);margin-top:5px">Visiting this page's URL shows the blog listing.</div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label"><?= e(t('settings.posts_per_page')) ?></label>
                    <input type="number" name="posts_per_page" class="form-input"
                           value="<?= (int)($s['posts_per_page'] ?? 9) ?>"
                           min="1" max="100" style="max-width:120px">
                    <div style="font-size:12px;color:var(--muted);margin-top:5px">Number of posts shown on the blog listing page.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ DATE & TIME ═══ -->
    <div class="stab-panel" id="tab-datetime">
        <div class="card">
            <div class="card-header"><h3><span class="material-symbols-outlined mi-sm">schedule</span> Date &amp; Time</h3></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label"><?= e(t('settings.timezone')) ?></label>
                        <select name="timezone" class="form-select">
                            <?php
                            $regions = [
                                'Africa', 'America', 'Antarctica', 'Arctic', 'Asia',
                                'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific', 'UTC'
                            ];
                            $currentTz = (string)($s['timezone'] ?? 'Asia/Tbilisi');
                            $tzList = \DateTimeZone::listIdentifiers();
                            $grouped = [];
                            foreach ($tzList as $tz) {
                                $parts = explode('/', $tz, 2);
                                $group = in_array($parts[0], $regions) ? $parts[0] : 'Other';
                                $grouped[$group][] = $tz;
                            }
                            ksort($grouped);
                            foreach ($grouped as $group => $zones): ?>
                            <optgroup label="<?= e($group) ?>">
                                <?php foreach ($zones as $tz): ?>
                                <option value="<?= e($tz) ?>" <?= $tz === $currentTz ? 'selected' : '' ?>>
                                    <?= e(str_replace('_', ' ', $tz)) ?>
                                </option>
                                <?php endforeach ?>
                            </optgroup>
                            <?php endforeach ?>
                        </select>
                        <div style="font-size:12px;color:var(--muted);margin-top:5px">
                            Current time: <strong id="current-time"><?= date('Y-m-d H:i:s') ?></strong>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label"><?= e(t('settings.date_format')) ?></label>
                        <select name="date_format" class="form-select">
                            <?php
                            $formats = [
                                'M j, Y'  => date('M j, Y'),
                                'd.m.Y'   => date('d.m.Y'),
                                'Y-m-d'   => date('Y-m-d'),
                                'd/m/Y'   => date('d/m/Y'),
                                'F j, Y'  => date('F j, Y'),
                                'j F Y'   => date('j F Y'),
                            ];
                            $curFmt = (string)($s['date_format'] ?? 'M j, Y');
                            foreach ($formats as $fmt => $preview): ?>
                            <option value="<?= e($fmt) ?>" <?= $fmt === $curFmt ? 'selected' : '' ?>>
                                <?= e($preview) ?> (<?= e($fmt) ?>)
                            </option>
                            <?php endforeach ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label"><?= e(t('settings.time_format')) ?></label>
                        <select name="time_format" class="form-select">
                            <?php
                            $timeFmts = [
                                'H:i'    => date('H:i')    . ' (24h)',
                                'H:i:s'  => date('H:i:s')  . ' (24h with seconds)',
                                'g:i A'  => date('g:i A')  . ' (12h AM/PM)',
                                'g:i:s A'=> date('g:i:s A').' (12h with seconds)',
                            ];
                            $curTFmt = (string)($s['time_format'] ?? 'H:i');
                            foreach ($timeFmts as $fmt => $label): ?>
                            <option value="<?= e($fmt) ?>" <?= $fmt === $curTFmt ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ EMAIL ═══ -->
    <div class="stab-panel" id="tab-email">
        <div class="card">
            <div class="card-header"><h3><span class="material-symbols-outlined mi-sm">mail</span> Email (SMTP)</h3></div>
            <div class="card-body">
                <div class="settings-grid-2">
                    <div class="form-group">
                        <label class="form-label"><?= e(t('settings.admin_email')) ?></label>
                        <input type="email" name="admin_email" class="form-input"
                               value="<?= e((string)($s['admin_email'] ?? '')) ?>"
                               placeholder="admin@example.com">
                        <div style="font-size:12px;color:var(--muted);margin-top:4px">All notifications go here.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('settings.mail_from')) ?></label>
                        <input type="email" name="mail_from_address" class="form-input"
                               value="<?= e((string)($s['mail_from_address'] ?? '')) ?>"
                               placeholder="noreply@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('settings.mail_from_name')) ?></label>
                        <input type="text" name="mail_from_name" class="form-input"
                               value="<?= e((string)($s['mail_from_name'] ?? 'GoniCore')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Driver</label>
                        <select name="mail_driver" class="form-select" id="mailDriverSelect"
                                onchange="document.getElementById('smtpFields').style.display=this.value==='smtp'?'block':'none'">
                            <option value="php"  <?= ($s['mail_driver']??'php')==='php'  ? 'selected' : '' ?>>PHP mail()</option>
                            <option value="smtp" <?= ($s['mail_driver']??'php')==='smtp' ? 'selected' : '' ?>>SMTP</option>
                        </select>
                    </div>
                </div>
                <div id="smtpFields" style="margin-top:6px;<?= ($s['mail_driver']??'php')!=='smtp'?'display:none':'' ?>">
                    <div class="form-group">
                        <label class="form-label"><?= e(t('settings.smtp_host')) ?></label>
                        <input type="text" name="mail_smtp_host" class="form-input"
                               value="<?= e((string)($s['mail_smtp_host'] ?? '')) ?>" placeholder="smtp.gmail.com">
                    </div>
                    <div class="settings-grid-2">
                        <div class="form-group">
                            <label class="form-label"><?= e(t('settings.smtp_port')) ?></label>
                            <input type="number" name="mail_smtp_port" class="form-input"
                                   value="<?= (int)($s['mail_smtp_port'] ?? 587) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= e(t('settings.smtp_encryption')) ?></label>
                            <select name="mail_smtp_encryption" class="form-select">
                                <?php foreach (['tls'=>'TLS','ssl'=>'SSL',''=>'None'] as $v=>$l): ?>
                                <option value="<?= e($v) ?>" <?= ($s['mail_smtp_encryption']??'tls')===$v?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('settings.smtp_user')) ?></label>
                        <input type="text" name="mail_smtp_user" class="form-input"
                               value="<?= e((string)($s['mail_smtp_user'] ?? '')) ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label"><?= e(t('settings.smtp_pass')) ?></label>
                        <input type="password" name="mail_smtp_pass" class="form-input"
                               value="<?= e((string)($s['mail_smtp_pass'] ?? '')) ?>"
                               autocomplete="new-password">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ NOTIFICATIONS ═══ -->
    <div class="stab-panel" id="tab-notifications">
        <div class="card">
            <div class="card-header"><h3><span class="material-symbols-outlined mi-sm">notifications</span> <?= e(t('settings.notifications')) ?></h3></div>
            <div class="card-body">
                <p style="font-size:13px;color:var(--muted);margin-bottom:16px">
                    Choose which events trigger an admin email.
                </p>
                <?php foreach ([
                    'notify_post_new'      => [t('settings.notify_new_post'), ''],
                    'notify_user_register' => [t('settings.notify_new_user'), ''],
                    'notify_comment_new'   => ['New comment', 'Reserved — usable by comment plugins'],
                ] as $key => [$label, $hint]): ?>
                <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:10px 0;border-bottom:1px solid var(--border)">
                    <div>
                        <div style="font-size:14px;font-weight:600;color:var(--text)"><?= $label ?></div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px"><?= $hint ?></div>
                    </div>
                    <label class="gc-toggle" style="flex-shrink:0;margin-left:16px">
                        <input type="hidden"   name="<?= $key ?>" value="0">
                        <input type="checkbox" name="<?= $key ?>" value="1"
                               <?= ($s[$key] ?? '1') === '1' ? 'checked' : '' ?>>
                        <span class="gc-toggle-track"><span class="gc-toggle-thumb"></span></span>
                    </label>
                </div>
                <?php endforeach ?>
                <p style="font-size:12px;color:var(--muted);margin-top:16px">
                    Plugins (Store, Booking, etc.) can register their own events using the
                    <code style="background:var(--bg);padding:1px 5px;border-radius:3px">admin.notify</code> hook.
                </p>
            </div>
        </div>
    </div>

    <!-- ═══ SECURITY ═══ -->
    <div class="stab-panel" id="tab-security">
        <div class="card">
            <div class="card-header"><h3><span class="material-symbols-outlined mi-sm">lock</span> Security</h3></div>
            <div class="card-body">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Session Lifetime (minutes)</label>
                    <input type="number" name="session_lifetime" class="form-input"
                           value="<?= (int)($s['session_lifetime'] ?? 120) ?>"
                           min="5" max="10080" style="max-width:160px">
                    <div style="font-size:12px;color:var(--muted);margin-top:5px">
                        Inactive sessions expire after this many minutes (5 – 10080).
                        Takes effect on next login.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:22px">
        <button type="submit" class="btn btn-primary" style="padding:11px 32px;font-size:15px"><?= e(t('settings.save_settings')) ?></button>
    </div>
</form>

<script>
/* ── Tab switching (active tab persisted across the save redirect) ── */
(function () {
    var tabs   = document.querySelectorAll('.stab');
    var panels = document.querySelectorAll('.stab-panel');
    function activate(name) {
        var found = false;
        panels.forEach(function (p) { var on = p.id === 'tab-' + name; p.classList.toggle('active', on); if (on) found = true; });
        if (!found) { name = 'general'; document.getElementById('tab-general').classList.add('active'); }
        tabs.forEach(function (t) { t.classList.toggle('active', t.dataset.tab === name); });
        try { localStorage.setItem('gcSettingsTab', name); } catch (e) {}
    }
    tabs.forEach(function (t) { t.addEventListener('click', function () { activate(t.dataset.tab); }); });
    var saved = null; try { saved = localStorage.getItem('gcSettingsTab'); } catch (e) {}
    if (saved) activate(saved);
})();

/* ── Reading: toggle the page selector ── */
function togglePageSelect() {
    var g = document.getElementById('page-select-group');
    if (g) g.style.display = document.getElementById('hp-page').checked ? '' : 'none';
}

/* ── Live clock ── */
setInterval(function () {
    var el = document.getElementById('current-time');
    if (el) el.textContent = new Date().toLocaleString();
}, 1000);
</script>
