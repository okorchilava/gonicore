<?php $pageTitle='Fixed Routes'; $activeNav='taxi-routes'; $topbarActions=''; ?>
<?php if(!empty($saved)): ?><div id="gc-flash" data-msg="Saved." data-icon="success" style="display:none"></div><?php endif ?>
<div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">
<div class="card">
    <div class="card-header"><h3>Routes</h3></div>
    <?php if(empty($routes)): ?><div class="empty"><div class="empty-icon">🗺</div><h3>No fixed routes</h3><p>Add routes for quick price selection.</p></div>
    <?php else: ?>
    <table class="data-table"><thead><tr><th>Name</th><th>From → To</th><th>Distance</th><th>Price</th><th>Car</th><th>Active</th><th></th></tr></thead><tbody>
    <?php foreach($routes as $rt): ?>
    <tr>
        <td style="font-weight:700"><?= e($rt['name'])?></td>
        <td style="font-size:12.5px;color:var(--muted)"><?= e($rt['from_location'])?> → <?= e($rt['to_location'])?></td>
        <td><?= number_format((float)$rt['distance_km'],1)?> km</td>
        <td style="font-weight:700"><?= $taxi->formatPrice((float)$rt['price'])?></td>
        <td style="font-size:13px"><?= e($taxi->carTypes()[$rt['car_type']]??($rt['car_type']?:'Any'))?></td>
        <td><span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;background:<?=$rt['active']?'#d1fae5':'#fef2f2'?>;color:<?=$rt['active']?'#059669':'#b91c1c'?>"><?=$rt['active']?'Active':'Off'?></span></td>
        <td>
            <button type="button" class="btn btn-ghost" style="font-size:12px" onclick="editRoute(<?= htmlspecialchars(json_encode($rt),ENT_QUOTES)?>)">Edit</button>
            <form method="POST" action="<?= e($base)?>/manage/taxi/routes/<?=(int)$rt['id']?>/delete" style="display:inline">
                <button type="button" class="btn btn-danger" style="font-size:12px" onclick="gcConfirm(this,'Delete route?','','Delete')">✕</button>
            </form>
        </td>
    </tr>
    <?php endforeach ?></tbody></table>
    <?php endif ?>
</div>
<div class="card">
    <div class="card-header"><h3 id="rTitle">Add Route</h3></div>
    <div class="card-body">
        <form method="POST" action="<?= e($base)?>/manage/taxi/routes/create" id="rForm" style="display:flex;flex-direction:column;gap:9px">
            <input type="text"   name="name"          class="form-input" placeholder="Route name" required style="padding:8px 12px;font-size:13px">
            <input type="text"   name="from_location"  class="form-input" placeholder="From" style="padding:8px 12px;font-size:13px">
            <input type="text"   name="to_location"    class="form-input" placeholder="To"   style="padding:8px 12px;font-size:13px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                <input type="number" name="distance_km" class="form-input" placeholder="km" min="0" step="0.1" style="padding:8px 12px;font-size:13px">
                <input type="number" name="price"       class="form-input" placeholder="Price" min="0" step="0.01" style="padding:8px 12px;font-size:13px">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                <select name="car_type" class="form-select" style="padding:8px 12px;font-size:13px">
                    <option value="">Any car type</option>
                    <?php foreach($taxi->carTypes() as $v=>$l): ?><option value="<?=$v?>"><?= e($l)?></option><?php endforeach ?>
                </select>
                <input type="number" name="sort_order" class="form-input" placeholder="Sort" value="0" style="padding:8px 12px;font-size:13px">
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                <input type="checkbox" name="active" value="1" checked> Active
            </label>
            <button type="submit" class="btn btn-primary" style="font-size:13px">Save Route</button>
            <button type="button" class="btn btn-ghost" style="font-size:13px;display:none" id="rCancel" onclick="resetRouteForm()">Cancel</button>
        </form>
    </div>
</div>
</div>
<script>
function editRoute(rt){
    var f=document.getElementById('rForm');
    f.action='<?= e($base)?>/manage/taxi/routes/'+rt.id+'/update';
    f.name.value=rt.name;f.from_location.value=rt.from_location;f.to_location.value=rt.to_location;
    f.distance_km.value=rt.distance_km;f.price.value=rt.price;f.car_type.value=rt.car_type;
    f.sort_order.value=rt.sort_order;f.active.checked=rt.active==1;
    document.getElementById('rTitle').textContent='Edit Route';
    document.getElementById('rCancel').style.display='';
    f.scrollIntoView({behavior:'smooth'});
}
function resetRouteForm(){
    var f=document.getElementById('rForm');
    f.action='<?= e($base)?>/manage/taxi/routes/create';
    f.reset();f.active.checked=true;
    document.getElementById('rTitle').textContent='Add Route';
    document.getElementById('rCancel').style.display='none';
}
</script>
