<?php
$pageTitle = 'Drivers';
$activeNav = 'delivery-drivers';
$topbarActions = '';
?>
<?php if (!empty($saved ?? false)): ?><div id="gc-flash" data-msg="Saved." data-icon="success" style="display:none"></div><?php endif ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">

<div class="card">
    <div class="card-header"><h3>All Drivers (<?= count($drivers) ?>)</h3></div>
    <?php if (empty($drivers)): ?>
    <div class="empty"><div class="empty-icon">🧑</div><h3>No drivers yet</h3></div>
    <?php else: ?>
    <table class="data-table"><thead><tr>
        <th>Name</th><th>Phone</th><th>Vehicle</th><th>Status</th><th></th>
    </tr></thead><tbody>
    <?php foreach ($drivers as $d): ?>
    <tr>
        <td><strong><?= e($d['name']) ?></strong><?php if($d['email']): ?><div style="font-size:11.5px;color:var(--muted)"><?= e($d['email']) ?></div><?php endif ?></td>
        <td><?= e($d['phone']) ?></td>
        <td style="font-size:13px"><?= e($d['vehicle_type']) ?> <?= e($d['vehicle_num'] ? '· '.$d['vehicle_num'] : '') ?></td>
        <td><span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;background:<?= $d['status']==='active'?'#d1fae5':'#fef2f2' ?>;color:<?= $d['status']==='active'?'#059669':'#b91c1c' ?>"><?= ucfirst($d['status']) ?></span></td>
        <td>
            <button type="button" class="btn btn-ghost" style="font-size:12px" onclick="editDriver(<?= htmlspecialchars(json_encode($d), ENT_QUOTES) ?>)">Edit</button>
            <form method="POST" action="<?= e($base) ?>/manage/delivery/drivers/<?= (int)$d['id'] ?>/delete" style="display:inline">
                <button type="button" class="btn btn-danger" style="font-size:12px" onclick="gcConfirm(this,'Delete driver?','','Delete')">✕</button>
            </form>
        </td>
    </tr>
    <?php endforeach ?>
    </tbody></table>
    <?php endif ?>
</div>

<div class="card">
    <div class="card-header"><h3 id="driverFormTitle">Add Driver</h3></div>
    <div class="card-body">
        <form method="POST" action="<?= e($base) ?>/manage/delivery/drivers/create" id="driverForm" style="display:flex;flex-direction:column;gap:10px">
            <input type="hidden" name="_method" id="driverMethod" value="">
            <input type="text"  name="name"         class="form-input" placeholder="Full Name *"    required style="padding:8px 12px;font-size:13px">
            <input type="tel"   name="phone"         class="form-input" placeholder="Phone *"        required style="padding:8px 12px;font-size:13px">
            <input type="email" name="email"         class="form-input" placeholder="Email"                   style="padding:8px 12px;font-size:13px">
            <input type="text"  name="vehicle_type"  class="form-input" placeholder="Vehicle type (Car, Bike…)" style="padding:8px 12px;font-size:13px">
            <input type="text"  name="vehicle_num"   class="form-input" placeholder="Plate number"             style="padding:8px 12px;font-size:13px">
            <select name="status" class="form-select" style="padding:8px 12px;font-size:13px">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <button type="submit" class="btn btn-primary" style="font-size:13px">Save Driver</button>
            <button type="button" class="btn btn-ghost" style="font-size:13px;display:none" id="cancelEdit" onclick="resetDriverForm()">Cancel</button>
        </form>
    </div>
</div>

</div>
<script>
function editDriver(d) {
    var f = document.getElementById('driverForm');
    f.action = '<?= e($base) ?>/manage/delivery/drivers/' + d.id + '/update';
    f.name.value         = d.name;
    f.phone.value        = d.phone;
    f.email.value        = d.email;
    f.vehicle_type.value = d.vehicle_type;
    f.vehicle_num.value  = d.vehicle_num;
    f.status.value       = d.status;
    document.getElementById('driverFormTitle').textContent = 'Edit Driver';
    document.getElementById('cancelEdit').style.display = '';
    f.scrollIntoView({behavior:'smooth'});
}
function resetDriverForm() {
    var f = document.getElementById('driverForm');
    f.action = '<?= e($base) ?>/manage/delivery/drivers/create';
    f.reset();
    document.getElementById('driverFormTitle').textContent = 'Add Driver';
    document.getElementById('cancelEdit').style.display = 'none';
}
</script>
