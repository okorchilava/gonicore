<?php $pageTitle='Rides'; $activeNav='taxi-rides'; $topbarActions=''; ?>
<div class="card">
    <div class="card-header" style="justify-content:space-between;flex-wrap:wrap;gap:10px">
        <h3>Rides <span style="font-size:13px;color:var(--muted);font-weight:400">(<?=$total?>)</span></h3>
        <form method="GET" style="display:flex;gap:8px">
            <select name="status" class="form-select" style="font-size:13px;padding:6px 10px" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach($taxi->allStatuses() as $s): ?>
                <option value="<?=$s?>" <?=$s===$filterStatus?'selected':''?>><?=$taxi->statusLabel($s)?></option>
                <?php endforeach ?>
            </select>
        </form>
    </div>
    <?php if(empty($items)): ?><div class="empty"><div class="empty-icon">🚕</div><h3>No rides found</h3></div>
    <?php else: ?>
    <table class="data-table"><thead><tr>
        <th>Ride #</th><th>Customer</th><th>Route</th><th>Car</th><th>Price</th><th>Status</th><th>Payment</th><th>When</th><th></th>
    </tr></thead><tbody>
    <?php foreach($items as $rd): $c=$taxi->statusColor($rd['status']); ?>
    <tr>
        <td style="font-family:monospace;font-size:12px;font-weight:700"><?= e($rd['ride_number'])?></td>
        <td><div style="font-size:13px;font-weight:600"><?= e($rd['customer_name']?:'—')?></div><div style="font-size:11.5px;color:var(--muted)"><?= e($rd['customer_phone'])?></div></td>
        <td style="font-size:12.5px;max-width:200px"><div><?= e($rd['pickup_address'])?></div><div style="color:var(--muted)">→ <?= e($rd['destination'])?></div></td>
        <td style="font-size:13px"><?= e($taxi->carTypes()[$rd['car_type']]??$rd['car_type'])?></td>
        <td style="font-size:13px;font-weight:700"><?= $taxi->formatPrice((float)($rd['actual_price']??$rd['estimated_price']))?></td>
        <td><span style="background:<?=$c?>22;color:<?=$c?>;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;white-space:nowrap"><?=$taxi->statusLabel($rd['status'])?></span></td>
        <td><span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;background:<?=$rd['payment_status']==='paid'?'#d1fae5':'#fef3c7'?>;color:<?=$rd['payment_status']==='paid'?'#059669':'#92400e'?>"><?=$rd['payment_status']==='paid'?'Paid':'Unpaid'?></span></td>
        <td style="font-size:12px;color:var(--muted);white-space:nowrap"><?= date('d M, H:i',strtotime($rd['created_at']))?></td>
        <td><a href="<?= e($base)?>/manage/taxi/rides/<?=(int)$rd['id']?>" class="btn btn-ghost" style="font-size:12px">View</a></td>
    </tr>
    <?php endforeach ?></tbody></table>
    <?php if($pages>1): ?>
    <div style="padding:16px;display:flex;gap:6px;justify-content:center">
        <?php for($i=1;$i<=$pages;$i++): ?>
        <a href="?page=<?=$i?>&status=<?=urlencode($filterStatus)?>" style="padding:5px 12px;border-radius:6px;border:1px solid var(--border);font-size:13px;<?=$i===$page?'background:var(--accent);color:#fff;border-color:var(--accent)':''?>"><?=$i?></a>
        <?php endfor ?>
    </div>
    <?php endif ?>
    <?php endif ?>
</div>
