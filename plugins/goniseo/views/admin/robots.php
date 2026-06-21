<?php
$pageTitle     = 'GoniSEO — Robots.txt';
$activeNav     = 'goniseo-robots';
$topbarActions = '<a href="' . e($base) . '/manage/goniseo" class="btn btn-ghost" style="font-size:13px">← Dashboard</a>';

$isManaged = ($settings['manage_robots'] ?? '1') === '1';
?>
<style>
.gseo-switch{position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0}
.gseo-switch input{opacity:0;width:0;height:0}
.gseo-switch-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#cbd5e1;transition:.2s;border-radius:24px}
.gseo-switch-slider:before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:#fff;transition:.2s;border-radius:50%}
.gseo-switch input:checked + .gseo-switch-slider{background:#7c3aed}
.gseo-switch input:checked + .gseo-switch-slider:before{transform:translateX(20px)}
</style>

<?php if ($saved): ?>
<div class="alert alert-success" style="margin-bottom:14px">✓ Robots.txt შენახულია.</div>
<?php endif ?>

<div style="max-width:720px">

<!-- Robots.txt URL -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header"><h3>🔗 Robots.txt URL</h3></div>
    <div class="card-body" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <code style="flex:1;font-size:13px;background:var(--border);padding:8px 14px;border-radius:8px">
            <?= e($robotsUrl) ?>
        </code>
        <a href="<?= e($robotsUrl) ?>" target="_blank" class="btn btn-ghost" style="font-size:13px">↗ გახსნა</a>
    </div>
    <div style="padding:0 20px 14px;font-size:12.5px;color:var(--muted)">
        <?php if ($isManaged): ?>
        ✅ GoniSEO ამ მარშრუტს ამუშავებს — robots.txt ქვემოთ რედაქტირებადია.
        <?php else: ?>
        ⚠ GoniSEO robots.txt-ს ამჟამად <strong>არ</strong> ამუშავებს. ჩართეთ ქვემოთ.
        <?php endif ?>
    </div>
</div>

<form method="POST" action="<?= e($base) ?>/manage/goniseo/robots/save">

<!-- Manage toggle -->
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;padding:16px 22px">
        <div>
            <div style="font-weight:700;font-size:14px">GoniSEO მართავს robots.txt-ს</div>
            <div style="font-size:12.5px;color:var(--muted);margin-top:2px">
                ჩართვის შემთხვევაში, <code>/robots.txt</code> route-ი ქვემოდ ტექსტს დააბრუნებს.
            </div>
        </div>
        <div>
            <input type="hidden" name="manage_robots" value="0">
            <label class="gseo-switch">
                <input type="checkbox" name="manage_robots" value="1" id="gsManageRobots"
                       <?= $isManaged ? 'checked' : '' ?>
                       onchange="gsRobotsEditorState()">
                <span class="gseo-switch-slider"></span>
            </label>
        </div>
    </div>
</div>

<!-- Editor -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header" style="justify-content:space-between">
        <h3>✏ robots.txt შინაარსი</h3>
        <div style="display:flex;gap:8px">
            <button type="button" class="btn btn-ghost" style="font-size:12px"
                    onclick="gsInsertSnippet('User-agent: *\nAllow: /')">📋 Allow all</button>
            <button type="button" class="btn btn-ghost" style="font-size:12px"
                    onclick="gsInsertSnippet('User-agent: *\nDisallow: /')">🚫 Disallow all</button>
        </div>
    </div>
    <div class="card-body">
        <textarea id="gsRobotsContent" name="robots_txt" class="form-input"
                  rows="16"
                  style="font-family:monospace;font-size:13px;<?= !$isManaged ? 'opacity:.5;pointer-events:none' : '' ?>"
                  placeholder="User-agent: *&#10;Allow: /"><?= htmlspecialchars((string)($settings['robots_txt'] ?? "User-agent: *\nAllow: /"), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
        <div style="font-size:11.5px;color:var(--muted);margin-top:6px">
            სათითაო ბრძანება ახალ ხაზზე. Sitemap URL ავტომატურად <strong>არ</strong> ემატება — საჭიროების შემთხვევაში ხელით ჩაწერე.
        </div>
    </div>
</div>

<!-- Common snippets info -->
<div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:14px 18px;margin-bottom:20px;font-size:12.5px;color:var(--muted)">
    <strong style="color:#1e293b">სასარგებლო snippet-ები:</strong><br>
    <code>Disallow: /manage/</code> — Admin panel-ის დამალვა<br>
    <code>Disallow: /tmp/</code> — Temp files-ის დამალვა<br>
    <code>Sitemap: https://example.com/sitemap.xml</code> — Sitemap-ის ბმული<br>
    <code>Crawl-delay: 10</code> — Bot-ების ინტერვალი (წამებში)
</div>

<div style="display:flex;gap:12px">
    <button type="submit" class="btn btn-primary">💾 შენახვა</button>
    <a href="<?= e($base) ?>/manage/goniseo" class="btn btn-ghost">გაუქმება</a>
</div>
</form>
</div>

<script>
function gsRobotsEditorState() {
    var enabled = document.getElementById('gsManageRobots').checked;
    var ta = document.getElementById('gsRobotsContent');
    ta.style.opacity = enabled ? '1' : '0.5';
    ta.style.pointerEvents = enabled ? '' : 'none';
}

function gsInsertSnippet(text) {
    var ta = document.getElementById('gsRobotsContent');
    var val = ta.value;
    ta.value = val + (val && !val.endsWith('\n') ? '\n' : '') + text + '\n';
    ta.focus();
    ta.scrollTop = ta.scrollHeight;
}
</script>
