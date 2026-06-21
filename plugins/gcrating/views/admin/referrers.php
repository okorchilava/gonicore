<?php
$pageTitle     = 'GCRating — Traffic Sources';
$activeNav     = 'gcrating';
$topbarActions = '';

$periods     = ['today' => 'დღეს', '7d' => '7 დღე', '30d' => '30 დღე', '90d' => '90 დღე', 'all' => 'სულ'];
$sourceTotal = max(1, array_sum(array_column($sources, 'cnt')));
$maxRef      = max(1, !empty($items) ? max(array_column($items, 'sessions')) : 1);

$srcTypes = ['direct', 'search', 'social', 'referral', 'internal'];
$srcMap   = [];
foreach ($sources as $s) $srcMap[$s['source_type']] = (int)$s['cnt'];
?>

<!-- ── Sub-nav ── -->
<div style="display:flex;gap:6px;margin-bottom:20px;flex-wrap:wrap;align-items:center">
    <a href="<?= e($base) ?>/manage/gcrating?period=<?= e($period) ?>"
       class="btn btn-ghost" style="font-size:13px">← Dashboard</a>
    <div style="width:1px;height:20px;background:var(--border)"></div>
    <?php foreach ($periods as $pk => $pl): ?>
    <a href="<?= e($base) ?>/manage/gcrating/referrers?period=<?= e($pk) ?>"
       class="btn <?= $period === $pk ? 'btn-primary' : 'btn-ghost' ?>" style="font-size:13px"><?= e($pl) ?></a>
    <?php endforeach ?>
    <div style="flex:1"></div>
    <a href="<?= e($base) ?>/manage/gcrating/pages?period=<?= e($period) ?>"
       class="btn btn-ghost" style="font-size:13px">📄 გვერდები</a>
    <a href="<?= e($base) ?>/manage/gcrating/settings" class="btn btn-ghost" style="font-size:13px">⚙ პარამეტრები</a>
</div>

<!-- ── Source type breakdown cards ── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:20px">
<?php foreach ($srcTypes as $type):
    $cnt   = $srcMap[$type] ?? 0;
    $pct   = round($cnt / $sourceTotal * 100, 1);
    $color = GCRating\GCRatingService::sourceColor($type);
    $label = GCRating\GCRatingService::sourceLabel($type);
?>
<div class="card">
    <div class="card-body" style="padding:14px 16px">
        <div style="font-size:18px;margin-bottom:6px"><?= mb_substr($label, 0, 2) ?></div>
        <div style="font-size:1.3rem;font-weight:800;color:<?= $color ?>;line-height:1"><?= number_format($cnt) ?></div>
        <div style="font-size:11.5px;color:var(--muted);margin-top:3px"><?= ltrim(mb_substr($label, 2)) ?></div>
        <!-- Color bar showing share -->
        <div style="height:3px;background:var(--border);border-radius:2px;margin-top:8px">
            <div style="height:3px;background:<?= $color ?>;border-radius:2px;width:<?= $pct ?>%"></div>
        </div>
        <div style="font-size:11px;color:var(--muted);margin-top:4px;text-align:right"><?= $pct ?>%</div>
    </div>
</div>
<?php endforeach ?>
</div>

<!-- ── Referrers table ── -->
<div class="card">
    <div class="card-body" style="padding:0">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
            <div style="font-weight:700;font-size:14px">🌐 გარე Referrers</div>
            <div style="font-size:12.5px;color:var(--muted)"><?= count($items) ?> დომენი</div>
        </div>

        <?php if (empty($items)): ?>
        <div style="text-align:center;padding:60px 20px;color:var(--muted)">
            <div style="font-size:40px;margin-bottom:12px">🌐</div>
            <div style="font-size:15px;font-weight:700;margin-bottom:6px">გარე referrer-ი არ არის</div>
            <div style="font-size:13px">ვიზიტორები სხვა საიტებიდან ჯერ არ შემოსულა</div>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13.5px">
            <thead>
            <tr style="background:var(--bg-subtle,#f8fafc);border-bottom:2px solid var(--border)">
                <th style="padding:10px 16px;text-align:left;font-weight:700;color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.4px">#</th>
                <th style="padding:10px 16px;text-align:left;font-weight:700;color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.4px">Domain</th>
                <th style="padding:10px 16px;text-align:left;font-weight:700;color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.4px">ტიპი</th>
                <th style="padding:10px 16px;text-align:right;font-weight:700;color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.4px;white-space:nowrap">სესიები</th>
                <th style="padding:10px 16px;text-align:right;font-weight:700;color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.4px;white-space:nowrap">ვიზიტ.</th>
                <th style="padding:10px 16px;text-align:right;font-weight:700;color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.4px;white-space:nowrap">წილი</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $referralTotal = max(1, array_sum(array_column($items, 'sessions')));
            foreach ($items as $i => $ref):
                $cnt     = (int)$ref['sessions'];
                $type    = (string)$ref['source_type'];
                $color   = GCRating\GCRatingService::sourceColor($type);
                $label   = GCRating\GCRatingService::sourceLabel($type);
                $barPct  = round($cnt / $maxRef * 100);
                $sharePct = round($cnt / $referralTotal * 100, 1);
                $isEven   = $i % 2 === 1;
            ?>
            <tr style="border-bottom:1px solid var(--border);<?= $isEven ? 'background:var(--bg-subtle,#fafafa)' : '' ?>">
                <td style="padding:11px 16px;color:var(--muted);font-size:12px;font-variant-numeric:tabular-nums">
                    <?= $i + 1 ?>
                </td>
                <td style="padding:11px 16px;max-width:0;width:45%">
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="width:4px;height:28px;border-radius:2px;background:<?= $color ?>;flex-shrink:0"></div>
                        <div style="overflow:hidden">
                            <div style="font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13.5px">
                                <?= e((string)$ref['referrer_host']) ?>
                            </div>
                            <!-- bar -->
                            <div style="height:2px;background:var(--border);border-radius:1px;margin-top:4px;max-width:200px">
                                <div style="height:2px;background:<?= $color ?>;border-radius:1px;width:<?= $barPct ?>%"></div>
                            </div>
                        </div>
                    </div>
                </td>
                <td style="padding:11px 16px">
                    <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;
                                 padding:3px 8px;border-radius:20px;
                                 background:color-mix(in srgb,<?= $color ?> 12%,transparent);
                                 color:<?= $color ?>">
                        <?= e($label) ?>
                    </span>
                </td>
                <td style="padding:11px 16px;text-align:right;font-weight:800;color:#6366f1;font-size:15px;white-space:nowrap">
                    <?= number_format($cnt) ?>
                </td>
                <td style="padding:11px 16px;text-align:right;color:var(--muted);white-space:nowrap">
                    <?= number_format((int)$ref['visitors']) ?>
                </td>
                <td style="padding:11px 16px;text-align:right;color:var(--muted);font-size:12.5px;white-space:nowrap">
                    <?= $sharePct ?>%
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        </div>
        <?php endif ?>
    </div>
</div>
