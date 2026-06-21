<?php
$pageTitle     = 'GsAds — Ad Zones';
$activeNav     = 'gsads-zones';
$topbarActions = '<a href="' . e($base) . '/manage/gsads/zones/form" class="btn btn-primary" style="font-size:13px">+ ახალი Zone</a>';
?>
<style>
.gsads-zone-size{font-family:monospace;font-size:12px;background:var(--border);border-radius:5px;padding:2px 7px;white-space:nowrap}
.gsads-on{display:inline-flex;align-items:center;font-size:11.5px;font-weight:700;background:#d1fae5;color:#065f46;border-radius:20px;padding:2px 9px}
.gsads-off{display:inline-flex;align-items:center;font-size:11.5px;font-weight:700;background:#f1f5f9;color:#94a3b8;border-radius:20px;padding:2px 9px}
</style>

<?php if ($saved): ?>
<div class="alert alert-success" style="margin-bottom:20px">✓ Zone შენახულია.</div>
<?php endif ?>

<div class="card">
    <div class="card-header" style="justify-content:space-between">
        <h3>Ad Zones <span style="font-size:13px;color:var(--muted);font-weight:400">(<?= count($zones) ?>)</span></h3>
        <a href="<?= e($base) ?>/manage/gsads/zones/form" class="btn btn-ghost" style="font-size:13px">+ ახალი Zone</a>
    </div>

    <?php if (empty($zones)): ?>
    <div class="empty">
        <div class="empty-icon">🗂</div>
        <h3>Zone არ არის</h3>
        <p>Zone განსაზღვრავს სარეკლამო ადგილს — მაგ. „Header Banner", „Sidebar Top", „Footer Strip".</p>
        <a href="<?= e($base) ?>/manage/gsads/zones/form" class="btn btn-primary">პირველი Zone-ის შექმნა</a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="data-table">
        <thead><tr>
            <th>Zone</th>
            <th>Slug <span style="font-size:11px;font-weight:400;color:var(--muted)">(თემში გამოსაყენებელი)</span></th>
            <th style="text-align:center">ზომა</th>
            <th style="text-align:center">Ads</th>
            <th style="text-align:center">Imp.</th>
            <th style="text-align:center">Clicks</th>
            <th style="text-align:center">Status</th>
            <th style="text-align:right">Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($zones as $z):
            $imp = (int)$z['impressions'];
            $clk = (int)$z['clicks'];
        ?>
        <tr>
            <td>
                <div style="font-weight:700"><?= e((string)$z['name']) ?></div>
                <?php if ($z['description']): ?>
                <div style="font-size:12px;color:var(--muted)"><?= e((string)$z['description']) ?></div>
                <?php endif ?>
            </td>
            <td>
                <code style="font-size:12px;background:var(--border);padding:2px 7px;border-radius:5px;user-select:all"><?= e((string)$z['slug']) ?></code>
                <div style="font-size:11px;color:var(--muted);margin-top:3px">
                    <?= e("<?= gsads('" . $z['slug'] . "') ?>") ?>
                </div>
            </td>
            <td style="text-align:center">
                <?php if ($z['width'] && $z['height']): ?>
                <span class="gsads-zone-size"><?= (int)$z['width'] ?>×<?= (int)$z['height'] ?></span>
                <?php elseif ($z['width']): ?>
                <span class="gsads-zone-size">↔ <?= (int)$z['width'] ?>px</span>
                <?php else: ?>
                <span style="color:var(--muted);font-size:12px">—</span>
                <?php endif ?>
            </td>
            <td style="text-align:center">
                <a href="<?= e($base) ?>/manage/gsads/ads?zone_id=<?= (int)$z['id'] ?>"
                   style="font-weight:700;color:var(--accent)">
                   <?= (int)$z['active_ads'] ?> / <?= (int)$z['ad_count'] ?>
                </a>
            </td>
            <td style="text-align:center;font-size:13px"><?= number_format($imp) ?></td>
            <td style="text-align:center;font-size:13px"><?= number_format($clk) ?></td>
            <td style="text-align:center">
                <form method="POST" action="<?= e($base) ?>/manage/gsads/zones/toggle" style="display:inline">
                    <input type="hidden" name="id"     value="<?= (int)$z['id'] ?>">
                    <input type="hidden" name="active" value="<?= (int)$z['active'] ?>">
                    <button type="submit" class="btn btn-ghost" style="padding:3px 8px;font-size:12px;border-radius:20px">
                        <?= $z['active']
                            ? '<span class="gsads-on">✓ Active</span>'
                            : '<span class="gsads-off">Off</span>' ?>
                    </button>
                </form>
            </td>
            <td style="text-align:right">
                <div style="display:flex;gap:6px;justify-content:flex-end">
                    <a href="<?= e($base) ?>/manage/gsads/ads?zone_id=<?= (int)$z['id'] ?>"
                       class="btn btn-ghost" style="font-size:12px;padding:4px 10px" title="Ads">📋</a>
                    <a href="<?= e($base) ?>/manage/gsads/ads/form?zone_id=<?= (int)$z['id'] ?>"
                       class="btn btn-ghost" style="font-size:12px;padding:4px 10px" title="Ad დამატება">+🖼</a>
                    <a href="<?= e($base) ?>/manage/gsads/zones/form?id=<?= (int)$z['id'] ?>"
                       class="btn btn-ghost" style="font-size:12px;padding:4px 10px" title="Zone რედაქტირება">✏</a>
                    <form method="POST" action="<?= e($base) ?>/manage/gsads/zones/delete"
                          onsubmit="return confirm('Zone წაიშლება ყველა Ad-თან ერთად. გაგრძელება?')">
                        <input type="hidden" name="id" value="<?= (int)$z['id'] ?>">
                        <button type="submit" class="btn btn-ghost" style="font-size:12px;padding:4px 10px;color:#ef4444" title="Zone წაშლა">🗑</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    </div>
    <?php endif ?>
</div>
