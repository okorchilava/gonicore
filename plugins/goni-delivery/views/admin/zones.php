<?php
$pageTitle = 'Delivery Zones';
$activeNav = 'delivery-zones';
$topbarActions = '';
?>
<?php if (!empty($saved)): ?><div id="gc-flash" data-msg="Zones saved." data-icon="success" style="display:none"></div><?php endif ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

<div class="card">
    <div class="card-header"><h3>Zones</h3></div>
    <?php if (empty($zones)): ?>
    <div class="empty"><div class="empty-icon">📍</div><h3>No zones defined</h3></div>
    <?php else: ?>
    <table class="data-table"><thead><tr><th>Zone</th><th>Delivery Fee</th><th>Min Order</th><th>ETA</th><th>Active</th><th></th></tr></thead><tbody>
    <?php foreach ($zones as $z): ?>
    <tr>
        <td style="font-weight:700"><?= e($z['name']) ?></td>
        <td><?= $delivery->formatPrice((float)$z['price']) ?></td>
        <td><?= $z['min_order'] > 0 ? $delivery->formatPrice((float)$z['min_order']) : '—' ?></td>
        <td><?= (int)$z['eta_minutes'] ?> min</td>
        <td><span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;background:<?= $z['active']?'#d1fae5':'#fef2f2' ?>;color:<?= $z['active']?'#059669':'#b91c1c' ?>"><?= $z['active']?'Active':'Off' ?></span></td>
        <td>
            <button type="button" class="btn btn-ghost" style="font-size:12px" onclick="editZone(<?= htmlspecialchars(json_encode($z), ENT_QUOTES) ?>)">Edit</button>
            <form method="POST" action="<?= e($base) ?>/manage/delivery/zones/<?= (int)$z['id'] ?>/delete" style="display:inline">
                <button type="button" class="btn btn-danger" style="font-size:12px" onclick="gcConfirm(this,'Delete zone?','','Delete')">✕</button>
            </form>
        </td>
    </tr>
    <?php endforeach ?>
    </tbody></table>
    <?php endif ?>
</div>

<div class="card">
    <div class="card-header"><h3 id="zoneFormTitle">Add Zone</h3></div>
    <div class="card-body">
        <form method="POST" action="<?= e($base) ?>/manage/delivery/zones/create" id="zoneForm" style="display:flex;flex-direction:column;gap:10px">
            <input type="text"   name="name"        class="form-input" placeholder="Zone name (e.g. City Center)" required style="padding:8px 12px;font-size:13px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                <input type="number" name="price"     class="form-input" placeholder="Delivery fee" min="0" step="0.01" style="padding:8px 12px;font-size:13px">
                <input type="number" name="min_order" class="form-input" placeholder="Min order"    min="0" step="0.01" style="padding:8px 12px;font-size:13px">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                <input type="number" name="eta_minutes" class="form-input" placeholder="ETA (min)" min="1" value="30" style="padding:8px 12px;font-size:13px">
                <input type="number" name="sort_order"  class="form-input" placeholder="Sort order" min="0" value="0"  style="padding:8px 12px;font-size:13px">
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                <input type="checkbox" name="active" value="1" checked> Active
            </label>
            <button type="submit" class="btn btn-primary" style="font-size:13px">Save Zone</button>
            <button type="button" class="btn btn-ghost" style="font-size:13px;display:none" id="cancelZone" onclick="resetZoneForm()">Cancel</button>
        </form>
    </div>
</div>

</div>
<script>
function editZone(z) {
    var f = document.getElementById('zoneForm');
    f.action = '<?= e($base) ?>/manage/delivery/zones/' + z.id + '/update';
    f.name.value        = z.name;
    f.price.value       = z.price;
    f.min_order.value   = z.min_order;
    f.eta_minutes.value = z.eta_minutes;
    f.sort_order.value  = z.sort_order;
    f.active.checked    = z.active == 1;
    document.getElementById('zoneFormTitle').textContent = 'Edit Zone';
    document.getElementById('cancelZone').style.display = '';
    f.scrollIntoView({behavior:'smooth'});
}
function resetZoneForm() {
    var f = document.getElementById('zoneForm');
    f.action = '<?= e($base) ?>/manage/delivery/zones/create';
    f.reset(); f.active.checked = true; f.eta_minutes.value = 30;
    document.getElementById('zoneFormTitle').textContent = 'Add Zone';
    document.getElementById('cancelZone').style.display = 'none';
}
</script>
