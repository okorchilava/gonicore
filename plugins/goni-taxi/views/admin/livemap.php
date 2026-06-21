<?php
$pageTitle = 'Live Map';
$activeNav = 'taxi-livemap';
$topbarActions = '<span style="font-size:12px;color:var(--muted)" id="lmLastUpdate">updating...</span>';

$onlineDrivers = array_filter($drivers, fn($d) => $d['is_online'] || $d['status'] === 'busy');
$pendingRides  = array_filter($rides,   fn($r) => $r['status'] === 'pending');
$activeRides   = array_filter($rides,   fn($r) => in_array($r['status'],['driver_assigned','in_progress']));
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
.lm-stat{background:var(--surface);padding:14px;text-align:center}
.lm-stat-val{font-size:22px;font-weight:900;margin-bottom:2px}
.lm-stat-lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px}
.lm-sec{padding:10px 14px 4px;font-size:10px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border)}
.lm-driver-row{display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--border2,#1a2235);cursor:pointer;transition:background .1s}
.lm-driver-row:hover{background:var(--s2,#1a2235)}
.lm-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.lm-dot.online{background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.2)}
.lm-dot.busy{background:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.2)}
.lm-dot.offline{background:#64748b}
.lm-driver-name{font-size:13px;font-weight:700;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.lm-driver-sub{font-size:11px;color:var(--muted)}
.lm-ride-row{display:flex;align-items:flex-start;gap:10px;padding:10px 14px;border-bottom:1px solid var(--border2,#1a2235);cursor:pointer;transition:background .1s}
.lm-ride-row:hover{background:var(--s2,#1a2235)}
.lm-ride-num{font-size:11px;font-family:monospace;color:var(--muted)}
.lm-ride-addr{font-size:12.5px;font-weight:600;flex:1;min-width:0}
.lm-status-pill{font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;flex-shrink:0;white-space:nowrap}
.lm-overlay{position:absolute;top:14px;right:14px;z-index:500;display:flex;flex-direction:column;gap:8px}
.lm-overlay-btn{background:rgba(17,24,39,.9);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.1);color:#f1f5f9;border-radius:10px;padding:8px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;white-space:nowrap}
.lm-overlay-btn:hover{background:rgba(79,70,229,.8)}
.lm-overlay-btn.active{background:rgba(79,70,229,.9)}
.lm-empty{padding:24px 14px;text-align:center;color:var(--muted);font-size:13px}
</style>

<div class="lm-wrap">

  <!-- Sidebar -->
  <div class="lm-sidebar">

    <!-- Stats -->
    <div class="lm-stats">
      <div class="lm-stat">
        <div class="lm-stat-val" style="color:#22c55e" id="lmOnlineCount"><?= count($onlineDrivers) ?></div>
        <div class="lm-stat-lbl">Online Drivers</div>
      </div>
      <div class="lm-stat">
        <div class="lm-stat-val" style="color:#f59e0b" id="lmPendingCount"><?= count($pendingRides) ?></div>
        <div class="lm-stat-lbl">Pending Rides</div>
      </div>
      <div class="lm-stat">
        <div class="lm-stat-val" style="color:#3b82f6" id="lmActiveCount"><?= count($activeRides) ?></div>
        <div class="lm-stat-lbl">Active Rides</div>
      </div>
      <div class="lm-stat">
        <div class="lm-stat-val" id="lmTotalCount"><?= count($drivers) ?></div>
        <div class="lm-stat-lbl">Total Drivers</div>
      </div>
    </div>

    <!-- Drivers list -->
    <div class="lm-sec">🚕 Drivers</div>
    <div id="lmDriverList">
      <?php foreach($drivers as $d):
        $status = $d['status'];
        $online = !empty($d['is_online']);
        $dotCls = $status === 'busy' ? 'busy' : ($online ? 'online' : 'offline');
        $hasGps = !empty($d['current_lat']);
      ?>
      <div class="lm-driver-row" onclick="focusDriver(<?= (int)$d['id'] ?>)"
           data-id="<?= (int)$d['id'] ?>"
           data-lat="<?= $hasGps ? (float)$d['current_lat'] : '' ?>"
           data-lng="<?= $hasGps ? (float)$d['current_lng'] : '' ?>">
        <span class="lm-dot <?= $dotCls ?>"></span>
        <div style="flex:1;min-width:0">
          <div class="lm-driver-name"><?= e($d['name']) ?></div>
          <div class="lm-driver-sub">
            <?= e($taxi->carTypes()[$d['car_type']] ?? $d['car_type']) ?>
            · <?= $d['car_number'] ? e($d['car_number']) : '—' ?>
            <?= $hasGps ? ' · 📍' : '' ?>
          </div>
        </div>
        <div style="text-align:right;flex-shrink:0">
          <div style="font-size:10px;font-weight:700;padding:2px 6px;border-radius:999px;background:<?= $status==='busy'?'rgba(245,158,11,.15)':($online?'rgba(34,197,94,.15)':'rgba(100,116,139,.1)') ?>;color:<?= $status==='busy'?'#f59e0b':($online?'#22c55e':'#64748b') ?>">
            <?= $status === 'busy' ? 'Busy' : ($online ? 'Online' : 'Offline') ?>
          </div>
          <?php if($d['avg_rating'] > 0): ?>
          <div style="font-size:10px;color:var(--muted);margin-top:3px">★<?= number_format((float)$d['avg_rating'],1)?></div>
          <?php endif ?>
        </div>
      </div>
      <?php endforeach ?>
      <?php if(empty($drivers)): ?>
      <div class="lm-empty">მძღოლები არ არის</div>
      <?php endif ?>
    </div>

    <!-- Active / Pending Rides -->
    <div class="lm-sec">🗒 Active & Pending Rides</div>
    <div id="lmRideList">
      <?php
      $shownRides = array_filter($rides, fn($r) => in_array($r['status'],['pending','driver_assigned','in_progress']));
      usort($shownRides, fn($a,$b) => strcmp($a['status'],$b['status']));
      foreach($shownRides as $ride):
        $statusColors = ['pending'=>'#f59e0b','driver_assigned'=>'#3b82f6','in_progress'=>'#10b981'];
        $sc = $statusColors[$ride['status']] ?? '#64748b';
      ?>
      <div class="lm-ride-row" onclick="focusRide(<?= (int)$ride['id'] ?>)"
           data-id="<?= (int)$ride['id'] ?>"
           data-pickup-lat="<?= !empty($ride['pickup_lat']) ? (float)$ride['pickup_lat'] : '' ?>"
           data-pickup-lng="<?= !empty($ride['pickup_lng']) ? (float)$ride['pickup_lng'] : '' ?>">
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px">
            <span class="lm-status-pill" style="background:<?= $sc ?>22;color:<?= $sc ?>"><?= $taxi->statusLabel($ride['status']) ?></span>
            <span class="lm-ride-num"><?= e($ride['ride_number']) ?></span>
          </div>
          <div class="lm-ride-addr" style="color:var(--text)">📍 <?= e(mb_substr($ride['pickup_address'],0,35)) ?></div>
          <div class="lm-ride-addr" style="color:var(--muted);font-size:12px;margin-top:2px">🏁 <?= e(mb_substr($ride['destination'],0,35)) ?></div>
        </div>
        <div style="font-size:12px;font-weight:700;color:var(--accent);flex-shrink:0"><?= number_format((float)($ride['estimated_price']??0),2).$sym ?></div>
      </div>
      <?php endforeach ?>
      <?php if(empty($shownRides)): ?>
      <div class="lm-empty">აქტიური მგზავრობები არ არის</div>
      <?php endif ?>
    </div>

  </div>

  <!-- Map -->
  <div class="lm-map">
    <div id="liveMap"></div>

    <!-- Overlay controls -->
    <div class="lm-overlay">
      <button class="lm-overlay-btn active" id="btnShowDrivers" onclick="toggleLayer('drivers')">🚕 Drivers</button>
      <button class="lm-overlay-btn active" id="btnShowRides"   onclick="toggleLayer('rides')">📍 Rides</button>
      <button class="lm-overlay-btn" id="btnHeatmap" onclick="toggleHeatmap()">🌡 Heatmap</button>
    </div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var BASE = <?= json_encode($base) ?>;
var SYM  = <?= json_encode($sym) ?>;
var DRIVERS_DATA = <?= json_encode(array_values($drivers)) ?>;
var RIDES_DATA   = <?= json_encode(array_values($rides)) ?>;

var map = L.map('liveMap', {zoomControl:true}).setView([41.6938, 44.8015], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19, attribution:'© OSM'}).addTo(map);

var drMarkers = {}, rideMarkers = {};
var showDrivers = true, showRides = true, showHeatmap = false;
var heatmapLayer = null;

function driverIcon(status) {
    var color = status === 'busy' ? '#f59e0b' : (status === 'active' ? '#22c55e' : '#64748b');
    return L.divIcon({
        html: '<div style="position:relative">'
            + '<div style="font-size:32px;line-height:1;filter:drop-shadow(0 2px 6px rgba(0,0,0,.4))">🚕</div>'
            + '<div style="position:absolute;bottom:-2px;right:-2px;width:10px;height:10px;border-radius:50%;background:'+color+';border:2px solid #fff"></div>'
            + '</div>',
        iconSize:[40,40], iconAnchor:[20,20], className:''
    });
}

function rideIcon(status) {
    var emoji = status === 'pending' ? '⏳' : (status === 'in_progress' ? '🚗' : '📌');
    return L.divIcon({html:'<div style="font-size:26px;line-height:1;filter:drop-shadow(0 2px 4px rgba(0,0,0,.3))">'+emoji+'</div>',iconSize:[32,32],iconAnchor:[16,16],className:''});
}

function renderDrivers() {
    // Clear old
    Object.values(drMarkers).forEach(function(m){ map.removeLayer(m); });
    drMarkers = {};
    if (!showDrivers) return;

    DRIVERS_DATA.forEach(function(d) {
        if (!d.current_lat || !d.current_lng) return;
        var m = L.marker([parseFloat(d.current_lat), parseFloat(d.current_lng)], {icon: driverIcon(d.status)});
        m.bindPopup(
            '<strong>'+d.name+'</strong><br>'
            + (d.car_model||'') + ' · ' + (d.car_number||'') + '<br>'
            + '<span style="color:'+(d.status==='busy'?'#f59e0b':'#22c55e')+'">'+d.status+'</span>'
            + ' · ★' + parseFloat(d.avg_rating||0).toFixed(1)
        );
        m.addTo(map);
        drMarkers[d.id] = m;
    });
}

function renderRides() {
    Object.values(rideMarkers).forEach(function(m){ map.removeLayer(m); });
    rideMarkers = {};
    if (!showRides) return;

    RIDES_DATA.forEach(function(r) {
        if (!['pending','driver_assigned','in_progress'].includes(r.status)) return;
        if (!r.pickup_lat || !r.pickup_lng) return;
        var m = L.marker([parseFloat(r.pickup_lat), parseFloat(r.pickup_lng)], {icon: rideIcon(r.status)});
        m.bindPopup(
            '<strong>'+r.ride_number+'</strong><br>'
            + '📍 '+(r.pickup_address||'?')+'<br>'
            + '🏁 '+(r.destination||'?')+'<br>'
            + '<em>'+r.status+'</em>'
        );
        m.addTo(map);
        rideMarkers[r.id] = m;
    });
}

function toggleLayer(type) {
    if (type === 'drivers') {
        showDrivers = !showDrivers;
        document.getElementById('btnShowDrivers').classList.toggle('active', showDrivers);
        renderDrivers();
    } else {
        showRides = !showRides;
        document.getElementById('btnShowRides').classList.toggle('active', showRides);
        renderRides();
    }
}

function toggleHeatmap() {
    showHeatmap = !showHeatmap;
    document.getElementById('btnHeatmap').classList.toggle('active', showHeatmap);
    if (showHeatmap) renderHeatmap(); else removeHeatmap();
}

function renderHeatmap() {
    // Simple circle-based heatmap (no external lib needed)
    // Show circles where rides happened recently
    if (heatmapLayer) map.removeLayer(heatmapLayer);
    heatmapLayer = L.layerGroup();
    RIDES_DATA.forEach(function(r) {
        if (!r.pickup_lat) return;
        var c = L.circle([parseFloat(r.pickup_lat), parseFloat(r.pickup_lng)], {
            radius: 400, color:'#ef4444', fillColor:'#ef4444',
            fillOpacity: 0.08, weight: 0
        });
        heatmapLayer.addLayer(c);
    });
    heatmapLayer.addTo(map);
}

function removeHeatmap() {
    if (heatmapLayer) { map.removeLayer(heatmapLayer); heatmapLayer = null; }
}

function focusDriver(id) {
    var m = drMarkers[id];
    if (m) { map.setView(m.getLatLng(), 16, {animate:true}); m.openPopup(); return; }
    // Find in data
    var d = DRIVERS_DATA.find(function(x){ return x.id == id; });
    if (d && d.current_lat) {
        map.setView([parseFloat(d.current_lat), parseFloat(d.current_lng)], 16, {animate:true});
    }
}

function focusRide(id) {
    var m = rideMarkers[id];
    if (m) { map.setView(m.getLatLng(), 16, {animate:true}); m.openPopup(); return; }
    var r = RIDES_DATA.find(function(x){ return x.id == id; });
    if (r && r.pickup_lat) {
        map.setView([parseFloat(r.pickup_lat), parseFloat(r.pickup_lng)], 16, {animate:true});
    }
}

renderDrivers();
renderRides();

// ── Auto-refresh every 15s ────────────────────────────────────────────────────
function refresh() {
    fetch(BASE + '/api/taxi/livemap-data')
        .then(function(r){ return r.json(); })
        .then(function(d) {
            if (d.drivers) { DRIVERS_DATA = d.drivers; renderDrivers(); }
            if (d.rides)   { RIDES_DATA   = d.rides;   renderRides(); }
            if (showHeatmap) { removeHeatmap(); renderHeatmap(); }
            // Update counters
            var online  = DRIVERS_DATA.filter(function(x){ return x.is_online || x.status==='busy'; }).length;
            var pending = RIDES_DATA.filter(function(x){ return x.status==='pending'; }).length;
            var active  = RIDES_DATA.filter(function(x){ return ['driver_assigned','in_progress'].includes(x.status); }).length;
            document.getElementById('lmOnlineCount').textContent  = online;
            document.getElementById('lmPendingCount').textContent = pending;
            document.getElementById('lmActiveCount').textContent  = active;
            document.getElementById('lmLastUpdate').textContent   = 'updated ' + new Date().toLocaleTimeString();
        })
        .catch(function(){});
    setTimeout(refresh, 15000);
}
setTimeout(refresh, 15000);
document.getElementById('lmLastUpdate').textContent = 'live · updates every 15s';
</script>
