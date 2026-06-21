<?php
$pageTitle     = 'GCRating — Dashboard';
$activeNav     = 'gcrating';
$topbarActions = '';

// Period helpers
$periods   = ['today' => 'დღეს', '7d' => '7 დღე', '30d' => '30 დღე', '90d' => '90 დღე', 'all' => 'სულ'];
$periodUrl = static fn(string $p) => e($base) . '/manage/gcrating?period=' . $p;

// Chart data
$maxSessions  = max(1, array_reduce($daily, fn($m, $d) => max($m, (int)$d['sessions']),  0));
$maxPageviews = max(1, array_reduce($daily, fn($m, $d) => max($m, (int)$d['pageviews']), 0));

// Source totals
$sourceTotal = max(1, array_sum(array_column($sources, 'cnt')));
// Device totals
$deviceTotal = max(1, array_sum(array_column($devices, 'cnt')));

$srcMap = [];
foreach ($sources  as $s) $srcMap[$s['source_type']] = (int)$s['cnt'];
$devMap = [];
foreach ($devices  as $d) $devMap[$d['device']]       = (int)$d['cnt'];
$brwMap = [];
foreach ($browsers as $b) $brwMap[$b['browser']]      = (int)$b['cnt'];
?>

<!-- ── Sub-nav ── -->
<div style="display:flex;gap:6px;margin-bottom:20px;flex-wrap:wrap">
<?php foreach ($periods as $pk => $pl): ?>
    <a href="<?= $periodUrl($pk) ?>" class="btn <?= $period === $pk ? 'btn-primary' : 'btn-ghost' ?>"
       style="font-size:13px"><?= e($pl) ?></a>
<?php endforeach ?>
    <div style="flex:1"></div>
    <a href="<?= e($base) ?>/manage/gcrating/pages?period=<?= e($period) ?>"
       class="btn btn-ghost" style="font-size:13px">📄 გვერდები</a>
    <a href="<?= e($base) ?>/manage/gcrating/referrers?period=<?= e($period) ?>"
       class="btn btn-ghost" style="font-size:13px">↗ წყაროები</a>
    <a href="<?= e($base) ?>/manage/gcrating/settings"
       class="btn btn-ghost" style="font-size:13px">⚙ პარამეტრები</a>
</div>

<!-- ── Today's live counter ── -->
<div style="background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:14px;padding:16px 22px;color:#fff;margin-bottom:20px;display:flex;align-items:center;gap:20px">
    <div style="font-size:28px">📊</div>
    <div>
        <div style="font-size:12px;opacity:.8;margin-bottom:2px">დღეს</div>
        <div style="font-size:22px;font-weight:800;line-height:1">
            <?= number_format((int)($today['sessions'] ?? 0)) ?> სესია
            <span style="font-size:14px;opacity:.75;font-weight:500;margin-left:8px">
                / <?= number_format((int)($today['visitors'] ?? 0)) ?> მომხმარებელი
            </span>
        </div>
    </div>
    <div style="margin-left:auto;text-align:right;font-size:12px;opacity:.7">
        სულ DB: <?= number_format((int)($totals['sessions'] ?? 0)) ?> სესია
    </div>
</div>

<!-- ── KPI cards ── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:20px">

<?php
$kpis = [
    ['label' => 'სესიები',         'value' => number_format($overview['sessions']),        'icon' => '🔄', 'color' => '#6366f1'],
    ['label' => 'უნიკ. ვიზიტ.',   'value' => number_format($overview['unique_visitors']),  'icon' => '👤', 'color' => '#3b82f6'],
    ['label' => 'გვ. ნახვები',     'value' => number_format($overview['pageviews']),        'icon' => '👁',  'color' => '#10b981'],
    ['label' => 'საშ. დრო',        'value' => GCRating\GCRatingService::formatTime($overview['avg_session_time']), 'icon' => '⏱', 'color' => '#f59e0b'],
    ['label' => 'Bounce Rate',     'value' => $overview['bounce_rate'] . '%',               'icon' => '↩',  'color' => '#ef4444'],
    ['label' => 'საშ. გვ./სესია', 'value' => $overview['avg_pages'],                       'icon' => '📑', 'color' => '#8b5cf6'],
];
foreach ($kpis as $k):
?>
<div class="card">
    <div class="card-body" style="padding:16px 18px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
            <span style="font-size:20px"><?= $k['icon'] ?></span>
            <span style="width:8px;height:8px;border-radius:50%;background:<?= $k['color'] ?>"></span>
        </div>
        <div style="font-size:1.6rem;font-weight:800;color:<?= $k['color'] ?>;line-height:1"><?= $k['value'] ?></div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px"><?= $k['label'] ?></div>
    </div>
</div>
<?php endforeach ?>
</div>

<!-- ── Chart: last 30 days ── -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:20px 22px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
            <div style="font-weight:700;font-size:14px">📈 ბოლო 30 დღე</div>
            <div style="display:flex;gap:16px;font-size:12px">
                <span style="display:flex;align-items:center;gap:5px">
                    <span style="width:10px;height:10px;border-radius:2px;background:#6366f1;display:inline-block"></span>
                    სესიები
                </span>
                <span style="display:flex;align-items:center;gap:5px">
                    <span style="width:10px;height:10px;border-radius:2px;background:#10b981;display:inline-block"></span>
                    გვ. ნახვები
                </span>
            </div>
        </div>

        <?php if (empty($daily)): ?>
        <div style="text-align:center;padding:40px;color:var(--muted);font-size:13px">ჯერ მონაცემი არ არის</div>
        <?php else: ?>
        <div style="display:flex;align-items:flex-end;gap:3px;height:120px;overflow-x:auto;padding-bottom:22px;position:relative">
            <?php
            // Fill in missing days
            $dailyByDate = [];
            foreach ($daily as $d) $dailyByDate[$d['date']] = $d;
            $chartDays = [];
            for ($i = 29; $i >= 0; $i--) {
                $dt = date('Y-m-d', strtotime("-{$i} days"));
                $chartDays[] = $dailyByDate[$dt] ?? ['date' => $dt, 'sessions' => 0, 'visitors' => 0, 'pageviews' => 0];
            }
            $maxS = max(1, max(array_column($chartDays, 'sessions')));
            $maxP = max(1, max(array_column($chartDays, 'pageviews')));
            $maxV = max($maxS, $maxP);
            ?>
            <?php foreach ($chartDays as $day): ?>
            <?php
                $sPct = round((int)$day['sessions'] / $maxV * 100, 1);
                $pPct = round((int)$day['pageviews'] / $maxV * 100, 1);
                $label = date('M d', strtotime($day['date']));
            ?>
            <div style="flex:1;min-width:14px;display:flex;align-items:flex-end;gap:1px;height:100%;position:relative"
                 title="<?= e($label) ?>: <?= (int)$day['sessions'] ?> სესია, <?= (int)$day['pageviews'] ?> ნახვა">
                <div style="flex:1;background:#6366f1;border-radius:2px 2px 0 0;height:<?= $sPct ?>%;min-height:<?= (int)$day['sessions']>0?2:0 ?>px;transition:opacity .2s" onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'"></div>
                <div style="flex:1;background:#10b981;border-radius:2px 2px 0 0;height:<?= $pPct ?>%;min-height:<?= (int)$day['pageviews']>0?2:0 ?>px;transition:opacity .2s" onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'"></div>
            </div>
            <?php endforeach ?>
            <!-- X axis labels -->
            <div style="position:absolute;bottom:0;left:0;right:0;display:flex;justify-content:space-between;font-size:10px;color:var(--muted)">
                <?php
                $labelIdxs = [0, 4, 9, 14, 19, 24, 29];
                foreach ($labelIdxs as $li):
                    if (isset($chartDays[$li])):
                ?>
                <span><?= date('d/m', strtotime($chartDays[$li]['date'])) ?></span>
                <?php endif; endforeach ?>
            </div>
        </div>
        <?php endif ?>
    </div>
</div>

<!-- ── Bottom grid: Pages | Sources | Devices ── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px">

    <!-- Top Pages -->
    <div class="card">
        <div class="card-body" style="padding:18px 20px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
                <div style="font-weight:700;font-size:14px">📄 Top გვერდები</div>
                <a href="<?= e($base) ?>/manage/gcrating/pages?period=<?= e($period) ?>"
                   style="font-size:12px;color:var(--primary)">ყველა →</a>
            </div>
            <?php if (empty($topPages)): ?>
            <div style="color:var(--muted);font-size:13px;text-align:center;padding:20px">მონაცემი არ არის</div>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:8px">
                <?php
                $maxPv = max(1, max(array_column($topPages, 'views')));
                foreach ($topPages as $pg):
                    $pct = round((int)$pg['views'] / $maxPv * 100);
                ?>
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:3px">
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px;color:var(--text)"
                              title="<?= e((string)$pg['url']) ?>">
                            <?= e(strlen((string)$pg['url']) > 40 ? '…' . substr((string)$pg['url'], -38) : (string)$pg['url']) ?>
                        </span>
                        <span style="font-weight:700;color:#6366f1;flex-shrink:0;margin-left:8px"><?= number_format((int)$pg['views']) ?></span>
                    </div>
                    <div style="height:4px;background:var(--border);border-radius:2px">
                        <div style="height:4px;background:#6366f1;border-radius:2px;width:<?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Traffic Sources -->
    <div class="card">
        <div class="card-body" style="padding:18px 20px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
                <div style="font-weight:700;font-size:14px">↗ ტრაფიკის წყაროები</div>
                <a href="<?= e($base) ?>/manage/gcrating/referrers?period=<?= e($period) ?>"
                   style="font-size:12px;color:var(--primary)">ყველა →</a>
            </div>
            <?php if (empty($sources)): ?>
            <div style="color:var(--muted);font-size:13px;text-align:center;padding:20px">მონაცემი არ არის</div>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:10px">
                <?php
                $maxSrc = max(1, max(array_column($sources, 'cnt')));
                foreach ($sources as $src):
                    $cnt   = (int)$src['cnt'];
                    $type  = (string)$src['source_type'];
                    $color = GCRating\GCRatingService::sourceColor($type);
                    $label = GCRating\GCRatingService::sourceLabel($type);
                    $pct   = round($cnt / $maxSrc * 100);
                    $pctTotal = round($cnt / $sourceTotal * 100, 1);
                ?>
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:3px">
                        <span><?= e($label) ?></span>
                        <span style="font-weight:700;color:<?= $color ?>">
                            <?= number_format($cnt) ?> <span style="font-weight:400;color:var(--muted)">(<?= $pctTotal ?>%)</span>
                        </span>
                    </div>
                    <div style="height:5px;background:var(--border);border-radius:3px">
                        <div style="height:5px;background:<?= $color ?>;border-radius:3px;width:<?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Devices & Browsers -->
    <div class="card">
        <div class="card-body" style="padding:18px 20px">
            <div style="font-weight:700;font-size:14px;margin-bottom:14px">📱 მოწყობილობები</div>
            <?php if (empty($devices)): ?>
            <div style="color:var(--muted);font-size:13px;text-align:center;padding:10px">მონაცემი არ არის</div>
            <?php else: ?>
            <!-- Device donut-style row -->
            <div style="display:flex;gap:6px;margin-bottom:14px">
                <?php
                $devColors = ['desktop' => '#6366f1', 'mobile' => '#10b981', 'tablet' => '#f59e0b'];
                $devIcons  = ['desktop' => '🖥', 'mobile' => '📱', 'tablet' => '📲'];
                foreach ($devices as $dev):
                    $type  = (string)$dev['device'];
                    $cnt   = (int)$dev['cnt'];
                    $pct   = round($cnt / $deviceTotal * 100, 1);
                    $color = $devColors[$type] ?? '#9ca3af';
                    $icon  = $devIcons[$type]  ?? '📟';
                ?>
                <div style="flex:1;background:color-mix(in srgb,<?= $color ?> 10%,transparent);border:1.5px solid <?= $color ?>;border-radius:10px;padding:10px;text-align:center">
                    <div style="font-size:18px"><?= $icon ?></div>
                    <div style="font-weight:800;font-size:1rem;color:<?= $color ?>"><?= $pct ?>%</div>
                    <div style="font-size:11px;color:var(--muted);margin-top:2px"><?= ucfirst($type) ?></div>
                </div>
                <?php endforeach ?>
            </div>

            <!-- Browser list -->
            <div style="font-size:12px;color:var(--muted);font-weight:600;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px">ბრაუზერები</div>
            <div style="display:flex;flex-direction:column;gap:6px">
                <?php
                $maxBr = max(1, max(array_column($browsers, 'cnt')));
                $brwTotal = max(1, array_sum(array_column($browsers, 'cnt')));
                $brwColors = ['Chrome'=>'#4285f4','Firefox'=>'#ff7139','Safari'=>'#0fb5ee','Edge'=>'#0078d4','Opera'=>'#ff1b2d','Yandex'=>'#fc3f1d','Other'=>'#9ca3af','IE'=>'#00bcf2'];
                foreach ($browsers as $br):
                    $name  = (string)$br['browser'];
                    $cnt   = (int)$br['cnt'];
                    $pct   = round($cnt / $maxBr * 100);
                    $pctT  = round($cnt / $brwTotal * 100, 1);
                    $color = $brwColors[$name] ?? '#9ca3af';
                ?>
                <div style="display:flex;align-items:center;gap:8px;font-size:12.5px">
                    <span style="width:8px;height:8px;border-radius:50%;background:<?= $color ?>;flex-shrink:0"></span>
                    <span style="flex:1"><?= e($name) ?></span>
                    <div style="width:60px;height:4px;background:var(--border);border-radius:2px">
                        <div style="height:4px;background:<?= $color ?>;border-radius:2px;width:<?= $pct ?>%"></div>
                    </div>
                    <span style="color:var(--muted);width:36px;text-align:right"><?= $pctT ?>%</span>
                </div>
                <?php endforeach ?>
            </div>
            <?php endif ?>
        </div>
    </div>

</div>

<!-- ── Top Referrers preview ── -->
<?php if (!empty($topRefs)): ?>
<div class="card" style="margin-top:16px">
    <div class="card-body" style="padding:18px 20px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <div style="font-weight:700;font-size:14px">🌐 Top Referrers</div>
            <a href="<?= e($base) ?>/manage/gcrating/referrers?period=<?= e($period) ?>"
               style="font-size:12px;color:var(--primary)">ყველა →</a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px">
            <?php foreach ($topRefs as $ref):
                $color = GCRating\GCRatingService::sourceColor((string)$ref['source_type']);
            ?>
            <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--bg-subtle,#f8fafc);border-radius:8px;border:1px solid var(--border)">
                <div style="width:6px;height:32px;border-radius:3px;background:<?= $color ?>;flex-shrink:0"></div>
                <div style="overflow:hidden">
                    <div style="font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <?= e((string)$ref['referrer_host']) ?>
                    </div>
                    <div style="font-size:11.5px;color:var(--muted)">
                        <?= number_format((int)$ref['sessions']) ?> სესია
                    </div>
                </div>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</div>
<?php endif ?>
