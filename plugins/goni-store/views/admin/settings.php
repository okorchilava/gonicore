<?php $pageTitle='Store Settings'; $activeNav='store'; $topbarActions=''; $s=$settings??[]; ?>
<div style="display:flex;gap:8px;margin-bottom:20px">
    <a href="<?= e($base) ?>/manage/store" style="padding:7px 14px;background:#fff;border:1px solid var(--border);border-radius:7px;font-size:13px;font-weight:600;color:var(--muted);text-decoration:none">Dashboard</a>
    <a href="<?= e($base) ?>/manage/store/settings" style="padding:7px 14px;background:var(--accent);color:#fff;border:1px solid var(--accent);border-radius:7px;font-size:13px;font-weight:600;text-decoration:none">Settings</a>
</div>
<?php if ($success??null): ?><div class="alert alert-success" style="margin-bottom:16px"><?= e($success) ?></div><?php endif ?>
<form method="POST" action="<?= e($base) ?>/manage/store/settings">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:900px">
<div class="card"><div class="card-header"><h3>Currency</h3></div><div class="card-body" style="padding:16px;display:flex;flex-direction:column;gap:12px">
<?php foreach ([['currency','Currency Code','USD'],['currency_symbol','Symbol','$'],['thousand_sep','Thousand Separator',','],['decimal_sep','Decimal Separator','.'],['decimals','Decimal Places','2']] as [$k,$l,$d]): ?>
<div class="form-group"><label class="form-label"><?= $l ?></label><input type="text" name="<?= $k ?>" class="form-input" value="<?= e($s[$k]??$d) ?>"></div>
<?php endforeach ?>
<div class="form-group"><label class="form-label">Symbol Position</label><select name="currency_position" class="form-select">
<?php foreach (['before'=>'Before price ($10)','after'=>'After price (10$)'] as $v=>$l): ?><option value="<?= $v ?>" <?= ($s['currency_position']??'before')===$v?'selected':'' ?>><?= $l ?></option><?php endforeach ?>
</select></div>
</div></div>

<div class="card"><div class="card-header"><h3>Pages & URLs</h3></div><div class="card-body" style="padding:16px;display:flex;flex-direction:column;gap:12px">
<?php foreach ([['shop_page_slug','Shop Page Slug','shop'],['cart_page_slug','Cart Page Slug','cart'],['checkout_page_slug','Checkout Page Slug','checkout'],['products_per_page','Products Per Page','12']] as [$k,$l,$d]): ?>
<div class="form-group"><label class="form-label"><?= $l ?></label><input type="text" name="<?= $k ?>" class="form-input" value="<?= e($s[$k]??$d) ?>"></div>
<?php endforeach ?>
<div class="form-group"><label class="form-label">Shop Layout</label><select name="shop_layout" class="form-select">
<option value="grid" <?= ($s['shop_layout']??'grid')==='grid'?'selected':'' ?>>Grid</option>
<option value="list" <?= ($s['shop_layout']??'grid')==='list'?'selected':'' ?>>List</option>
</select></div>
</div></div>

<div class="card"><div class="card-header"><h3>Tax & Shipping</h3></div><div class="card-body" style="padding:16px;display:flex;flex-direction:column;gap:12px">
<?php foreach ([['tax_rate','Tax Rate (%)','0'],['shipping_cost','Flat Shipping Cost','0'],['free_shipping_min','Free Shipping Min Order (0=off)','0']] as [$k,$l,$d]): ?>
<div class="form-group"><label class="form-label"><?= $l ?></label><input type="number" name="<?= $k ?>" class="form-input" step="0.01" value="<?= e($s[$k]??$d) ?>"></div>
<?php endforeach ?>
<div style="margin-top:4px"><label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13.5px"><input type="checkbox" name="tax_included" value="1" <?= !empty($s['tax_included'])&&$s['tax_included']==='1'?'checked':'' ?>> Prices include tax</label></div>
</div></div>

<div class="card"><div class="card-header"><h3>Email</h3></div><div class="card-body" style="padding:16px;display:flex;flex-direction:column;gap:12px">
<?php foreach ([['order_email','New Order Notification Email',''],['from_email','From Email','']] as [$k,$l,$d]): ?>
<div class="form-group"><label class="form-label"><?= $l ?></label><input type="email" name="<?= $k ?>" class="form-input" value="<?= e($s[$k]??$d) ?>"></div>
<?php endforeach ?>
<div><label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13.5px"><input type="checkbox" name="allow_guest_checkout" value="1" <?= ($s['allow_guest_checkout']??'1')==='1'?'checked':'' ?>> Allow guest checkout</label></div>
</div></div>
</div>
<div style="margin-top:20px"><button type="submit" class="btn btn-primary" style="padding:11px 32px">Save Settings</button></div>
</form>
