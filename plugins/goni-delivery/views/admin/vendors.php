<?php $pageTitle='Vendors'; $activeNav='delivery-vendors'; $topbarActions='<a href="'.e($base).'/manage/delivery/vendors/create" class="btn btn-primary">＋ Add Vendor</a>'; ?>
<?php if(($_GET['deleted']??'')==='1'): ?><div id="gc-flash" data-msg="Vendor deleted." data-icon="success" style="display:none"></div><?php endif ?>
<div class="card">
  <div class="card-header"><h3>Vendors / Restaurants</h3></div>
  <?php if(empty($vendors)): ?>
  <div style="padding:40px;text-align:center;color:var(--muted)"><div style="font-size:40px;margin-bottom:12px">🏪</div><div>No vendors yet.</div><a href="<?= e($base)?>/manage/delivery/vendors/create" class="btn btn-primary" style="margin-top:16px">Add First Vendor</a></div>
  <?php else: ?>
  <table class="data-table">
    <thead><tr><th>Vendor</th><th>Category</th><th>Status</th><th>Rating</th><th>Orders</th><th>Commission</th><th></th></tr></thead>
    <tbody>
    <?php foreach($vendors as $v): ?>
    <tr>
      <td>
        <div style="font-weight:700"><?= e($v['name'])?></div>
        <div style="font-size:11px;color:var(--muted)"><?= e($v['address'])?></div>
      </td>
      <td><span style="font-size:12px;background:var(--surface2,#f1f5f9);padding:2px 8px;border-radius:999px"><?= e($v['category'])?></span></td>
      <td>
        <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;background:<?= $v['status']==='active'?'#f0fdf4':'#fef2f2'?>;color:<?= $v['status']==='active'?'#166534':'#b91c1c'?>">
          <?= $v['status']?>
        </span>
      </td>
      <td><?= $v['rating']>0 ? '★'.number_format((float)$v['rating'],1) : '—' ?></td>
      <td><?= (int)$v['total_orders']?></td>
      <td><?= number_format((float)$v['commission_pct'],1)?>%</td>
      <td style="white-space:nowrap">
        <a href="<?= e($base)?>/manage/delivery/vendors/<?= (int)$v['id']?>/catalog" class="btn btn-ghost" style="font-size:12px">📦 Catalog</a>
        <a href="<?= e($base)?>/manage/delivery/vendors/<?= (int)$v['id']?>" class="btn btn-ghost" style="font-size:12px">✏ Edit</a>
        <?php if(!empty($v['vendor_token'])): ?>
        <a href="<?= e($base)?>/delivery/portal/<?= e($v['vendor_token'])?>" target="_blank" class="btn btn-ghost" style="font-size:12px">🏪 Portal</a>
        <?php endif ?>
      </td>
    </tr>
    <?php endforeach ?>
    </tbody>
  </table>
  <?php endif ?>
</div>
