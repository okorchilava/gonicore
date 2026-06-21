<?php
$pageTitle = $isNew ? 'Add Vendor' : 'Edit Vendor';
$activeNav = 'delivery-vendors';
$topbarActions = !$isNew ? '<form method="POST" action="'.e($base).'/manage/delivery/vendors/'.(int)$vendor['id'].'/token" style="display:inline"><button class="btn btn-ghost" style="font-size:12px" onclick="return confirm(\'Regenerate token?\')">🔑 Regen Token</button></form>' : '';
$v = $vendor ?? [];
?>
<?php if(($_GET['saved']??'')==='1'): ?><div id="gc-flash" data-msg="Vendor saved." data-icon="success" style="display:none"></div><?php endif ?>
<?php if(($_GET['token']??'')==='1'): ?><div id="gc-flash" data-msg="Token regenerated." data-icon="success" style="display:none"></div><?php endif ?>

<div style="max-width:680px">
<form method="POST" action="<?= $isNew ? e($base).'/manage/delivery/vendors/create' : e($base).'/manage/delivery/vendors/'.(int)$v['id'].'/update' ?>">

<div class="card" style="margin-bottom:16px">
  <div class="card-header"><h3>Basic Info</h3></div>
  <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div class="form-group" style="margin:0;grid-column:1/-1"><label class="form-label">Vendor Name *</label><input type="text" name="name" class="form-input" value="<?= e($v['name']??'')?>" required></div>
    <div class="form-group" style="margin:0"><label class="form-label">Slug (URL)</label><input type="text" name="slug" class="form-input" value="<?= e($v['slug']??'')?>" placeholder="auto-generated"></div>
    <div class="form-group" style="margin:0">
      <label class="form-label">Category</label>
      <select name="category" class="form-input">
        <?php foreach(['restaurant'=>'🍽 Restaurant','grocery'=>'🛒 Grocery','pharmacy'=>'💊 Pharmacy','cafe'=>'☕ Cafe','bakery'=>'🥐 Bakery','other'=>'📦 Other'] as $k=>$l): ?>
        <option value="<?= e($k)?>" <?= ($v['category']??'restaurant')===$k?'selected':''?>><?= e($l)?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="form-group" style="margin:0;grid-column:1/-1"><label class="form-label">Description</label><textarea name="description" class="form-input" rows="2"><?= e($v['description']??'')?></textarea></div>
    <div class="form-group" style="margin:0"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-input" value="<?= e($v['phone']??'')?>"></div>
    <div class="form-group" style="margin:0"><label class="form-label">Email</label><input type="email" name="email" class="form-input" value="<?= e($v['email']??'')?>"></div>
    <div class="form-group" style="margin:0;grid-column:1/-1">
      <label class="form-label">მისამართი</label>
      <input type="text" name="address" id="vendorAddress" class="form-input" value="<?= e($v['address']??'')?>" placeholder="რუკაზე pin-ის დასმით ავტო-შეივსება">
    </div>
    <input type="hidden" name="lat" id="vendorLat" value="<?= e($v['lat']??'')?>">
    <input type="hidden" name="lng" id="vendorLng" value="<?= e($v['lng']??'')?>">
    <div class="form-group" style="margin:0;grid-column:1/-1">
      <label class="form-label" style="display:flex;align-items:center;justify-content:space-between">
        <span>📍 მდებარეობა რუკაზე</span>
        <span id="coordsDisplay" style="font-size:11px;color:#64748b;font-weight:400"><?= ($v['lat']??'') ? e(round((float)$v['lat'],5)).', '.e(round((float)$v['lng'],5)) : 'pin-ი არ არის დასმული' ?></span>
      </label>
      <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
      <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
      <style>
        #vendorMap { position:relative; z-index:0; isolation:isolate; }
        #vendorMap .leaflet-pane,
        #vendorMap .leaflet-top,
        #vendorMap .leaflet-bottom { z-index:auto; }
        #vendorMap .leaflet-control { z-index:auto; }
      </style>
      <div id="vendorMap" style="height:320px;border-radius:10px;border:1.5px solid #e2e8f0;overflow:hidden;margin-top:6px;position:relative;z-index:0;isolation:isolate"></div>
      <p style="font-size:11px;color:#64748b;margin:6px 0 0">რუკაზე კლიკი — pin გადაადგილდება, მისამართი ავტო-შეივსება</p>
    </div>
    <script>
    (function(){
      var initLat = parseFloat(<?= json_encode($v['lat']??null) ?>) || 41.6938;
      var initLng = parseFloat(<?= json_encode($v['lng']??null) ?>) || 44.8015;
      var hasPin  = <?= (!empty($v['lat'])&&!empty($v['lng']))?'true':'false' ?>;

      var map = L.map('vendorMap').setView([initLat, initLng], hasPin ? 16 : 12);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors', maxZoom: 19
      }).addTo(map);

      var marker = hasPin
        ? L.marker([initLat, initLng], {draggable:true}).addTo(map)
        : null;

      function setPin(lat, lng){
        if(marker){ marker.setLatLng([lat,lng]); }
        else { marker = L.marker([lat,lng],{draggable:true}).addTo(map); bindDrag(); }
        document.getElementById('vendorLat').value = lat.toFixed(7);
        document.getElementById('vendorLng').value = lng.toFixed(7);
        document.getElementById('coordsDisplay').textContent = lat.toFixed(5)+', '+lng.toFixed(5);
        // Reverse geocode via Nominatim
        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat='+lat+'&lon='+lng+'&zoom=18&addressdetails=1',{headers:{'Accept-Language':'ka'}})
          .then(function(r){return r.json();}).then(function(d){
            if(d&&d.display_name){
              var addr = document.getElementById('vendorAddress');
              if(!addr.dataset.userEdited) addr.value = d.display_name;
            }
          }).catch(function(){});
      }

      function bindDrag(){
        marker.on('dragend',function(e){ var ll=e.target.getLatLng(); setPin(ll.lat,ll.lng); });
      }
      if(marker) bindDrag();

      map.on('click',function(e){ setPin(e.latlng.lat,e.latlng.lng); map.setView(e.latlng,Math.max(map.getZoom(),16)); });

      // Mark address as user-edited if they type manually
      document.getElementById('vendorAddress').addEventListener('input',function(){ this.dataset.userEdited='1'; });
    })();
    </script>
    <div class="form-group" style="margin:0;grid-column:1/-1"><label class="form-label">Cuisine Tags (comma-separated)</label><input type="text" name="cuisine_tags" class="form-input" value="<?= e($v['cuisine_tags']??'')?>" placeholder="Georgian, Pizza, Burgers"></div>
  </div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="card-header"><h3>Hours & Operations</h3></div>
  <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div class="form-group" style="margin:0"><label class="form-label">Open Time</label><input type="time" name="open_time" class="form-input" value="<?= e($v['open_time']??'')?>"></div>
    <div class="form-group" style="margin:0"><label class="form-label">Close Time</label><input type="time" name="close_time" class="form-input" value="<?= e($v['close_time']??'')?>"></div>
    <div class="form-group" style="margin:0;grid-column:1/-1">
      <label class="form-label">Working Days</label>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px">
        <?php $days=[1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'];
        $daysOpen = $v['days_open'] ?? '1234567';
        foreach($days as $n=>$l): ?>
        <label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-size:13px">
          <input type="checkbox" name="days_open[]" value="<?= $n?>" <?= str_contains($daysOpen,(string)$n)?'checked':''?>>
          <?= $l?>
        </label>
        <?php endforeach ?>
      </div>
    </div>
    <div class="form-group" style="margin:0"><label class="form-label">Default Prep Time (min)</label><input type="number" name="prep_time_min" class="form-input" value="<?= (int)($v['prep_time_min']??20)?>" min="1"></div>
    <div class="form-group" style="margin:0">
      <label class="form-label">Status</label>
      <select name="status" class="form-input">
        <option value="active" <?= ($v['status']??'active')==='active'?'selected':''?>>Active</option>
        <option value="busy"   <?= ($v['status']??'')==='busy'?'selected':''?>>Busy</option>
        <option value="inactive" <?= ($v['status']??'')==='inactive'?'selected':''?>>Inactive</option>
      </select>
    </div>
    <label style="display:flex;align-items:center;gap:8px;font-size:13px;grid-column:1/-1">
      <input type="checkbox" name="is_featured" <?= !empty($v['is_featured'])?'checked':''?>>
      <strong>Featured</strong> (show at top of list)
    </label>
  </div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="card-header"><h3>Pricing</h3></div>
  <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
    <div class="form-group" style="margin:0"><label class="form-label">Min Order (₾)</label><input type="number" name="min_order" class="form-input" value="<?= number_format((float)($v['min_order']??0),2)?>" min="0" step="0.01"></div>
    <div class="form-group" style="margin:0"><label class="form-label">Delivery Fee (₾)</label><input type="number" name="delivery_fee" class="form-input" value="<?= number_format((float)($v['delivery_fee']??3),2)?>" min="0" step="0.01"></div>
    <div class="form-group" style="margin:0"><label class="form-label">Free Delivery From (₾)</label><input type="number" name="free_delivery_threshold" class="form-input" value="<?= number_format((float)($v['free_delivery_threshold']??0),2)?>" min="0" step="0.01"></div>
    <div class="form-group" style="margin:0"><label class="form-label">Platform Commission (%)</label><input type="number" name="commission_pct" class="form-input" value="<?= number_format((float)($v['commission_pct']??15),1)?>" min="0" max="100" step="0.1"></div>
  </div>
</div>

<div style="display:flex;gap:10px">
  <button type="submit" class="btn btn-primary"><?= $isNew ? '＋ Create Vendor' : '💾 Save' ?></button>
  <a href="<?= e($base)?>/manage/delivery/vendors" class="btn btn-ghost">Cancel</a>
  <?php if(!$isNew): ?>
  <form method="POST" action="<?= e($base)?>/manage/delivery/vendors/<?= (int)$v['id']?>/delete" style="margin-left:auto">
    <button type="submit" class="btn btn-danger" onclick="return confirm('Delete vendor?')">🗑 Delete</button>
  </form>
  <?php endif ?>
</div>
</form>
</div>
