<?php
$pageTitle     = 'GCRating — Pages';
$activeNav     = 'gcrating';
$topbarActions = '';

$periods  = ['today' => 'დღეს', '7d' => '7 დღე', '30d' => '30 დღე', '90d' => '90 დღე', 'all' => 'სულ'];
$maxViews = max(1, !empty($items) ? max(array_column($items, 'views')) : 1);
?>

<!-- ── Sub-nav ── -->
<div style="display:flex;gap:6px;margin-bottom:20px;flex-wrap:wrap;align-items:center">
    <a href="<?= e($base) ?>/manage/gcrating?period=<?= e($period) ?>"
       class="btn btn-ghost" style="font-size:13px">← Dashboard</a>
    <div style="width:1px;height:20px;background:var(--border)"></div>
    <?php foreach ($periods as $pk => $pl): ?>
    <a href="<?= e($base) ?>/manage/gcrating/pages?period=<?= e($pk) ?>"
       class="btn <?= $period === $pk ? 'btn-primary' : 'btn-ghost' ?>" style="font-size:13px"><?= e($pl) ?></a>
    <?php endforeach ?>
    <div style="flex:1"></div>
    <a href="<?= e($base) ?>/manage/gcrating/referrers?period=<?= e($period) ?>"
       class="btn btn-ghost" style="font-size:13px">↗ წყაროები</a>
    <a href="<?= e($base) ?>/manage/gcrating/settings" class="btn btn-ghost" style="font-size:13px">⚙ პარამეტრები</a>
</div>

<div class="card">
    <div class="card-body" style="padding:0">

        <?php if (empty($items)): ?>
        <div style="text-align:center;padding:60px 20px;color:var(--muted)">
            <div style="font-size:40px;margin-bottom:12px">📄</div>
            <div style="font-size:15px;font-weight:700;margin-bottom:6px">გვერდების სტატისტიკა არ არის</div>
            <div style="font-size:13px">ვიზიტორები ჯერ არ შემოსულა ამ პერიოდში</div>
        </div>
        <?php else: ?>
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
            <div style="font-weight:700;font-size:14px">📄 გვერდების სტატისტიკა</div>
            <div style="font-size:12.5px;color:var(--muted)"><?= count($items) ?> გვერდი</div>
        </div>

        <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13.5px">
            <thead>
            <tr style="background:var(--bg-subtle,#f8fafc);border-bottom:2px solid var(--border)">
                <th style="padding:11px 16px;text-align:left;font-weight:700;color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.4px">URL / სათაური</th>
                <th style="padding:11px 16px;text-align:right;font-weight:700;color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.4px;white-space:nowrap">ნახვები</th>
                <th style="padding:11px 16px;text-align:right;font-weight:700;color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.4px;white-space:nowrap">უნიკ.</th>
                <th style="padding:11px 16px;text-align:right;font-weight:700;color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.4px;white-space:nowrap">საშ. დრო</th>
                <th style="padding:11px 16px;text-align:left;font-weight:700;color:var(--muted);font-size:12px;white-space:nowrap"></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $i => $pg):
                $pct   = round((int)$pg['views'] / $maxViews * 100);
                $avg   = (int)round((float)$pg['avg_time']);
                $isEven = $i % 2 === 1;
            ?>
            <tr style="border-bottom:1px solid var(--border);<?= $isEven ? 'background:var(--bg-subtle,#fafafa)' : '' ?>">
                <td style="padding:12px 16px;max-width:0;width:50%">
                    <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600;font-size:13px;color:var(--text)"
                         title="<?= e((string)$pg['url']) ?>">
                        <?= e((string)$pg['url']) ?>
                    </div>
                    <?php if (!empty($pg['title'])): ?>
                    <div style="font-size:11.5px;color:var(--muted);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <?= e((string)$pg['title']) ?>
                    </div>
                    <?php endif ?>
                    <!-- Progress bar -->
                    <div style="height:2px;background:var(--border);border-radius:1px;margin-top:5px">
                        <div style="height:2px;background:#6366f1;border-radius:1px;width:<?= $pct ?>%"></div>
                    </div>
                </td>
                <td style="padding:12px 16px;text-align:right;font-weight:800;color:#6366f1;font-size:15px;white-space:nowrap">
                    <?= number_format((int)$pg['views']) ?>
                </td>
                <td style="padding:12px 16px;text-align:right;color:var(--muted);white-space:nowrap">
                    <?= number_format((int)$pg['unique_views']) ?>
                </td>
                <td style="padding:12px 16px;text-align:right;color:var(--muted);white-space:nowrap;font-variant-numeric:tabular-nums">
                    <?= GCRating\GCRatingService::formatTime($avg) ?>
                </td>
                <td style="padding:12px 16px;white-space:nowrap">
                    <?php
                    $siteBase = defined('BASE_URL') ? rtrim(constant('BASE_URL'), '/') : '';
                    $fullUrl  = $siteBase . (string)$pg['url'];
                    ?>
                    <a href="<?= e($fullUrl) ?>" target="_blank"
                       style="font-size:11.5px;color:var(--primary);text-decoration:none" title="გახსნა">↗</a>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        </div>
        <?php endif ?>
    </div>
</div>
