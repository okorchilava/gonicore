<?php
$slug     = $taxi->setting('page_slug','taxi');
$brand    = $taxi->setting('brand_name','GoniTaxi');
$sym      = $taxi->setting('currency_symbol','₾');
$bogAvail = false;
try { $bogAvail = class_exists('BogPayment\BogService') && gc_container()->get(\BogPayment\BogService::class)->isEnabled(); } catch(\Throwable){}
$carTypes = $taxi->carTypes();
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
:root{
  --p:#4f46e5;--pd:#4338ca;--g:#10b981;--r:#ef4444;--a:#f59e0b;
  --bg:#f1f5f9;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;
  --shadow:0 1px 3px rgba(0,0,0,.07),0 8px 24px rgba(0,0,0,.06);
}
*{box-sizing:border-box}
.gc-main{padding:72px 0 0!important;max-width:none!important}

/* ── Layout ── */
.tx-wrap{display:grid;grid-template-columns:1fr 400px;height:calc(100vh - 72px);overflow:hidden;background:var(--bg)}

/* ── Waypoints ── */
.tx-wp-field{position:relative}
.tx-wp-input{padding-right:34px!important}
.tx-wp-remove{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:13px;padding:4px 6px;border-radius:4px;transition:color .15s;z-index:10;line-height:1}
.tx-wp-remove:hover{color:var(--r)}
.tx-add-stop{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:7px 12px;background:none;border:1.5px dashed var(--border);border-radius:8px;font-size:12px;font-weight:700;color:var(--muted);cursor:pointer;font-family:inherit;margin-bottom:8px;transition:all .15s}
.tx-add-stop:hover{border-color:var(--p);color:var(--p);background:#eef2ff}
.tx-map-col{position:relative;background:#dde8f0}
#bookingMap{width:100%;height:100%}
.tx-form-col{overflow-y:auto;background:var(--bg);display:flex;flex-direction:column}
.tx-form-col::-webkit-scrollbar{width:7px;background:var(--bg)}
.tx-form-col::-webkit-scrollbar-track{background:#e2e8f0;border-radius:4px}
.tx-form-col::-webkit-scrollbar-thumb{background:#94a3b8;border-radius:4px;min-height:40px}
.tx-form-col::-webkit-scrollbar-thumb:hover{background:#64748b}

/* ── Map overlays ── */
.tx-map-badge{position:absolute;top:14px;left:50%;transform:translateX(-50%);z-index:500;pointer-events:none;display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.95);backdrop-filter:blur(8px);border-radius:999px;padding:8px 18px;font-size:12.5px;font-weight:700;color:var(--text);box-shadow:0 2px 12px rgba(0,0,0,.12);white-space:nowrap}
.tx-map-dot{width:8px;height:8px;border-radius:50%;background:var(--g);flex-shrink:0;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.4)}60%{box-shadow:0 0 0 6px rgba(16,185,129,0)}}

/* ── Form header ── */
.tx-head{background:linear-gradient(135deg,var(--p) 0%,#7c3aed 100%);padding:22px 24px;color:#fff;flex-shrink:0}
.tx-head-row{display:flex;align-items:center;justify-content:space-between}
.tx-head-brand{display:flex;align-items:center;gap:10px;font-size:20px;font-weight:900;letter-spacing:-.3px}
.tx-head-user{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.15);border-radius:999px;padding:5px 12px 5px 5px}
.tx-head-avatar{width:26px;height:26px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-size:13px}
.tx-head-name{font-size:12.5px;font-weight:700;max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tx-head-logout{font-size:11px;opacity:.7;text-decoration:none;color:#fff;margin-left:4px}
.tx-head-logout:hover{opacity:1}

/* ── Form inner ── */
.tx-form-inner{padding:20px 22px 32px;flex:1}

/* ── Section labels ── */
.tx-section{font-size:10.5px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px;margin-top:18px}
.tx-section:first-child{margin-top:0}

/* ── Inputs ── */
.tx-field{margin-bottom:10px;position:relative}
.tx-input-wrap{position:relative}
.tx-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:15px;pointer-events:none}
.tx-input{width:100%;border:1.5px solid var(--border);border-radius:10px;padding:11px 12px 11px 38px;font-size:13.5px;font-family:inherit;background:var(--card);color:var(--text);outline:none;transition:border-color .15s,box-shadow .15s}
.tx-input:focus{border-color:var(--p);box-shadow:0 0 0 3px rgba(79,70,229,.1)}
.tx-input.no-icon{padding-left:12px}
.tx-ac{position:absolute;z-index:9999;background:var(--card);border:1.5px solid var(--border);border-radius:12px;top:calc(100% + 4px);left:0;width:100%;box-shadow:var(--shadow);max-height:200px;overflow-y:auto;display:none}
.tx-ac-item{padding:10px 14px;font-size:13px;cursor:pointer;display:flex;align-items:flex-start;gap:8px;border-bottom:1px solid #f1f5f9;transition:background .1s}
.tx-ac-item:last-child{border-bottom:none}
.tx-ac-item:hover{background:#f8fafc}
.tx-ac-item-icon{font-size:14px;margin-top:1px;flex-shrink:0}
.tx-ac-item-text{font-size:12.5px;line-height:1.4;color:var(--text)}

/* ── GPS button ── */
.tx-gps-btn{display:inline-flex;align-items:center;gap:6px;background:none;border:1.5px solid var(--border);border-radius:8px;padding:6px 12px;font-size:12px;font-weight:700;color:var(--muted);cursor:pointer;font-family:inherit;transition:all .15s;margin-bottom:8px}
.tx-gps-btn:hover{border-color:var(--p);color:var(--p);background:#eef2ff}
.tx-gps-btn.active{border-color:var(--g);color:var(--g);background:#f0fdf4}

/* ── Car type grid ── */
.tx-cars{display:grid;grid-template-columns:repeat(2,1fr);gap:7px;margin-bottom:2px}
.tx-car{border:1.5px solid var(--border);border-radius:12px;padding:10px 10px 8px;cursor:pointer;transition:all .15s;background:var(--card);text-align:center;position:relative}
.tx-car:hover{border-color:#c7d2fe;background:#fafbff}
.tx-car input{display:none}
.tx-car.selected{border-color:var(--p);background:#eef2ff;box-shadow:0 0 0 3px rgba(79,70,229,.1)}
.tx-car-icon{font-size:24px;margin-bottom:3px;line-height:1}
.tx-car-name{font-size:12px;font-weight:700;color:var(--text);margin-bottom:2px}
.tx-car-price{font-size:12px;color:var(--p);font-weight:800}

/* ── When row ── */
.tx-when{display:flex;background:#f1f5f9;border-radius:10px;padding:3px;gap:3px;margin-bottom:10px}
.tx-when-btn{flex:1;padding:8px;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s;background:transparent;color:var(--muted)}
.tx-when-btn.active{background:var(--card);color:var(--p);box-shadow:0 1px 4px rgba(0,0,0,.1)}

/* ── Two column row ── */
.tx-row2{display:grid;grid-template-columns:1fr 1fr;gap:8px}

/* ── Divider ── */
.tx-divider{height:1px;background:var(--border);margin:16px 0}

/* ── Fare display ── */
.tx-fare{background:linear-gradient(135deg,var(--p),#7c3aed);border-radius:14px;padding:14px 18px;color:#fff;display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
.tx-fare-label{font-size:11px;opacity:.8;margin-bottom:3px;font-weight:600;text-transform:uppercase;letter-spacing:.4px}
.tx-fare-val{font-size:28px;font-weight:900;line-height:1}
.tx-fare-detail{text-align:right}
.tx-fare-km{font-size:13px;opacity:.85;font-weight:700}
.tx-fare-note{font-size:11px;opacity:.6;margin-top:2px}

/* ── Payment options ── */
.tx-pay{display:flex;gap:8px;margin-bottom:4px}
.tx-pay-opt{flex:1;display:flex;align-items:center;gap:10px;padding:11px 12px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;background:var(--card);transition:all .15s}
.tx-pay-opt:hover{border-color:#c7d2fe}
.tx-pay-opt input{display:none}
.tx-pay-opt.selected{border-color:var(--p);background:#eef2ff}
.tx-pay-icon{font-size:20px;flex-shrink:0}
.tx-pay-name{font-size:13px;font-weight:700;color:var(--text)}
.tx-pay-sub{font-size:11px;color:var(--muted)}

/* ── Submit button ── */
.tx-submit{width:100%;padding:14px;background:var(--p);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:800;cursor:pointer;font-family:inherit;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:8px}
.tx-submit:hover{background:var(--pd);transform:translateY(-1px);box-shadow:0 4px 16px rgba(79,70,229,.35)}
.tx-submit:active{transform:none}
.tx-submit:disabled{background:#a5b4fc;cursor:not-allowed;transform:none;box-shadow:none}

/* ── Error ── */
.tx-err{background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:11px 14px;color:#b91c1c;font-size:13px;margin-bottom:14px;display:flex;align-items:center;gap:8px}

/* ── Routes ── */
.tx-routes{display:flex;flex-direction:column;gap:7px;margin-top:8px}
.tx-route{border:1.5px solid var(--border);border-radius:11px;padding:10px 14px;cursor:pointer;transition:all .15s;background:var(--card);display:flex;align-items:center;justify-content:space-between}
.tx-route:hover{border-color:#c7d2fe;background:#fafbff}
.tx-route.selected{border-color:var(--p);background:#eef2ff}
.tx-route-name{font-size:13px;font-weight:700;color:var(--text)}
.tx-route-sub{font-size:11.5px;color:var(--muted);margin-top:2px}
.tx-route-price{font-size:16px;font-weight:900;color:var(--p)}

/* ── Responsive ── */
@media(max-width:900px){
  .tx-wrap{grid-template-columns:1fr;height:auto;overflow:visible}
  .tx-map-col{height:42vw;min-height:220px;max-height:340px}
  #bookingMap{height:100%}
  .tx-head{padding:14px 16px}
  .tx-head-brand{font-size:17px;gap:7px}
  .tx-head-name{max-width:80px}
  .tx-form-inner{padding:14px 14px 24px}
  .tx-gps-btn{width:100%;justify-content:center;font-size:13px;padding:10px 14px}
  .tx-section{margin-top:14px}
  .tx-cars{grid-template-columns:repeat(2,1fr);gap:6px}
  .tx-car{padding:8px 6px 7px}
  .tx-car-icon{font-size:20px}
  .tx-car-name{font-size:11px}
  .tx-car-price{font-size:11px}
  .tx-row2{grid-template-columns:1fr}
  .tx-fare-val{font-size:24px}
  .tx-submit{font-size:16px;padding:15px;border-radius:14px}
  .tx-ac{max-height:160px}
  .tx-ac-item-text{font-size:12px}
  .tx-map-badge{font-size:11.5px;padding:7px 14px}
}
@keyframes gpspulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(1.6)}}
@keyframes mylocpulse{0%{transform:translateX(-50%) scale(1);opacity:.7}100%{transform:translateX(-50%) scale(2.6);opacity:0}}
</style>

<?php if($error): ?>
<div style="padding:10px 24px"><div class="tx-err">⚠️ <?= e($error) ?></div></div>
<?php endif ?>

<div class="tx-wrap">
  <!-- Map -->
  <div class="tx-map-col">
    <div class="tx-map-badge">
      <span style="width:8px;height:8px;border-radius:50%;background:var(--g);flex-shrink:0;animation:pulse 2s infinite;display:inline-block"></span>
      <span id="mapBadgeTxt">Click map or type pickup address</span>
    </div>
    <div id="bookingMap"></div>
  </div>

  <!-- Form -->
  <div class="tx-form-col">
    <div class="tx-head">
      <div class="tx-head-row">
        <div class="tx-head-brand">
          <span>🚕</span><?= e($brand) ?>
        </div>
        <?php if(!empty($customer)): ?>
        <div class="tx-head-user">
          <div class="tx-head-avatar">👤</div>
          <span class="tx-head-name"><?= e($customer['name'] ?: $customer['phone']) ?></span>
          <a href="<?= e($base).'/'.$slug ?>/auth/logout" class="tx-head-logout">↩</a>
        </div>
        <?php endif ?>
      </div>
    </div>

    <div class="tx-form-inner">
      <form method="POST" action="<?= e($base).'/'.$slug ?>/book" id="txForm">
        <input type="hidden" name="route_id"    id="routeIdField" value="">
        <input type="hidden" name="pickup_lat"  id="pickupLat" value="">
        <input type="hidden" name="pickup_lng"  id="pickupLng" value="">
        <input type="hidden" name="dest_lat"    id="destLat" value="">
        <input type="hidden" name="dest_lng"    id="destLng" value="">
        <input type="hidden" name="distance_km" id="distKm" value="">

        <!-- Route -->
        <div class="tx-section">📍 მარშრუტი</div>
        <button type="button" class="tx-gps-btn" id="locBtn" onclick="locateMe()">
          <span>📡</span> ჩემი მდებარეობა
        </button>
        <div class="tx-field">
          <div class="tx-input-wrap">
            <span class="tx-icon">📍</span>
            <input type="text" name="pickup_address" id="pickupInput" class="tx-input"
                   placeholder="აირჩიეთ მგზავრობის დაწყების ადგილი" required autocomplete="off">
            <div id="pickupAc" class="tx-ac"></div>
          </div>
        </div>
        <!-- Waypoints -->
        <div id="waypointsContainer"></div>
        <button type="button" class="tx-add-stop" onclick="addWaypoint()">＋ შუალედური გაჩერება</button>
        <div class="tx-field">
          <div class="tx-input-wrap">
            <span class="tx-icon">🏁</span>
            <input type="text" name="destination" id="destInput" class="tx-input"
                   placeholder="აირჩიეთ საბოლოო წერტილი" required autocomplete="off">
            <div id="destAc" class="tx-ac"></div>
          </div>
        </div>

        <!-- Car type -->
        <div class="tx-section">🚗 Car Type</div>
        <div class="tx-cars" id="carGrid">
          <?php foreach($carTypes as $v => $l):
            $parts = explode(' ',$l); $icon = $parts[0]; $name = implode(' ',array_slice($parts,1));
          ?>
          <label class="tx-car <?= $v==='sedan'?'selected':'' ?>" id="carLabel_<?= e($v) ?>">
            <input type="radio" name="car_type" value="<?= e($v) ?>"
                   <?= $v==='sedan'?'checked':'' ?> onchange="onCarChange(this)">
            <div class="tx-car-icon"><?= $icon ?></div>
            <div class="tx-car-name"><?= e($name) ?></div>
            <div class="tx-car-price" id="carPrice_<?= e($v) ?>">—</div>
          </label>
          <?php endforeach ?>
        </div>

        <div class="tx-divider"></div>

        <!-- When -->
        <div class="tx-section">🕐 When</div>
        <div class="tx-when">
          <button type="button" id="btnNow" class="tx-when-btn active" onclick="setWhen('now')">⚡ Now</button>
          <button type="button" id="btnSched" class="tx-when-btn" onclick="setWhen('schedule')">🗓 Schedule</button>
        </div>
        <div id="schedField" style="display:none;margin-bottom:10px">
          <input type="datetime-local" name="scheduled_at" id="scheduledAt" class="tx-input no-icon">
        </div>
        <input type="hidden" name="scheduled_at" id="scheduledNow" value="">

        <!-- Details -->
        <div class="tx-row2">
          <div class="tx-field">
            <div class="tx-section" style="margin-top:0">👤 Name</div>
            <input type="text" name="customer_name" class="tx-input no-icon"
                   value="<?= e($customer['name'] ?? '') ?>" placeholder="Your name">
          </div>
          <div class="tx-field">
            <div class="tx-section" style="margin-top:0">📱 Phone *</div>
            <input type="tel" name="customer_phone" class="tx-input no-icon"
                   value="<?= e($customer['phone'] ?? '') ?>" placeholder="+995 555 000 000" required>
          </div>
        </div>
        <div class="tx-field">
          <div class="tx-input-wrap">
            <span class="tx-icon">👥</span>
            <input type="number" name="passengers" class="tx-input" value="1" min="1" max="8" placeholder="Passengers">
          </div>
        </div>
        <div class="tx-field">
          <div class="tx-input-wrap">
            <span class="tx-icon">📝</span>
            <input type="text" name="customer_note" class="tx-input" placeholder="Note for driver (optional)">
          </div>
        </div>

        <div class="tx-divider"></div>

        <!-- Payment -->
        <div class="tx-section">💳 Payment</div>
        <div class="tx-pay">
          <label class="tx-pay-opt selected" id="payOptCash" onclick="selectPay(this)">
            <input type="radio" name="payment_method" value="cash" checked>
            <span class="tx-pay-icon">💵</span>
            <div><div class="tx-pay-name">Cash</div><div class="tx-pay-sub">After ride</div></div>
          </label>
          <?php if($bogAvail): ?>
          <label class="tx-pay-opt" id="payOptBog" onclick="selectPay(this)">
            <input type="radio" name="payment_method" value="bog">
            <span class="tx-pay-icon">💳</span>
            <div><div class="tx-pay-name">Card</div><div class="tx-pay-sub">Pay now</div></div>
          </label>
          <?php endif ?>
        </div>

        <!-- Fare -->
        <div class="tx-fare" id="fareBox" style="margin-top:14px">
          <div>
            <div class="tx-fare-label">Estimated Fare</div>
            <div class="tx-fare-val" id="fareVal">—</div>
          </div>
          <div class="tx-fare-detail">
            <div class="tx-fare-km" id="fareKm"></div>
            <div class="tx-fare-note">incl. all fees</div>
          </div>
        </div>

        <!-- Fixed routes -->
        <?php if(!empty($routes)): ?>
        <details style="margin-bottom:14px">
          <summary style="cursor:pointer;font-size:12.5px;font-weight:800;color:var(--p);padding:4px 0;list-style:none;display:flex;align-items:center;gap:6px">
            <span>🗺</span> Fixed Routes <span style="opacity:.5;font-weight:400">(<?= count($routes) ?>)</span>
          </summary>
          <div class="tx-routes">
            <?php foreach($routes as $rt): ?>
            <div class="tx-route" id="route_<?= (int)$rt['id'] ?>"
                 onclick="selectRoute(<?= (int)$rt['id'] ?>,<?= json_encode($rt['from_location']) ?>,<?= json_encode($rt['to_location']) ?>,<?= (float)$rt['price'] ?>,<?= (float)$rt['distance_km'] ?>)">
              <div>
                <div class="tx-route-name"><?= e($rt['name']) ?></div>
                <div class="tx-route-sub"><?= e($rt['from_location']) ?> → <?= e($rt['to_location']) ?> · <?= number_format((float)$rt['distance_km'],1) ?> km</div>
              </div>
              <div class="tx-route-price"><?= $taxi->formatPrice((float)$rt['price']) ?></div>
            </div>
            <?php endforeach ?>
          </div>
        </details>
        <?php endif ?>

        <button type="submit" class="tx-submit" id="bookBtn">
          <span>🚕</span> <span id="bookBtnTxt">Book Ride</span>
        </button>
      </form>
    </div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var BASE_FARE=<?= (float)$taxi->setting('base_fare','5') ?>,
    PER_KM  =<?= (float)$taxi->setting('price_per_km','1.5') ?>,
    MIN_FARE=<?= (float)$taxi->setting('min_fare','5') ?>,
    SYM     ='<?= e($sym) ?>';
var MULTS={economy:.8,sedan:1.0,suv:1.3,minivan:1.4};

// ── Map ──────────────────────────────────────────────────────────────────────
var map=L.map('bookingMap',{zoomControl:true}).setView([41.6938,44.8015],18);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19}).addTo(map);

var puMk=null,dsMk=null,routeLine=null;
var waypoints=[],wpCount=0;
var puIcon=L.divIcon({html:'<div style="background:#4f46e5;width:20px;height:20px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 8px rgba(79,70,229,.6)"></div>',iconSize:[26,26],iconAnchor:[13,13],className:''});
var dsIcon=L.divIcon({html:'<div style="background:#ef4444;width:20px;height:20px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 8px rgba(239,68,68,.5)"></div>',iconSize:[26,26],iconAnchor:[13,13],className:''});
var taxiIcon=L.divIcon({html:'<div style="font-size:44px;line-height:1;filter:drop-shadow(0 3px 7px rgba(0,0,0,.5))">🚕</div>',iconSize:[52,52],iconAnchor:[26,26],className:''});
var myLocIcon=L.divIcon({html:'<div style="position:relative;width:56px;height:64px;display:flex;align-items:flex-end;justify-content:center"><div style="position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:50px;height:50px;border-radius:50%;background:rgba(79,70,229,.25);animation:mylocpulse 1.6s ease-out infinite"></div><div style="font-size:46px;line-height:1;filter:drop-shadow(0 3px 6px rgba(0,0,0,.35));position:relative;z-index:1">🧍</div></div>',iconSize:[56,64],iconAnchor:[28,64],className:''});
var myLocMk=null;
var wpIcon=L.divIcon({html:'<div style="background:#f59e0b;width:16px;height:16px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 6px rgba(245,158,11,.5)"></div>',iconSize:[22,22],iconAnchor:[11,11],className:''});

map.on('click',function(e){
    if(!document.getElementById('pickupLat').value) setPickup(e.latlng.lat,e.latlng.lng,null);
    else setDest(e.latlng.lat,e.latlng.lng,null);
});

function drawRoute(){
    if(!puMk||!dsMk){if(routeLine){map.removeLayer(routeLine);routeLine=null;}return;}
    var pts=[puMk.getLatLng()];
    waypoints.forEach(function(wp){if(wp&&wp.lat&&wp.lng)pts.push({lat:wp.lat,lng:wp.lng});});
    pts.push(dsMk.getLatLng());
    var coordStr=pts.map(function(p){return p.lng+','+p.lat;}).join(';');
    fetch('https://router.project-osrm.org/route/v1/driving/'+coordStr+'?overview=full&geometries=geojson')
        .then(function(r){return r.json();}).then(function(d){
            if(routeLine)map.removeLayer(routeLine);
            if(d.routes&&d.routes[0]){
                routeLine=L.geoJSON(d.routes[0].geometry,{style:{color:'#4f46e5',weight:4,opacity:.7}}).addTo(map);
                map.fitBounds(routeLine.getBounds(),{padding:[50,50]});
                var km=d.routes[0].distance/1000;
                document.getElementById('distKm').value=Math.round(km*10)/10;
                recalcFare(km);
            }
        }).catch(function(){
            if(routeLine)map.removeLayer(routeLine);
            var lls=[puMk.getLatLng()];
            waypoints.forEach(function(wp){if(wp&&wp.lat&&wp.lng)lls.push([wp.lat,wp.lng]);});
            lls.push(dsMk.getLatLng());
            routeLine=L.polyline(lls,{color:'#4f46e5',weight:3,dashArray:'6,4'}).addTo(map);
            map.fitBounds(L.latLngBounds(lls),{padding:[50,50]});
        });
}

function setPickup(lat,lng,label){
    if(puMk)map.removeLayer(puMk);
    puMk=L.marker([lat,lng],{icon:puIcon,draggable:true}).addTo(map).bindTooltip('Pickup');
    puMk.on('dragend',function(e){var p=e.target.getLatLng();document.getElementById('pickupLat').value=p.lat;document.getElementById('pickupLng').value=p.lng;reverseGeocode(p.lat,p.lng,function(a){document.getElementById('pickupInput').value=a;});drawRoute();});
    document.getElementById('pickupLat').value=lat;
    document.getElementById('pickupLng').value=lng;
    if(label) document.getElementById('pickupInput').value=label;
    else reverseGeocode(lat,lng,function(a){document.getElementById('pickupInput').value=a;});
    document.getElementById('mapBadgeTxt').textContent='Now click map to set destination';
    if(dsMk)drawRoute(); else map.setView([lat,lng],18);
}
function setDest(lat,lng,label){
    if(dsMk)map.removeLayer(dsMk);
    dsMk=L.marker([lat,lng],{icon:dsIcon,draggable:true}).addTo(map).bindTooltip('Destination');
    dsMk.on('dragend',function(e){var p=e.target.getLatLng();document.getElementById('destLat').value=p.lat;document.getElementById('destLng').value=p.lng;reverseGeocode(p.lat,p.lng,function(a){document.getElementById('destInput').value=a;});drawRoute();});
    document.getElementById('destLat').value=lat;
    document.getElementById('destLng').value=lng;
    if(label) document.getElementById('destInput').value=label;
    else reverseGeocode(lat,lng,function(a){document.getElementById('destInput').value=a;});
    document.getElementById('mapBadgeTxt').textContent='Route set — ready to book';
    drawRoute();
}

// ── GPS ──────────────────────────────────────────────────────────────────────
function locateMe(){
    var btn=document.getElementById('locBtn');
    btn.disabled=true;btn.innerHTML='<span>⌛</span> Locating…';
    navigator.geolocation.getCurrentPosition(function(pos){
        map.setView([pos.coords.latitude,pos.coords.longitude],18);
        setPickup(pos.coords.latitude,pos.coords.longitude,null);
        btn.disabled=false;btn.innerHTML='<span>✅</span> Location set';
        btn.classList.add('active');
    },function(){btn.disabled=false;btn.innerHTML='<span>📡</span> Use my location';},{enableHighAccuracy:true,timeout:10000});
}
if(navigator.geolocation){
    navigator.geolocation.getCurrentPosition(function(pos){
        var lat=pos.coords.latitude,lng=pos.coords.longitude;
        map.setView([lat,lng],18);
        if(myLocMk)map.removeLayer(myLocMk);
        myLocMk=L.marker([lat,lng],{icon:myLocIcon,interactive:false,zIndexOffset:500}).addTo(map);
    },function(){},{enableHighAccuracy:true,timeout:8000});
    navigator.geolocation.watchPosition(function(pos){
        var lat=pos.coords.latitude,lng=pos.coords.longitude;
        if(myLocMk)myLocMk.setLatLng([lat,lng]);
        else{myLocMk=L.marker([lat,lng],{icon:myLocIcon,interactive:false,zIndexOffset:500}).addTo(map);}
        if(!document.getElementById('pickupLat').value)map.panTo([lat,lng],{animate:true,duration:0.5});
    },function(){},{enableHighAccuracy:true,maximumAge:10000});
}

// ── Reverse geocode ───────────────────────────────────────────────────────────
function reverseGeocode(lat,lng,cb){
    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat='+lat+'&lon='+lng,{headers:{'Accept-Language':'ka,en'}})
        .then(function(r){return r.json();}).then(function(d){cb(d.display_name||lat+','+lng);}).catch(function(){cb(lat.toFixed(5)+','+lng.toFixed(5));});
}

// ── Autocomplete ──────────────────────────────────────────────────────────────
var _acT={};
function setupAc(inputId,acId,onSelect){
    var inp=document.getElementById(inputId),list=document.getElementById(acId);
    inp.addEventListener('input',function(){
        clearTimeout(_acT[inputId]);
        if(inp.value.length<3){list.style.display='none';return;}
        _acT[inputId]=setTimeout(function(){
            fetch('https://nominatim.openstreetmap.org/search?format=json&q='+encodeURIComponent(inp.value)+'&countrycodes=ge&limit=5',{headers:{'Accept-Language':'ka,en'}})
                .then(function(r){return r.json();}).then(function(res){
                    list.innerHTML='';
                    if(!res.length){list.style.display='none';return;}
                    res.forEach(function(r){
                        var el=document.createElement('div');el.className='tx-ac-item';
                        el.innerHTML='<span class="tx-ac-item-icon">📍</span><span class="tx-ac-item-text">'+r.display_name+'</span>';
                        el.addEventListener('mousedown',function(e){e.preventDefault();inp.value=r.display_name;list.style.display='none';onSelect(parseFloat(r.lat),parseFloat(r.lon),r.display_name);});
                        list.appendChild(el);
                    });
                    list.style.display='block';
                }).catch(function(){list.style.display='none';});
        },350);
    });
    inp.addEventListener('blur',function(){setTimeout(function(){list.style.display='none';},200);});
}
setupAc('pickupInput','pickupAc',function(lat,lng,lbl){setPickup(lat,lng,lbl);});
setupAc('destInput','destAc',function(lat,lng,lbl){setDest(lat,lng,lbl);});

// ── Fare calc ─────────────────────────────────────────────────────────────────
function recalcFare(km){
    if(typeof km!=='number'||km<=0){
        document.getElementById('fareVal').textContent='—';
        document.getElementById('fareKm').textContent='';
        Object.keys(MULTS).forEach(function(c){var el=document.getElementById('carPrice_'+c);if(el)el.textContent='—';});
        return;
    }
    var car=document.querySelector('input[name="car_type"]:checked')?.value||'sedan';
    Object.keys(MULTS).forEach(function(c){
        var el=document.getElementById('carPrice_'+c);if(!el)return;
        var f=Math.max(MIN_FARE,Math.round((BASE_FARE+km*PER_KM)*(MULTS[c]||1)*100)/100);
        el.textContent=f.toFixed(2)+' '+SYM;
    });
    var mult=MULTS[car]||1;
    var fare=Math.max(MIN_FARE,Math.round((BASE_FARE+km*PER_KM)*mult*100)/100);
    document.getElementById('fareVal').textContent=fare.toFixed(2)+' '+SYM;
    document.getElementById('fareKm').textContent=km.toFixed(1)+' km';
}

// ── Car selection ─────────────────────────────────────────────────────────────
function onCarChange(inp){
    document.querySelectorAll('.tx-car').forEach(function(el){el.classList.remove('selected');});
    inp.closest('.tx-car').classList.add('selected');
    recalcFare(parseFloat(document.getElementById('distKm').value)||0);
}

// ── Online drivers on map ─────────────────────────────────────────────────────
var onlineMk=[];
function loadOnlineDrivers(){
    fetch(<?= json_encode($base.'/api/taxi/online-drivers') ?>)
        .then(function(r){return r.json();})
        .then(function(d){
            onlineMk.forEach(function(m){map.removeLayer(m);});onlineMk=[];
            (d.drivers||[]).forEach(function(dr){onlineMk.push(L.marker([dr.lat,dr.lng],{icon:taxiIcon,interactive:false}).addTo(map));});
        }).catch(function(){});
}
loadOnlineDrivers();setInterval(loadOnlineDrivers,20000);

// ── Fixed routes ──────────────────────────────────────────────────────────────
function selectRoute(id,from,to,price,km){
    document.getElementById('routeIdField').value=id;
    document.getElementById('pickupInput').value=from;
    document.getElementById('destInput').value=to;
    document.getElementById('distKm').value=km;
    document.getElementById('fareVal').textContent=price.toFixed(2)+' '+SYM;
    document.getElementById('fareKm').textContent=km+' km';
    document.querySelectorAll('.tx-route').forEach(function(el){el.classList.remove('selected');});
    var sel=document.getElementById('route_'+id);if(sel)sel.classList.add('selected');
}

// ── Payment ───────────────────────────────────────────────────────────────────
function selectPay(lbl){
    document.querySelectorAll('.tx-pay-opt').forEach(function(el){el.classList.remove('selected');});
    lbl.classList.add('selected');
    lbl.querySelector('input').checked=true;
}

// ── When toggle ───────────────────────────────────────────────────────────────
function setWhen(mode){
    var scheduledNow=document.getElementById('scheduledNow');
    var scheduledAt =document.getElementById('scheduledAt');
    if(mode==='now'){
        document.getElementById('btnNow').classList.add('active');
        document.getElementById('btnSched').classList.remove('active');
        document.getElementById('schedField').style.display='none';
        scheduledAt.removeAttribute('name');
        scheduledNow.name='scheduled_at';scheduledNow.value='';
    } else {
        document.getElementById('btnSched').classList.add('active');
        document.getElementById('btnNow').classList.remove('active');
        document.getElementById('schedField').style.display='block';
        scheduledAt.name='scheduled_at';
        scheduledNow.removeAttribute('name');scheduledNow.value='';
    }
}
setWhen('now');

// ── Waypoints ─────────────────────────────────────────────────────────────────
function addWaypoint(){
    var idx=wpCount++;
    var arrIdx=waypoints.length;
    waypoints.push({lat:null,lng:null,address:'',marker:null,uid:idx});
    var container=document.getElementById('waypointsContainer');
    var div=document.createElement('div');
    div.className='tx-field tx-wp-field';
    div.id='wpField_'+idx;
    var num=arrIdx+1;
    div.innerHTML='<div class="tx-input-wrap">'
        +'<span class="tx-icon" style="color:#f59e0b">●</span>'
        +'<input type="text" class="tx-input tx-wp-input" id="wpInput_'+idx+'" placeholder="გაჩერება '+num+'" autocomplete="off">'
        +'<div id="wpAc_'+idx+'" class="tx-ac"></div>'
        +'<button type="button" class="tx-wp-remove" onclick="removeWaypoint('+arrIdx+','+idx+')" title="ამოიღე">✕</button>'
        +'</div>'
        +'<input type="hidden" name="waypoint_addr[]" id="wpAddr_'+idx+'">'
        +'<input type="hidden" name="waypoint_lat[]"  id="wpLat_'+idx+'">'
        +'<input type="hidden" name="waypoint_lng[]"  id="wpLng_'+idx+'">';
    container.appendChild(div);
    setupWaypointAc(arrIdx,idx);
    document.getElementById('wpInput_'+idx).focus();
}
function removeWaypoint(arrIdx,uid){
    if(waypoints[arrIdx]&&waypoints[arrIdx].marker)map.removeLayer(waypoints[arrIdx].marker);
    waypoints[arrIdx]=null;
    var el=document.getElementById('wpField_'+uid);if(el)el.remove();
    drawRoute();
}
function setWaypoint(arrIdx,uid,lat,lng,label){
    if(waypoints[arrIdx]&&waypoints[arrIdx].marker)map.removeLayer(waypoints[arrIdx].marker);
    var mk=L.marker([lat,lng],{icon:wpIcon,draggable:true}).addTo(map).bindTooltip('გაჩერება '+(arrIdx+1));
    mk.on('dragend',function(e){
        var p=e.target.getLatLng();
        waypoints[arrIdx].lat=p.lat;waypoints[arrIdx].lng=p.lng;
        document.getElementById('wpLat_'+uid).value=p.lat;
        document.getElementById('wpLng_'+uid).value=p.lng;
        reverseGeocode(p.lat,p.lng,function(a){document.getElementById('wpInput_'+uid).value=a;waypoints[arrIdx].address=a;});
        drawRoute();
    });
    waypoints[arrIdx]={lat:lat,lng:lng,address:label||'',marker:mk,uid:uid};
    document.getElementById('wpAddr_'+uid).value=label||'';
    document.getElementById('wpLat_'+uid).value=lat;
    document.getElementById('wpLng_'+uid).value=lng;
    drawRoute();
}
function setupWaypointAc(arrIdx,uid){
    var inp=document.getElementById('wpInput_'+uid);
    var list=document.getElementById('wpAc_'+uid);
    var t;
    inp.addEventListener('input',function(){
        clearTimeout(t);
        if(inp.value.length<3){list.style.display='none';return;}
        t=setTimeout(function(){
            fetch('https://nominatim.openstreetmap.org/search?format=json&q='+encodeURIComponent(inp.value)+'&countrycodes=ge&limit=5',{headers:{'Accept-Language':'ka,en'}})
                .then(function(r){return r.json();}).then(function(res){
                    list.innerHTML='';
                    if(!res.length){list.style.display='none';return;}
                    res.forEach(function(r){
                        var el=document.createElement('div');el.className='tx-ac-item';
                        el.innerHTML='<span class="tx-ac-item-icon">📍</span><span class="tx-ac-item-text">'+r.display_name+'</span>';
                        el.addEventListener('mousedown',function(e){
                            e.preventDefault();inp.value=r.display_name;list.style.display='none';
                            setWaypoint(arrIdx,uid,parseFloat(r.lat),parseFloat(r.lon),r.display_name);
                        });
                        list.appendChild(el);
                    });
                    list.style.display='block';
                }).catch(function(){list.style.display='none';});
        },350);
    });
    inp.addEventListener('blur',function(){setTimeout(function(){list.style.display='none';},200);});
}

// ── Submit ────────────────────────────────────────────────────────────────────
document.getElementById('txForm').addEventListener('submit',function(){
    document.getElementById('bookBtn').disabled=true;
    document.getElementById('bookBtnTxt').textContent='Booking…';
});
</script>
