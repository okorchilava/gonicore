<?php
$pageTitle = 'Settings';
$activeNav = 'settings';
$topbarActions = '';

$s = $settings ?? [];
$saved = $saved ?? false;
?>

<?php if ($saved): ?>
<div id="gc-flash" data-msg="Settings saved successfully." data-icon="success" style="display:none"></div>
<?php endif ?>

<form method="POST" action="<?= e($base) ?>/manage/settings">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

    <!-- General -->
    <div class="card">
        <div class="card-header"><h3>🌐 General</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Site Name</label>
                <input type="text" name="site_name" class="form-input"
                       value="<?= e((string)($s['site_name'] ?? 'GoniCore')) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Tagline</label>
                <input type="text" name="site_tagline" class="form-input"
                       value="<?= e((string)($s['site_tagline'] ?? '')) ?>"
                       placeholder="A brief description of your site">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Site URL</label>
                <input type="url" name="site_url" class="form-input"
                       value="<?= e((string)($s['site_url'] ?? '')) ?>"
                       placeholder="https://example.com">
                <div style="font-size:12px;color:var(--muted);margin-top:5px">Used for canonical links and feeds.</div>
            </div>
        </div>
    </div>

    <!-- Reading -->
    <div class="card">
        <div class="card-header"><h3>📖 Reading</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Homepage shows</label>
                <div style="display:flex;flex-direction:column;gap:10px;margin-top:4px">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:14px" id="hp-posts-label">
                        <input type="radio" name="homepage_type" value="posts"
                               <?= (($s['homepage_type'] ?? 'posts') === 'posts') ? 'checked' : '' ?>
                               id="hp-posts" onchange="togglePageSelect()"
                               style="accent-color:var(--accent)">
                        <div>
                            <div style="font-weight:500">Latest posts</div>
                            <div style="font-size:12px;color:var(--muted)">Show the blog post list</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:14px" id="hp-page-label">
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
                <label class="form-label">Posts per page</label>
                <input type="number" name="posts_per_page" class="form-input"
                       value="<?= (int)($s['posts_per_page'] ?? 9) ?>"
                       min="1" max="100" style="max-width:120px">
                <div style="font-size:12px;color:var(--muted);margin-top:5px">Number of posts shown on the blog listing page.</div>
            </div>
        </div>
    </div>

    <!-- Date & Time -->
    <div class="card" style="grid-column:span 2">
        <div class="card-header"><h3>🕐 Date &amp; Time</h3></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Timezone</label>
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
                    <label class="form-label">Date Format</label>
                    <select name="date_format" class="form-select" onchange="updatePreview()">
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
                    <label class="form-label">Time Format</label>
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

<!-- Mail / Notifications -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;margin-top:20px">

    <!-- Mail settings -->
    <div class="card">
        <div class="card-header"><h3>📧 Email (SMTP)</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Admin Email</label>
                <input type="email" name="admin_email" class="form-input"
                       value="<?= e((string)($s['admin_email'] ?? '')) ?>"
                       placeholder="admin@example.com">
                <div style="font-size:12px;color:var(--muted);margin-top:4px">All notifications go here.</div>
            </div>
            <div class="form-group">
                <label class="form-label">From Address</label>
                <input type="email" name="mail_from_address" class="form-input"
                       value="<?= e((string)($s['mail_from_address'] ?? '')) ?>"
                       placeholder="noreply@example.com">
            </div>
            <div class="form-group">
                <label class="form-label">From Name</label>
                <input type="text" name="mail_from_name" class="form-input"
                       value="<?= e((string)($s['mail_from_name'] ?? 'GoniCore')) ?>">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Driver</label>
                <select name="mail_driver" class="form-select" id="mailDriverSelect"
                        onchange="document.getElementById('smtpFields').style.display=this.value==='smtp'?'block':'none'">
                    <option value="php"  <?= ($s['mail_driver']??'php')==='php'  ? 'selected' : '' ?>>PHP mail()</option>
                    <option value="smtp" <?= ($s['mail_driver']??'php')==='smtp' ? 'selected' : '' ?>>SMTP</option>
                </select>
            </div>
            <div id="smtpFields" style="margin-top:14px;<?= ($s['mail_driver']??'php')!=='smtp'?'display:none':'' ?>">
                <div class="form-group">
                    <label class="form-label">SMTP Host</label>
                    <input type="text" name="mail_smtp_host" class="form-input"
                           value="<?= e((string)($s['mail_smtp_host'] ?? '')) ?>" placeholder="smtp.gmail.com">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Port</label>
                        <input type="number" name="mail_smtp_port" class="form-input"
                               value="<?= (int)($s['mail_smtp_port'] ?? 587) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Encryption</label>
                        <select name="mail_smtp_encryption" class="form-select">
                            <?php foreach (['tls'=>'TLS','ssl'=>'SSL',''=>'None'] as $v=>$l): ?>
                            <option value="<?= e($v) ?>" <?= ($s['mail_smtp_encryption']??'tls')===$v?'selected':'' ?>><?= $l ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="mail_smtp_user" class="form-input"
                           value="<?= e((string)($s['mail_smtp_user'] ?? '')) ?>">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Password</label>
                    <input type="password" name="mail_smtp_pass" class="form-input"
                           value="<?= e((string)($s['mail_smtp_pass'] ?? '')) ?>"
                           autocomplete="new-password">
                </div>
            </div>
        </div>
    </div>

    <!-- Notification events -->
    <div class="card">
        <div class="card-header"><h3>🔔 Notifications</h3></div>
        <div class="card-body">
            <p style="font-size:13px;color:var(--muted);margin-bottom:16px">
                Choose which events trigger an admin email.
            </p>
            <?php foreach ([
                'notify_post_new'      => ['New post created',      'Sent when any post or page is created'],
                'notify_user_register' => ['New user registered',   'Sent when a new user account is created'],
                'notify_comment_new'   => ['New comment',           'Reserved — usable by comment plugins'],
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

<div style="margin-top:20px">
    <button type="submit" class="btn btn-primary" style="padding:11px 32px;font-size:15px">Save Settings</button>
</div>
</form>
<style>
.gc-toggle{position:relative;display:inline-flex;align-items:center;cursor:pointer}
.gc-toggle input[type=checkbox]{position:absolute;opacity:0;width:0;height:0}
.gc-toggle input[type=hidden]+input[type=checkbox]{position:absolute}
.gc-toggle-track{width:40px;height:22px;background:#cbd5e1;border-radius:11px;transition:background .2s;position:relative}
.gc-toggle input[type=checkbox]:checked~.gc-toggle-track{background:var(--accent)}
.gc-toggle-thumb{position:absolute;top:3px;left:3px;width:16px;height:16px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.gc-toggle input[type=checkbox]:checked~.gc-toggle-track .gc-toggle-thumb{transform:translateX(18px)}
</style>

<script>
function togglePageSelect() {
    var g = document.getElementById('page-select-group');
    if (g) g.style.display = document.getElementById('hp-page').checked ? '' : 'none';
}

// Live clock
setInterval(function() {
    var el = document.getElementById('current-time');
    if (el) el.textContent = new Date().toLocaleString();
}, 1000);
</script>
