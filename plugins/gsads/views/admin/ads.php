<?php
$pageTitle     = 'GsAds — Ads';
$activeNav     = 'gsads-ads';
$topbarActions = '<a href="' . e($base) . '/manage/gsads/ads/form' . ($zoneId ? '?zone_id=' . (int)$zoneId : '') . '" class="btn btn-primary" style="font-size:13px">+ ახალი Ad</a>';

$typeBadge = static function (string $t): string {
    return match ($t) {
        'image' => '<span style="font-size:11.5px;font-weight:700;background:#ede9fe;color:#6d28d9;border-radius:20px;padding:2px 8px">🖼 Image</span>',
        'html'  => '<span style="font-size:11.5px;font-weight:700;background:#dbeafe;color:#1d4ed8;border-radius:20px;padding:2px 8px">📄 HTML</span>',
        'text'  => '<span style="font-size:11.5px;font-weight:700;background:#fce7f3;color:#9d174d;border-radius:20px;padding:2px 8px">📝 Text</span>',
        default => '<span style="font-size:11.5px;color:var(--muted)">' . e($t) . '</span>',
    };
};
?>
<style>
.gsads-on{font-size:11.5px;font-weight:700;background:#d1fae5;color:#065f46;border-radius:20px;padding:2px 9px}
.gsads-off{font-size:11.5px;font-weight:700;background:#f1f5f9;color:#94a3b8;border-radius:20px;padding:2px 9px}
.gsads-sched{font-size:11px;color:var(--muted)}
</style>

<?php if ($saved): ?>
<div class="alert alert-success" style="margin-bottom:20px">✓ Ad შენახულია.</div>
<?php endif ?>

<!-- Zone filter -->
<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
    <a href="<?= e($base) ?>/manage/gsads/ads"
       class="btn btn-ghost" style="font-size:13px;<?= !$zoneId ? 'border-color:var(--accent);color:var(--accent)' : '' ?>">
       ყველა Zones
    </a>
    <?php foreach ($zones as $z): ?>
    <a href="<?= e($base) ?>/manage/gsads/ads?zone_id=<?= (int)$z['id'] ?>"
       class="btn btn-ghost" style="font-size:13px;<?= $zoneId === (int)$z['id'] ? 'border-color:var(--accent);color:var(--accent)' : '' ?>">
        <?= e((string)$z['name']) ?>
    </a>
    <?php endforeach ?>
</div>

<div class="card">
    <div class="card-header" style="justify-content:space-between">
        <h3>Ads <span style="font-size:13px;color:var(--muted);font-weight:400">(<?= number_format($total) ?>)</span></h3>
        <a href="<?= e($base) ?>/manage/gsads/ads/form<?= $zoneId ? '?zone_id='.(int)$zoneId : '' ?>"
           class="btn btn-ghost" style="font-size:13px">+ ახალი Ad</a>
    </div>

    <?php if (empty($items)): ?>
    <div class="empty">
        <div class="empty-icon">🖼</div>
        <h3>Ad არ არის</h3>
        <p>შექმენი პირველი რეკლამა ამ Zone-ისთვის.</p>
        <a href="<?= e($base) ?>/manage/gsads/ads/form<?= $zoneId ? '?zone_id='.(int)$zoneId : '' ?>"
           class="btn btn-primary">Ad-ის შექმნა</a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="data-table">
        <thead><tr>
            <th>ტიპი</th>
            <th>Zone</th>
            <th>სახელი</th>
            <th style="text-align:center">Imp.</th>
            <th style="text-align:center">Clicks</th>
            <th style="text-align:center">CTR</th>
            <th>განრიგი</th>
            <th style="text-align:center">W</th>
            <th style="text-align:center">Status</th>
            <th style="text-align:right">Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($items as $ad):
            $imp = (int)$ad['impressions'];
            $clk = (int)$ad['clicks'];
            $ctr = $imp > 0 ? round($clk / $imp * 100, 1) : 0;
            $today = date('Y-m-d');
            $expired  = $ad['ends_at']   && $ad['ends_at']   < $today;
            $upcoming = $ad['starts_at'] && $ad['starts_at'] > $today;
        ?>
        <tr style="<?= $expired ? 'opacity:.55' : '' ?>">
            <td><?= $typeBadge((string)$ad['type']) ?></td>
            <td>
                <span style="font-size:12.5px">
                    <?= e((string)($zoneMap[(int)$ad['zone_id']] ?? '—')) ?>
                </span>
            </td>
            <td>
                <div style="font-weight:600;font-size:13.5px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                     title="<?= e((string)$ad['name']) ?>">
                    <?= e((string)$ad['name']) ?>
                </div>
                <?php if ($ad['type'] === 'image' && $ad['image_url']): ?>
                <div style="font-size:11px;color:var(--muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    <?= e((string)$ad['image_url']) ?>
                </div>
                <?php endif ?>
            </td>
            <td style="text-align:center;font-size:13px"><?= number_format($imp) ?></td>
            <td style="text-align:center;font-size:13px"><?= number_format($clk) ?></td>
            <td style="text-align:center;font-size:13px<?= $ctr > 2 ? ';color:#7c3aed;font-weight:700' : '' ?>"><?= $ctr ?>%</td>
            <td class="gsads-sched">
                <?php if ($ad['starts_at'] || $ad['ends_at']): ?>
                <?= $ad['starts_at'] ? date('d.m.y', strtotime($ad['starts_at'])) : '∞' ?>
                –
                <?= $ad['ends_at']   ? date('d.m.y', strtotime($ad['ends_at']))   : '∞' ?>
                <?php if ($expired):  ?><span style="color:#ef4444"> (Expired)</span><?php endif ?>
                <?php if ($upcoming): ?><span style="color:#f59e0b"> (Upcoming)</span><?php endif ?>
                <?php else: ?>
                <span style="color:var(--muted)">—</span>
                <?php endif ?>
            </td>
            <td style="text-align:center;font-size:12px;color:var(--muted)"><?= (int)$ad['weight'] ?></td>
            <td style="text-align:center">
                <form method="POST" action="<?= e($base) ?>/manage/gsads/ads/toggle" style="display:inline">
                    <input type="hidden" name="id"      value="<?= (int)$ad['id'] ?>">
                    <input type="hidden" name="active"  value="<?= (int)$ad['active'] ?>">
                    <input type="hidden" name="zone_id" value="<?= (int)$ad['zone_id'] ?>">
                    <button type="submit" class="btn btn-ghost" style="padding:3px 8px;font-size:12px;border-radius:20px">
                        <?= $ad['active']
                            ? '<span class="gsads-on">✓</span>'
                            : '<span class="gsads-off">Off</span>' ?>
                    </button>
                </form>
            </td>
            <td style="text-align:right">
                <div style="display:flex;gap:6px;justify-content:flex-end">
                    <a href="<?= e($base) ?>/manage/gsads/ads/form?id=<?= (int)$ad['id'] ?>"
                       class="btn btn-ghost" style="font-size:12px;padding:4px 10px" title="რედაქტირება">✏</a>
                    <form method="POST" action="<?= e($base) ?>/manage/gsads/ads/delete"
                          onsubmit="return confirm('Ad წაიშლება. გაგრძელება?')">
                        <input type="hidden" name="id" value="<?= (int)$ad['id'] ?>">
                        <button type="submit" class="btn btn-ghost"
                                style="font-size:12px;padding:4px 10px;color:#ef4444" title="წაშლა">🗑</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    </div>

    <?php if ($pages > 1): ?>
    <div style="padding:16px;display:flex;gap:6px;justify-content:center;flex-wrap:wrap">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?page=<?= $i ?><?= $zoneId ? '&zone_id='.(int)$zoneId : '' ?>"
           style="padding:5px 12px;border-radius:6px;border:1px solid var(--border);font-size:13px;text-decoration:none;
                  <?= $i === $page ? 'background:var(--accent);color:#fff;border-color:var(--accent)' : '' ?>">
            <?= $i ?>
        </a>
        <?php endfor ?>
    </div>
    <?php endif ?>
    <?php endif ?>
</div>
