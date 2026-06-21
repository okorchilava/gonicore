<?php
$pageTitle     = 'Tariffs';
$activeNav     = 'taxi-tariffs';
$topbarActions = '';
$dayNames = $taxi->dayNames();
$carTypes = array_merge(['' => '🚕 All Types'], $taxi->carTypes());
?>
<?php if ($saved): ?><div id="gc-flash" data-msg="Tariff saved." data-icon="success" style="display:none"></div><?php endif ?>
<?php if ($deleted): ?><div id="gc-flash" data-msg="Tariff deleted." data-icon="success" style="display:none"></div><?php endif ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

<div class="card">
    <div class="card-header">
        <h3>Tariffs <span style="font-size:13px;color:var(--muted);font-weight:400">(<?= count($tariffs) ?>)</span></h3>
        <div style="font-size:12px;color:var(--muted)">Higher priority wins. Car-specific beats "All Types".</div>
    </div>
    <?php if (empty($tariffs)): ?>
    <div class="empty"><div class="empty-icon">💰</div><h3>No tariffs yet</h3>
        <p style="color:var(--muted)">Add a tariff to override the global fare settings.</p>
    </div>
    <?php else: ?>
    <table class="data-table"><thead><tr>
        <th>Name</th><th>Car Type</th><th>Base</th><th>Per km</th><th>Min</th><th>Surge</th><th>Hours</th><th>Days</th><th>Pri</th><th>Active</th><th></th>
    </tr></thead><tbody>
    <?php foreach ($tariffs as $t):
        $days = str_split((string)$t['days']);
    ?>
    <tr id="tr-<?= (int)$t['id'] ?>">
        <td style="font-weight:600"><?= e($t['name']) ?></td>
        <td style="font-size:13px"><?= e($carTypes[$t['car_type']] ?? '🚕 All') ?></td>
        <td style="font-size:13px"><?= number_format((float)$t['base_fare'],2) ?></td>
        <td style="font-size:13px"><?= number_format((float)$t['price_per_km'],4) ?></td>
        <td style="font-size:13px"><?= number_format((float)$t['min_fare'],2) ?></td>
        <td style="font-size:13px"><?= number_format((float)$t['surge_multiplier'],2) ?>×</td>
        <td style="font-size:12px;color:var(--muted)">
            <?= $t['time_from'] && $t['time_to'] ? substr($t['time_from'],0,5).'–'.substr($t['time_to'],0,5) : 'All day' ?>
        </td>
        <td style="font-size:12px">
            <?php foreach ($dayNames as $n => $d): ?>
            <span style="padding:1px 3px;border-radius:3px;font-size:10px;<?= in_array((string)$n,$days,true)?'background:var(--accent);color:#fff':'color:var(--muted)' ?>"><?= $d ?></span>
            <?php endforeach ?>
        </td>
        <td style="font-size:13px;color:var(--muted)"><?= (int)$t['priority'] ?></td>
        <td>
            <span style="font-size:20px" title="<?= $t['active']?'Active':'Inactive' ?>"><?= $t['active']?'🟢':'⚫' ?></span>
        </td>
        <td style="white-space:nowrap">
            <button class="btn btn-ghost" style="font-size:12px" onclick="editTariff(<?= (int)$t['id'] ?>)">Edit</button>
            <form method="POST" action="<?= e($base) ?>/manage/taxi/tariffs/<?= (int)$t['id'] ?>/delete" style="display:inline">
                <button type="button" class="btn btn-danger" style="font-size:12px"
                    onclick="gcConfirm(this,'Delete tariff?','This will remove the tariff permanently.','Delete')">Delete</button>
            </form>
        </td>
    </tr>
    <!-- Edit form (hidden) -->
    <tr id="edit-<?= (int)$t['id'] ?>" style="display:none">
        <td colspan="11" style="padding:16px;background:var(--surface)">
        <form method="POST" action="<?= e($base) ?>/manage/taxi/tariffs/<?= (int)$t['id'] ?>/update">
        <?php $editDays = str_split((string)$t['days']); ?>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:12px">
            <div><label class="form-label" style="font-size:12px">Name</label><input type="text" name="name" class="form-input" value="<?= e($t['name']) ?>" required></div>
            <div><label class="form-label" style="font-size:12px">Car Type</label><select name="car_type" class="form-input"><?php foreach($carTypes as $v=>$l): ?><option value="<?= e($v) ?>"<?= $t['car_type']===$v?' selected':'' ?>><?= e($l) ?></option><?php endforeach ?></select></div>
            <div><label class="form-label" style="font-size:12px">Base Fare</label><input type="number" name="base_fare" class="form-input" step="0.01" min="0" value="<?= number_format((float)$t['base_fare'],2,'.','') ?>"></div>
            <div><label class="form-label" style="font-size:12px">Price/km</label><input type="number" name="price_per_km" class="form-input" step="0.0001" min="0" value="<?= number_format((float)$t['price_per_km'],4,'.','') ?>"></div>
            <div><label class="form-label" style="font-size:12px">Min Fare</label><input type="number" name="min_fare" class="form-input" step="0.01" min="0" value="<?= number_format((float)$t['min_fare'],2,'.','') ?>"></div>
            <div><label class="form-label" style="font-size:12px">Surge ×</label><input type="number" name="surge_multiplier" class="form-input" step="0.01" min="0.1" value="<?= number_format((float)$t['surge_multiplier'],2,'.','') ?>"></div>
            <div><label class="form-label" style="font-size:12px">Time From</label><input type="time" name="time_from" class="form-input" value="<?= e(substr((string)$t['time_from'],0,5)) ?>"></div>
            <div><label class="form-label" style="font-size:12px">Time To</label><input type="time" name="time_to" class="form-input" value="<?= e(substr((string)$t['time_to'],0,5)) ?>"></div>
            <div><label class="form-label" style="font-size:12px">Priority</label><input type="number" name="priority" class="form-input" value="<?= (int)$t['priority'] ?>"></div>
            <div style="display:flex;align-items:center;gap:8px;padding-top:24px">
                <input type="checkbox" name="active" value="1" <?= $t['active']?'checked':'' ?> style="width:16px;height:16px">
                <label style="font-size:13px">Active</label>
            </div>
        </div>
        <div style="margin-bottom:12px">
            <label class="form-label" style="font-size:12px">Days</label>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                <?php foreach($dayNames as $n=>$d): ?>
                <label style="display:flex;align-items:center;gap:4px;cursor:pointer;font-size:13px">
                    <input type="checkbox" name="days[]" value="<?= $n ?>" <?= in_array((string)$n,$editDays,true)?'checked':'' ?> style="width:15px;height:15px">
                    <?= $d ?>
                </label>
                <?php endforeach ?>
            </div>
        </div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary" style="font-size:13px">Save</button>
            <button type="button" class="btn btn-ghost" style="font-size:13px" onclick="editTariff(<?= (int)$t['id'] ?>)">Cancel</button>
        </div>
        </form>
        </td>
    </tr>
    <?php endforeach ?>
    </tbody></table>
    <?php endif ?>
</div>

<!-- New tariff form -->
<div class="card">
    <div class="card-header"><h3>+ New Tariff</h3></div>
    <form method="POST" action="<?= e($base) ?>/manage/taxi/tariffs/create">
    <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
        <div>
            <label class="form-label">Name <span style="color:#ef4444">*</span></label>
            <input type="text" name="name" class="form-input" required placeholder="e.g. Night Surge, Economy Flat…">
        </div>
        <div>
            <label class="form-label">Car Type</label>
            <select name="car_type" class="form-input">
                <?php foreach($carTypes as $v=>$l): ?><option value="<?= e($v) ?>"><?= e($l) ?></option><?php endforeach ?>
            </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <div><label class="form-label">Base Fare</label><input type="number" name="base_fare" class="form-input" step="0.01" min="0" value="5.00"></div>
            <div><label class="form-label">Price / km</label><input type="number" name="price_per_km" class="form-input" step="0.0001" min="0" value="1.5000"></div>
            <div><label class="form-label">Min Fare</label><input type="number" name="min_fare" class="form-input" step="0.01" min="0" value="5.00"></div>
            <div><label class="form-label">Surge ×</label><input type="number" name="surge_multiplier" class="form-input" step="0.01" min="0.1" value="1.00" placeholder="1.00 = no surge"></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <div><label class="form-label">Time From</label><input type="time" name="time_from" class="form-input"></div>
            <div><label class="form-label">Time To</label><input type="time" name="time_to" class="form-input"></div>
        </div>
        <div>
            <label class="form-label">Days</label>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                <?php foreach($dayNames as $n=>$d): ?>
                <label style="display:flex;align-items:center;gap:4px;cursor:pointer;font-size:13px">
                    <input type="checkbox" name="days[]" value="<?= $n ?>" checked style="width:15px;height:15px">
                    <?= $d ?>
                </label>
                <?php endforeach ?>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <div><label class="form-label">Priority</label><input type="number" name="priority" class="form-input" value="0" min="0"></div>
            <div style="display:flex;align-items:center;gap:8px;padding-top:24px">
                <input type="checkbox" name="active" value="1" checked style="width:16px;height:16px" id="new-active">
                <label for="new-active" style="font-size:13px;cursor:pointer">Active</label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%">Create Tariff</button>
    </div>
    </form>
</div>

</div>

<script>
function editTariff(id) {
    var tr = document.getElementById('tr-'+id);
    var ed = document.getElementById('edit-'+id);
    var visible = ed.style.display !== 'none';
    ed.style.display = visible ? 'none' : 'table-row';
}
</script>
