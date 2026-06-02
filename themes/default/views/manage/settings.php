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

<div style="margin-top:20px">
    <button type="submit" class="btn btn-primary" style="padding:11px 32px;font-size:15px">Save Settings</button>
</div>
</form>

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
