<?php
$pageTitle = 'Live Map · Delivery';
$activeNav = 'delivery-livemap';
$topbarActions = '<span style="font-size:12px;color:var(--muted)" id="lmLastUpdate">live · updates every 15s</span>';

$onlineDrivers  = array_filter($drivers, fn($d) => !empty($d['is_online']) || $d['status']==='busy');
$activeOrders   = array_filter($orders,  fn($o) => in_array($o['status'],['pending','accepted','picked_up','in_transit']));
$pendingOrders  = array_filter($orders,  fn($o) => $o['status']==='pending');
$inTransitOrders= array_filter($orders,  fn($o) => in_array($o['status'],['accepted','picked_up','in_transit']));
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
.lm-wrap{display:grid;grid-template-columns:320px 1fr;gap:0;height:calc(100vh - 112px);overflow:hidden;margin:-24px}
.lm-sidebar{overflow-y:auto;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column}
.lm-sidebar::-webkit-scrollbar{width:4px}
.lm-sidebar::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}
.lm-map{flex:1;position:relative}
#liveMap{width:100%;height:100%}
.lm-stats{display:grid;grid-template-columns:1fr 1fr;gap:1px;background:var(--border);border-bottom:1px solid var(--border);flex-shrink:0}
.lm-stat{background:var(--surface);padding:13px;text-align:center}
.lm-stat-val{font-size:20px;font-weight:900;margin-bottom:2px}
.lm-stat-lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px}
.lm-sec{padding:9px 14px 4px;font-size:10px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border)}
.lm-row{display:flex;align-items:center;gap:10px;padding:9px 14px;border-bottom:1px solid var(--border2,#1a2235);cursor:pointer;transition:background .1s}
.lm-row:hover{background:var(--s2,#1a2235)}
.lm-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.lm-dot.online{background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.2)}
.lm-dot.busy{background:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.2)}
.lm-dot.offline{background:#64748b}
.lm-name{font-size:13px;font-weight:700;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.lm-sub{font-size:11px;color:var(--muted)}
.lm-pill{font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;white-space:nowrap;flex-shrink:0}
.lm-overlay{position:absolute;top:14px;right:14px;z-index:500;display:flex;flex-direction:column;gap:8px}
.lm-btn{background:rgba(17,24,39,.9);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.1);color:#f1f5f9;border-radius:10px;padding:8px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;white-space:nowrap}
.lm-btn:hover{background:rgba(245,158,11,.8)}
.lm-btn.active{background:rgba(245,158,11,.9)}
.lm-empty{padding:20px 14px;text-align:center;color:var(--muted);font-size:13px}
.lm-vendor-badge{font-size:10px;padding:1px 6px;border-radius:999px;background:rgba(245,158,11,.15);color:#f59e0b;font-weight:700}
</style>

<div class="lm-wrap">

  <!-- Sidebar -->
  <div class="lm-sidebar">

    <!-- Stats strip -->
    <div class="lm-stats">
      <div class="lm-stat">
        <div class="lm-stat-val" style="color:#22c55e" id="lmOnlineCount"><?= count($onlineDrivers)?></div>
        <div class="lm-stat-lbl">Online Couriers</div>
      </div>
      <div class="lm-stat">
        <div class="lm-stat-val" style="color:#f59e0b" id="lmPendingCount"><?= count($pendingOrders)?></div>
        <div class="lm-stat-lbl">Pending Orders</div>
      </div>
      <div class="lm-stat">
        <div class="lm-stat-val" style="color:#3b82f6" id="lmActiveCount"><?= count($inTransitOrders)?></div>
        <div class="lm-stat-lbl">In Delivery</div>
      </div>
      <div class="lm-stat">
        <div class="lm-stat-val" id="lmTotalDrivers"><?= count($drivers)?></div>
        <div class="lm-stat-lbl">Total Couriers</div>
      </div>
    </div>

    <!-- Couriers -->
    <div class="lm-sec">🛵 Couriers</div>
    <?php if(empty($drivers)): ?>
    <div class="lm-empty">კურიერები არ არის</div>
    <?php else: foreach($drivers as $d):
      $online  = !empty($d['is_online']);
      $busy    = $d['status'] === 'busy';
      $dotCls  = $busy ? 'busy' : ($online ? 'online' : 'offline');
      $hasGps  = !empty($d['current_lat']);
      $statusLabel = $busy ? 'Busy' : ($online ? 'Online' : 'Offline');
      $statusColor = $busy ? '#f59e0b' : ($online ? '#22c55e' : '#64748b');
    ?>
    <div class="lm-row" onclick="focusDriver(<?= (int)$d['id'] ?>)"
         data-lat="<?= $hasGps?(float)$d['current_lat']:'' ?>"
         data-lng="<?= $hasGps?(float)$d['current_lng']:'' ?>">
      <span class="lm-dot <?= $dotCls ?>"></span>
      <div style="flex:1;min-width:0">
        <div class="lm-name"><?= e($d['name']) ?></div>
        <div class="lm-sub">
          <?= e($d['vehicle_type']??'Courier') ?>
          <?= $d['vehicle_num'] ? ' · '.e($d['vehicle_num']) : '' ?>
          <?= $hasGps ? ' · 📍' : '' ?>
        </div>
      </div>
      <span class="lm-pill" style="background:<?= $statusColor?>22;color:<?= $statusColor?>"><?= $statusLabel?></span>
    </div>
    <?php endforeach; endif ?>

    <!-- Active Orders -->
    <div class="lm-sec">📦 Active Orders</div>
    <?php
    $shownOrders = array_filter($orders, fn($o)=>in_array($o['status'],['pending','accepted','picked_up','in_transit']));
    $statusColors = ['pending'=>'#f59e0b','accepted'=>'#3b82f6','picked_up'=>'#8b5cf6','in_transit'=>'#10b981'];
    $vendorMap = [];
    foreach($vendors as $v) $vendorMap[(int)$v['id']] = $v['name'];
    ?>
    <?php if(empty($shownOrders)): ?>
    <div class="lm-empty">აქტიური შეკვეთები არ არის</div>
    <?php else: foreach($shownOrders as $o):
      $sc = $statusColors[$o['status']] ?? '#64748b';
      $vendorName = $o['vendor_id'] ? ($vendorMap[(int)$o['vendor_id']] ?? '') : '';
    ?>
    <div class="lm-row" onclick="focusOrder(<?= (int)$o['id'] ?>)"
         data-pickup-lat="<?= !empty($o['pickup_lat'])?(float)$o['pickup_lat']:'' ?>"
         data-pickup-lng="<?= !empty($o['pickup_lng'])?(float)$o['pickup_lng']:'' ?>"
         data-delivery-lat="<?= !empty($o['delivery_lat'])?(float)$o['delivery_lat']:'' ?>"
         data-delivery-lng="<?= !empty($o['delivery_lng'])?(float)$o['delivery_lng']:'' ?>">
      <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px;flex-wrap:wrap">
          <span class="lm-pill" style="background:<?=$sc?>22;color:<?=$sc?>"><?= ucfirst(str_replace('_',' ',$o['status']))?></span>
          <span style="font-size:11px;font-family:monospace;color:var(--muted)"><?= e($o['order_number'])?></span>
          <?php if($vendorName): ?><span class="lm-vendor-badge"><?= e($vendorName)?></span><?php endif ?>
        </div>
        <div class="lm-name" style="font-size:12px">📍 <?= e(mb_substr($o['pickup_address']??'',0,30))?></div>
        <div class="lm-sub">🏁 <?= e(mb_substr($o['delivery_address']??'',0,30))?></div>
      </div>
      <div style="font-size:12px;font-weight:700;color:var(--accent);flex-shrink:0"><?= number_format((float)($o['price']??0),2).$sym?></div>
    </div>
    <?php endforeach; endif ?>

    <!-- Vendors on map -->
    <div class="lm-sec">🏪 Vendors</div>
    <?php foreach(array_filter($vendors,fn($v)=>!empty($v['lat'])) as $v): ?>
    <div class="lm-row" onclick="focusVendor(<?= (float)$v['lat']?>,<?= (float)$v['lng']?>,<?= json_encode($v['name'])?>)">
      <span class="lm-dot" style="background:<?= $v['status']==='active'?'#f59e0b':'#64748b'?>"></span>
      <div style="flex:1;min-width:0">
        <div class="lm-name"><?= e($v['name'])?></div>
        <div class="lm-sub"><?= e($v['category'])?> · <?= e(mb_substr($v['address'],0,28))?></div>
      </div>
      <span class="lm-pill" style="background:<?= $v['status']==='active'?'rgba(245,158,11,.15)':'rgba(100,116,139,.1)'?>;color:<?= $v['status']==='active'?'#f59e0b':'#64748b'?>"><?= $v['status']?></span>
    </div>
    <?php endforeach ?>

  </div>

  <!-- Map -->
  <div class="lm-map">
    <div id="liveMap"></div>
    <div class="lm-overlay">
      <button class="lm-btn active" id="btnCouriers" onclick="toggleLayer('couriers')">🛵 Couriers</button>
      <button class="lm-btn active" id="btnOrders"   onclick="toggleLayer('orders')">📦 Orders</button>
      <button class="lm-btn active" id="btnVendors"  onclick="toggleLayer('vendors')">🏪 Vendors</button>
      <button class="lm-btn" id="btnHeatmap"         onclick="toggleHeatmap()">🌡 Heatmap</button>
    </div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var BASE     = <?= json_encode($base) ?>;
var SYM      = <?= json_encode($sym) ?>;
var DRIVERS  = <?= json_encode(array_values($drivers)) ?>;
var ORDERS   = <?= json_encode(array_values($orders)) ?>;
var VENDORS  = <?= json_encode(array_values($vendors)) ?>;

var map = L.map('liveMap',{zoomControl:true,attributionControl:false}).setView([41.6938,44.8015],13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19}).addTo(map);

var driverMarkers={}, orderMarkers={}, vendorMarkers={};
var showCouriers=true, showOrders=true, showVendors=true, showHeatmap=false;
var heatLayer=null;

// ── Icons ────────────────────────────────────────────────────────────────────
function courierIcon(d){
    var color = d.status==='busy'?'#f59e0b':(d.is_online?'#22c55e':'#64748b');
    return L.divIcon({
        html:'<div style="position:relative">'
            +'<div style="font-size:30px;line-height:1;filter:drop-shadow(0 2px 6px rgba(0,0,0,.4))">🛵</div>'
            +'<div style="position:absolute;bottom:-1px;right:-1px;width:9px;height:9px;border-radius:50%;background:'+color+';border:2px solid #fff"></div>'
            +'</div>',
        iconSize:[38,38], iconAnchor:[19,19], className:''
    });
}
function orderIcon(status){
    var emoji={'pending':'⏳','accepted':'🚗','picked_up':'📦','in_transit':'🚗','delivered':'✅'}[status]||'📦';
    return L.divIcon({html:'<div style="font-size:24px;line-height:1;filter:drop-shadow(0 2px 4px rgba(0,0,0,.3))">'+emoji+'</div>',iconSize:[30,30],iconAnchor:[15,15],className:''});
}
function vendorIcon(){
    return L.divIcon({html:'<div style="font-size:26px;line-height:1;filter:drop-shadow(0 2px 4px rgba(0,0,0,.3))">🏪</div>',iconSize:[32,32],iconAnchor:[16,16],className:''});
}

// ── Render layers ─────────────────────────────────────────────────────────────
function renderCouriers(){
    Object.values(driverMarkers).forEach(function(m){map.removeLayer(m);}); driverMarkers={};
    if(!showCouriers) return;
    DRIVERS.forEach(function(d){
        if(!d.current_lat||!d.current_lng) return;
        var m = L.marker([parseFloat(d.current_lat),parseFloat(d.current_lng)],{icon:courierIcon(d)});
        m.bindPopup('<strong>'+d.name+'</strong><br>'+(d.vehicle_type||'')+(d.vehicle_num?' · '+d.vehicle_num:'')+'<br><span style="color:'+(d.status==='busy'?'#f59e0b':'#22c55e')+'">'+d.status+'</span>');
        m.addTo(map);
        driverMarkers[d.id]=m;
    });
}

function renderOrders(){
    Object.values(orderMarkers).forEach(function(m){map.removeLayer(m);}); orderMarkers={};
    if(!showOrders) return;
    ORDERS.forEach(function(o){
        if(!['pending','accepted','picked_up','in_transit'].includes(o.status)) return;
        // Pickup marker
        if(o.pickup_lat&&o.pickup_lng){
            var pm=L.marker([parseFloat(o.pickup_lat),parseFloat(o.pickup_lng)],{icon:orderIcon(o.status)});
            pm.bindPopup('<strong>'+o.order_number+'</strong><br>'+ucf(o.status.replace(/_/g,' '))+'<br>📍 '+(o.pickup_address||'?'));
            pm.addTo(map);
            orderMarkers['p_'+o.id]=pm;
        }
        // Delivery marker
        if(o.delivery_lat&&o.delivery_lng){
            var dm=L.marker([parseFloat(o.delivery_lat),parseFloat(o.delivery_lng)],{
                icon:L.divIcon({html:'<div style="font-size:22px;line-height:1">🏠</div>',iconSize:[28,28],iconAnchor:[14,14],className:''})
            });
            dm.bindPopup('Deliver to<br>🏁 '+(o.delivery_address||'?'));
            dm.addTo(map);
            orderMarkers['d_'+o.id]=dm;
        }
        // Line between pickup and delivery
        if(o.pickup_lat&&o.pickup_lng&&o.delivery_lat&&o.delivery_lng){
            var line=L.polyline([[parseFloat(o.pickup_lat),parseFloat(o.pickup_lng)],[parseFloat(o.delivery_lat),parseFloat(o.delivery_lng)]],{color:'#f59e0b',weight:2,opacity:.5,dashArray:'6,4'});
            line.addTo(map);
            orderMarkers['l_'+o.id]=line;
        }
    });
}

function renderVendors(){
    Object.values(vendorMarkers).forEach(function(m){map.removeLayer(m);}); vendorMarkers={};
    if(!showVendors) return;
    VENDORS.forEach(function(v){
        if(!v.lat||!v.lng) return;
        var m=L.marker([parseFloat(v.lat),parseFloat(v.lng)],{icon:vendorIcon()});
        m.bindPopup('<strong>'+v.name+'</strong><br>'+v.category+'<br>'+v.address);
        m.addTo(map);
        vendorMarkers[v.id]=m;
    });
}

function toggleLayer(type){
    if(type==='couriers'){ showCouriers=!showCouriers; document.getElementById('btnCouriers').classList.toggle('active',showCouriers); renderCouriers(); }
    else if(type==='orders'){ showOrders=!showOrders; document.getElementById('btnOrders').classList.toggle('active',showOrders); renderOrders(); }
    else if(type==='vendors'){ showVendors=!showVendors; document.getElementById('btnVendors').classList.toggle('active',showVendors); renderVendors(); }
}

function toggleHeatmap(){
    showHeatmap=!showHeatmap;
    document.getElementById('btnHeatmap').classList.toggle('active',showHeatmap);
    if(showHeatmap) buildHeatmap(); else removeHeatmap();
}
function buildHeatmap(){
    if(heatLayer) map.removeLayer(heatLayer);
    heatLayer=L.layerGroup();
    ORDERS.forEach(function(o){
        if(!o.pickup_lat) return;
        L.circle([parseFloat(o.pickup_lat),parseFloat(o.pickup_lng)],{radius:350,color:'#f59e0b',fillColor:'#f59e0b',fillOpacity:.1,weight:0}).addTo(heatLayer);
    });
    heatLayer.addTo(map);
}
function removeHeatmap(){ if(heatLayer){map.removeLayer(heatLayer);heatLayer=null;} }

// ── Focus helpers ─────────────────────────────────────────────────────────────
function focusDriver(id){
    var m=driverMarkers[id]; if(m){map.setView(m.getLatLng(),16,{animate:true});m.openPopup();return;}
    var d=DRIVERS.find(function(x){return x.id==id;});
    if(d&&d.current_lat) map.setView([parseFloat(d.current_lat),parseFloat(d.current_lng)],16,{animate:true});
}
function focusOrder(id){
    var m=orderMarkers['p_'+id]||orderMarkers['d_'+id];
    if(m&&m.getLatLng){map.setView(m.getLatLng(),16,{animate:true});m.openPopup();}
}
function focusVendor(lat,lng,name){ map.setView([lat,lng],17,{animate:true}); var m=Object.values(vendorMarkers).find(function(v){return v.getLatLng().lat.toFixed(4)==lat.toFixed(4);}); if(m)m.openPopup(); }
function ucf(s){ return s.charAt(0).toUpperCase()+s.slice(1); }

// ── Auto-refresh ──────────────────────────────────────────────────────────────
function refresh(){
    fetch(BASE+'/api/delivery/livemap-data')
        .then(function(r){return r.json();})
        .then(function(d){
            if(d.drivers){ DRIVERS=d.drivers; renderCouriers(); }
            if(d.orders){  ORDERS=d.orders;   renderOrders(); }
            if(showHeatmap){ removeHeatmap(); buildHeatmap(); }
            // Update counters
            var online  = DRIVERS.filter(function(x){return x.is_online||x.status==='busy';}).length;
            var pending = ORDERS.filter(function(x){return x.status==='pending';}).length;
            var active  = ORDERS.filter(function(x){return ['accepted','picked_up','in_transit'].includes(x.status);}).length;
            document.getElementById('lmOnlineCount').textContent  = online;
            document.getElementById('lmPendingCount').textContent = pending;
            document.getElementById('lmActiveCount').textContent  = active;
            document.getElementById('lmLastUpdate').textContent   = 'updated ' + new Date().toLocaleTimeString();
        }).catch(function(){});
    setTimeout(refresh, 15000);
}

// Initial render
renderCouriers();
renderOrders();
renderVendors();
setTimeout(refresh, 15000);
</script>
