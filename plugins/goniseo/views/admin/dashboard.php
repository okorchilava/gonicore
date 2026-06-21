<?php
$pageTitle     = 'GoniSEO — Dashboard';
$activeNav     = 'goniseo-dashboard';
$topbarActions =
    '<a href="' . e($base) . '/manage/goniseo/meta/form" class="btn btn-primary" style="font-size:13px">+ Meta შეყვანა</a>';

$metaCount    = (int)($stats['meta_count']    ?? 0);
$sitemapCount = (int)($stats['sitemap_count'] ?? 0);
$isEnabled    = ($settings['enabled'] ?? '1') === '1';
$titleFormat  = (string)($settings['title_format'] ?? '{title} | {site_name}');
$siteName     = (string)($settings['site_name'] ?? '');
$manageRobots = ($settings['manage_robots'] ?? '1') === '1';
?>
<style>
.gseo-stat{background:var(--card-bg,#fff);border:1px solid var(--border);border-radius:12px;padding:18px 22px;display:flex;flex-direction:column;gap:4px}
.gseo-stat-val{font-size:28px;font-weight:800}
.gseo-stat-lbl{font-size:12.5px;color:var(--muted)}
.gseo-qcard{background:var(--card-bg,#fff);border:1px solid var(--border);border-radius:12px;padding:18px 22px;display:flex;align-items:center;gap:14px;text-decoration:none;color:inherit;transition:border-color .15s,box-shadow .15s}
.gseo-qcard:hover{border-color:#7c3aed;box-shadow:0 2px 12px rgba(124,58,237,.12)}
.gseo-qcard-icon{font-size:28px;flex-shrink:0}
</style>

<?php if (!$isEnabled): ?>
<div class="alert" style="background:#fef3c7;border:1px solid #fcd34d;color:#92400e;border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:13.5px">
    ⚠ GoniSEO პლაგინი გათიშულია.
    <a href="<?= e($base) ?>/manage/goniseo/settings" style="color:#92400e;font-weight:700">პარამეტრებში ჩართვა →</a>
</div>
<?php endif ?>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px">
    <div class="gseo-stat">
        <span class="gseo-stat-val" style="color:#7c3aed"><?= $metaCount ?></span>
        <span class="gseo-stat-lbl">🏷 Meta შეყვანა</span>
    </div>
    <div class="gseo-stat">
        <span class="gseo-stat-val" style="color:#0891b2"><?= $sitemapCount ?></span>
        <span class="gseo-stat-lbl">🗺 Sitemap URL</span>
    </div>
    <div class="gseo-stat">
        <span class="gseo-stat-val" style="color:<?= $isEnabled ? '#10b981' : '#ef4444' ?>"><?= $isEnabled ? 'ON' : 'OFF' ?></span>
        <span class="gseo-stat-lbl">🔌 სტატუსი</span>
    </div>
    <div class="gseo-stat">
        <span class="gseo-stat-val" style="color:<?= $manageRobots ? '#10b981' : '#94a3b8' ?>"><?= $manageRobots ? 'ON' : 'OFF' ?></span>
        <span class="gseo-stat-lbl">🤖 Robots.txt</span>
    </div>
</div>

<!-- Quick links -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:24px">
    <a href="<?= e($base) ?>/manage/goniseo/meta" class="gseo-qcard">
        <span class="gseo-qcard-icon">🏷</span>
        <div>
            <div style="font-weight:700;font-size:14px">Meta Tags</div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px"><?= $metaCount ?> URL კონფიგურირებული</div>
        </div>
    </a>
    <a href="<?= e($base) ?>/manage/goniseo/sitemap" class="gseo-qcard">
        <span class="gseo-qcard-icon">🗺</span>
        <div>
            <div style="font-weight:700;font-size:14px">Sitemap.xml</div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px"><?= $sitemapCount ?> custom URL + meta URLs</div>
        </div>
    </a>
    <a href="<?= e($base) ?>/manage/goniseo/robots" class="gseo-qcard">
        <span class="gseo-qcard-icon">🤖</span>
        <div>
            <div style="font-weight:700;font-size:14px">Robots.txt</div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px"><?= $manageRobots ? 'GoniSEO მართავს' : 'გათიშული' ?></div>
        </div>
    </a>
    <a href="<?= e($base) ?>/manage/goniseo/settings" class="gseo-qcard">
        <span class="gseo-qcard-icon">⚙</span>
        <div>
            <div style="font-weight:700;font-size:14px">პარამეტრები</div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px">
                <?= $siteName !== '' ? e($siteName) : '<span style="color:#f59e0b">სახელი არ ჩაწერილა</span>' ?>
            </div>
        </div>
    </a>
</div>

<!-- Title format preview -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3>⚡ Title Format</h3></div>
    <div class="card-body" style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start">
        <div style="flex:1;min-width:200px">
            <div style="font-size:12px;color:var(--muted);margin-bottom:4px">Format სტრიქონი</div>
            <code style="font-size:13px;background:var(--border);padding:4px 10px;border-radius:6px;display:inline-block">
                <?= e($titleFormat) ?>
            </code>
        </div>
        <div style="flex:2;min-width:240px">
            <div style="font-size:12px;color:var(--muted);margin-bottom:4px">მაგ. Preview</div>
            <div style="font-size:14px;font-weight:600;color:#1e293b;padding:8px 14px;background:#f8fafc;border-radius:8px;border:1px solid var(--border)">
                <?= e(str_replace(['{title}', '{site_name}'], ['გვერდის სახელი', $siteName ?: 'Site Name'], $titleFormat)) ?>
            </div>
        </div>
        <div>
            <a href="<?= e($base) ?>/manage/goniseo/settings" class="btn btn-ghost" style="font-size:13px">✏ შეცვლა</a>
        </div>
    </div>
</div>

<!-- Recent meta entries -->
<div class="card">
    <div class="card-header" style="justify-content:space-between">
        <h3>🕐 ბოლო Meta შეყვანები</h3>
        <a href="<?= e($base) ?>/manage/goniseo/meta" class="btn btn-ghost" style="font-size:13px">ყველა →</a>
    </div>
    <?php if (empty($recentMeta)): ?>
    <div class="empty">
        <div class="empty-icon">🏷</div>
        <h3>Meta შეყვანა არ არის</h3>
        <p>დაამატე პირველი URL-ის SEO Meta.</p>
        <a href="<?= e($base) ?>/manage/goniseo/meta/form" class="btn btn-primary">+ Meta შეყვანა</a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="data-table">
        <thead><tr>
            <th>URL Path</th>
            <th>Title</th>
            <th>Description</th>
            <th style="text-align:right">Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($recentMeta as $m): ?>
        <tr>
            <td>
                <code style="font-size:12px;background:var(--border);padding:2px 7px;border-radius:4px">
                    <?= e((string)$m['url_path']) ?>
                </code>
            </td>
            <td style="font-size:13px;max-width:200px">
                <?= $m['title'] !== '' ? e((string)$m['title']) : '<span style="color:var(--muted);font-style:italic">default</span>' ?>
            </td>
            <td style="font-size:12.5px;color:var(--muted);max-width:220px">
                <?php $d = (string)$m['description']; ?>
                <?= $d !== '' ? e(mb_strimwidth($d, 0, 80, '…')) : '<span style="font-style:italic">—</span>' ?>
            </td>
            <td style="text-align:right">
                <a href="<?= e($base) ?>/manage/goniseo/meta/form?id=<?= (int)$m['id'] ?>"
                   class="btn btn-ghost" style="font-size:12px;padding:4px 10px">✏</a>
            </td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    </div>
    <?php endif ?>
</div>
