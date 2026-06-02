<?php $pageTitle='Coupons — GoniStore'; $activeNav='store'; $topbarActions=''; ?>
<div style="display:flex;gap:8px;margin-bottom:20px">
    <a href="<?= e($base) ?>/manage/store" style="padding:7px 14px;background:#fff;border:1px solid var(--border);border-radius:7px;font-size:13px;font-weight:600;color:var(--muted);text-decoration:none">Dashboard</a>
    <a href="<?= e($base) ?>/manage/store/products" style="padding:7px 14px;background:#fff;border:1px solid var(--border);border-radius:7px;font-size:13px;font-weight:600;color:var(--muted);text-decoration:none">Products</a>
    <a href="<?= e($base) ?>/manage/store/orders" style="padding:7px 14px;background:#fff;border:1px solid var(--border);border-radius:7px;font-size:13px;font-weight:600;color:var(--muted);text-decoration:none">Orders</a>
    <a href="<?= e($base) ?>/manage/store/coupons" style="padding:7px 14px;background:var(--accent);color:#fff;border:1px solid var(--accent);border-radius:7px;font-size:13px;font-weight:600;text-decoration:none">Coupons</a>
    <a href="<?= e($base) ?>/manage/store/settings" style="padding:7px 14px;background:#fff;border:1px solid var(--border);border-radius:7px;font-size:13px;font-weight:600;color:var(--muted);text-decoration:none">Settings</a>
</div>
<?php if ($success??null): ?><div class="alert alert-success" style="margin-bottom:16px"><?= e($success) ?></div><?php endif ?>
<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">
<div class="card" style="padding:0">
<table style="width:100%;border-collapse:collapse">
<thead><tr style="border-bottom:1px solid var(--border);font-size:12px;color:var(--muted);text-transform:uppercase">
<th style="padding:12px 16px;text-align:left">Code</th><th style="padding:12px 16px;text-align:left">Type</th><th style="padding:12px 16px;text-align:right">Value</th><th style="padding:12px 16px;text-align:center">Used</th><th style="padding:12px 16px;text-align:center">Active</th><th style="padding:12px 16px;text-align:right">Actions</th>
</tr></thead><tbody>
<?php if (empty($coupons)): ?>
<tr><td colspan="6" style="padding:32px;text-align:center;color:var(--muted)">No coupons.</td></tr>
<?php else: foreach ($coupons as $cp): ?>
<tr style="border-bottom:1px solid var(--border)">
<td style="padding:10px 16px;font-family:monospace;font-weight:700"><?= e($cp['code']) ?></td>
<td style="padding:10px 16px;color:var(--muted);font-size:13px"><?= $cp['type']==='percent'?'%':'Fixed' ?></td>
<td style="padding:10px 16px;text-align:right;font-weight:600"><?= $cp['type']==='percent'?e($cp['value']).'%':'$'.number_format((float)$cp['value'],2) ?></td>
<td style="padding:10px 16px;text-align:center;color:var(--muted)"><?= (int)$cp['used'] ?><?= $cp['max_uses']?'/'.e($cp['max_uses']):'' ?></td>
<td style="padding:10px 16px;text-align:center"><?= $cp['active']?'✓':'✕' ?></td>
<td style="padding:10px 16px;text-align:right">
<button onclick="editCoupon(<?= htmlspecialchars(json_encode($cp),ENT_QUOTES) ?>)" class="btn btn-ghost" style="font-size:12px;padding:5px 10px">Edit</button>
<form method="POST" action="<?= e($base) ?>/manage/store/coupons/<?= (int)$cp['id'] ?>/delete" style="display:inline" onsubmit="return confirm('Delete?')">
<button class="btn btn-danger" style="font-size:12px;padding:5px 10px">✕</button></form>
</td></tr>
<?php endforeach; endif ?>
</tbody></table></div>
<div class="card"><div class="card-header"><h3 id="cpFormTitle">New Coupon</h3></div>
<div class="card-body" style="padding:16px">
<form method="POST" action="<?= e($base) ?>/manage/store/coupons/save" id="cpForm">
<input type="hidden" name="id" id="cpId">
<div class="form-group" style="margin-bottom:10px"><label class="form-label">Code *</label><input type="text" name="code" id="cpCode" class="form-input" style="text-transform:uppercase" required></div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
<div class="form-group"><label class="form-label">Type</label><select name="type" id="cpType" class="form-select"><option value="percent">Percent (%)</option><option value="fixed">Fixed ($)</option></select></div>
<div class="form-group"><label class="form-label">Value</label><input type="number" name="value" id="cpValue" class="form-input" step="0.01" required></div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
<div class="form-group"><label class="form-label">Min Order</label><input type="number" name="min_order" id="cpMin" class="form-input" step="0.01" value="0"></div>
<div class="form-group"><label class="form-label">Max Uses</label><input type="number" name="max_uses" id="cpMax" class="form-input" placeholder="Unlimited"></div>
</div>
<div class="form-group" style="margin-bottom:10px"><label class="form-label">Expires At</label><input type="date" name="expires_at" id="cpExp" class="form-input"></div>
<div style="margin-bottom:14px"><label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13.5px"><input type="checkbox" name="active" id="cpActive" value="1" checked> Active</label></div>
<div style="display:flex;gap:8px"><button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">Save</button><button type="button" onclick="resetCp()" class="btn btn-ghost">Cancel</button></div>
</form></div></div></div>
<script>
function editCoupon(c){
    document.getElementById('cpFormTitle').textContent='Edit Coupon';
    document.getElementById('cpId').value=c.id;document.getElementById('cpCode').value=c.code;
    document.getElementById('cpType').value=c.type;document.getElementById('cpValue').value=c.value;
    document.getElementById('cpMin').value=c.min_order;document.getElementById('cpMax').value=c.max_uses||'';
    document.getElementById('cpExp').value=c.expires_at||'';document.getElementById('cpActive').checked=!!parseInt(c.active);
}
function resetCp(){document.getElementById('cpFormTitle').textContent='New Coupon';document.getElementById('cpForm').reset();document.getElementById('cpId').value='';}
</script>
