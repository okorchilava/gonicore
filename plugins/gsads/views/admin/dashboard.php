<?php
$pageTitle     = 'GsAds — Dashboard';
$activeNav     = 'gsads-dashboard';
$topbarActions = '<a href="' . e($base) . '/manage/gsads/zones/form" class="btn btn-primary" style="font-size:13px">+ ახალი Zone</a>';
?>
<style>
.gsads-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px}
.gsads-stat{background:var(--card-bg,#fff);border:1px solid var(--border);border-radius:12px;padding:18px 20px;display:flex;flex-direction:column;gap:4px}
.gsads-stat-val{font-size:28px;font-weight:800;line-height:1;color:var(--text)}
.gsads-stat-lbl{font-size:12px;color:var(--muted);font-weight:500}
.gsads-stat-ctr .gsads-stat-val{color:#7c3aed}
.gsads-badge-on{display:inline-flex;align-items:center;gap:4px;font-size:11.5px;font-weight:700;background:#d1fae5;color:#065f46;border-radius:20px;padding:2px 9px}
.gsads-badge-off{display:inline-flex;align-items:center;gap:4px;font-size:11.5px;font-weight:700;background:#f1f5f9;color:#94a3b8;border-radius:20px;padding:2px 9px}
</style>

<!-- Stats row -->
<div class="gsads-stat-grid">
    <div class="gsads-stat">
        <span class="gsads-stat-val"><?= number_format($stats['total_zones']) ?></span>
        <span class="gsads-stat-lbl">🗂 Ad Zones</span>
    </div>
    <div class="gsads-stat">
        <span class="gsads-stat-val"><?= number_format($stats['active_ads']) ?> <span style="font-size:15px;font-weight:500;color:var(--muted)">/ <?= number_format($stats['total_ads']) ?></span></span>
        <span class="gsads-stat-lbl">🖼 Active Ads</span>
    </div>
    <div class="gsads-stat">
        <span class="gsads-stat-val"><?= number_format($stats['total_impressions']) ?></span>
        <span class="gsads-stat-lbl">👁 Impressions</span>
    </div>
    <div class="gsads-stat">
        <span class="gsads-stat-val"><?= number_format($stats['total_clicks']) ?></span>
        <span class="gsads-stat-lbl">🖱 Clicks</span>
    </div>
    <div class="gsads-stat gsads-stat-ctr">
        <span class="gsads-stat-val"><?= number_format($stats['ctr'], 2) ?>%</span>
        <span class="gsads-stat-lbl">📈 CTR</span>
    </div>
</div>

<!-- Zones overview -->
<div class="card">
    <div class="card-header" style="justify-content:space-between">
        <h3>Ad Zones</h3>
        <a href="<?= e($base) ?>/manage/gsads/zones/form" class="btn btn-ghost" style="font-size:13px">+ Zone</a>
    </div>

    <?php if (empty($zoneStats)): ?>
    <div class="empty">
        <div class="empty-icon">🗂</div>
        <h3>Zone არ არის</h3>
        <p>შექმენი პირველი Ad Zone სარეკლამო ადგილის განსაზღვრისთვის.</p>
        <a href="<?= e($base) ?>/manage/gsads/zones/form" class="btn btn-primary">Zone-ის შექმნა</a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="data-table">
        <thead><tr>
            <th>Zone</th>
            <th>Slug</th>
            <th style="text-align:center">Ads</th>
            <th style="text-align:center">Impressions</th>
            <th style="text-align:center">Clicks</th>
            <th style="text-align:center">CTR</th>
            <th style="text-align:center">Status</th>
            <th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($zoneStats as $z):
            $imp = (int) $z['impressions'];
            $clk = (int) $z['clicks'];
            $ctr = $imp > 0 ? round($clk / $imp * 100, 1) : 0;
        ?>
        <tr>
            <td>
                <div style="font-weight:700;font-size:14px"><?= e((string)$z['name']) ?></div>
                <?php if ($z['description']): ?>
                <div style="font-size:12px;color:var(--muted)"><?= e((string)$z['description']) ?></div>
                <?php endif ?>
            </td>
            <td><code style="font-size:12px;background:var(--border);padding:2px 7px;border-radius:5px"><?= e((string)$z['slug']) ?></code></td>
            <td style="text-align:center;font-weight:700"><?= (int)$z['active_ads'] ?> / <?= (int)$z['ad_count'] ?></td>
            <td style="text-align:center"><?= number_format($imp) ?></td>
            <td style="text-align:center"><?= number_format($clk) ?></td>
            <td style="text-align:center"><?= $ctr ?>%</td>
            <td style="text-align:center">
                <?= $z['active'] ? '<span class="gsads-badge-on">✓ Active</span>' : '<span class="gsads-badge-off">Off</span>' ?>
            </td>
            <td>
                <div style="display:flex;gap:6px;justify-content:flex-end">
                    <a href="<?= e($base) ?>/manage/gsads/ads?zone_id=<?= (int)$z['id'] ?>"
                       class="btn btn-ghost" style="font-size:12px;padding:4px 10px" title="Ads-ის ნახვა">📋</a>
                    <a href="<?= e($base) ?>/manage/gsads/zones/form?id=<?= (int)$z['id'] ?>"
                       class="btn btn-ghost" style="font-size:12px;padding:4px 10px" title="Zone-ის რედაქტირება">✏</a>
                </div>
            </td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    </div>
    <?php endif ?>
</div>

<!-- Usage hint -->
<div class="card" style="margin-top:16px">
    <div class="card-header"><h3>გამოყენება თემებში</h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:10px;font-size:13px;color:var(--muted)">
        <div>📌 <strong>PHP</strong> — ჩასვი ნებისმიერ თემის ფაილში:</div>
        <pre style="background:var(--bg-2,rgba(0,0,0,.04));border:1px solid var(--border);border-radius:8px;padding:12px 16px;font-size:13px;margin:0;overflow-x:auto"><?= e("<?= gsads('zone-slug') ?>\n<?= gsads('sidebar-block', 3) ?>   // up to 3 ads\n<?= gsads('footer-strip', 0)  ?>   // all active ads") ?></pre>
        <div>🌐 Zone-ის slug სვეტი ნახვისთვის: <a href="<?= e($base) ?>/manage/gsads/zones">Zones →</a></div>
        <div>🔗 <strong>Click tracking</strong> — ავტომატური. Image/Text ტიპის რეკლამები ტრეკინგ URL-ს იყენებს (<code style="font-size:11px;background:var(--border);padding:1px 6px;border-radius:4px">/gsads/click?id=N</code>).</div>
        <div>📄 <strong>HTML</strong> ტიპის რეკლამები (Google AdSense და სხვ.) — click tracking მათი მხარეა, ჩვენ მხოლოდ Impression-ს ვითვლით.</div>
    </div>
</div>
