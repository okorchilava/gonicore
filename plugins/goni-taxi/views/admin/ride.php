<?php
$pageTitle='Ride '.$ride['ride_number']; $activeNav='taxi-rides'; $topbarActions='';
$sc=$taxi->statusColor($ride['status']);
?>
<?php if(!empty($flash)): ?><div id="gc-flash" data-msg="<?= e($flash)?>" data-icon="success" style="display:none"></div><?php endif ?>

<?php
$hasCoords = !empty($ride['pickup_lat']) && !empty($ride['pickup_lng'])
          && !empty($ride['dest_lat'])   && !empty($ride['dest_lng']);
$driver    = $ride['driver'] ?? null;
$rideNum   = $ride['ride_number'];
$sym       = $taxi->setting('currency_symbol','₾');
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
#rideMap{height:440px;border-radius:0 0 8px 8px}
#rideMap:fullscreen{height:100vh!important;border-radius:0}
.ride-map-pill{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:700;padding:3px 10px;border-radius:999px}
.adm-driver-tip{background:#fff!important;border:1px solid #e2e8f0!important;border-radius:10px!important;padding:10px 14px!important;box-shadow:0 4px 20px rgba(0,0,0,.12)!important;font-family:inherit!important}
.adm-driver-tip::before{display:none!important}
</style>

<div class="card" style="margin-bottom:20px">
    <div class="card-header" style="justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <h3>🗺 მგზავრობის რუკა</h3>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <span class="ride-map-pill" style="background:#eef2ff;color:#4f46e5">📍 <?= e(mb_substr($ride['pickup_address'],0,28)) ?>…</span>
            <span style="color:var(--muted);font-size:12px">→</span>
            <span class="ride-map-pill" style="background:#fef2f2;color:#dc2626">🏁 <?= e(mb_substr($ride['destination'],0,28)) ?>…</span>
            <?php if($ride['distance_km']): ?>
            <span class="ride-map-pill" style="background:#f0fdf4;color:#16a34a">📏 <?= number_format((float)$ride['distance_km'],1) ?> km</span>
            <?php endif ?>
            <span id="rideMapUpdated" style="font-size:11px;color:var(--muted)"></span>
        </div>
    </div>
    <div id="rideMap"></div>
</div>

<script>
(function(){
var rmap=L.map('rideMap',{zoomControl:true,attributionControl:false}).setView([41.6938,44.8015],15);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19}).addTo(rmap);

// ── Fullscreen ────────────────────────────────────────────────────────────────
var FsCtrl=L.Control.extend({options:{position:'topleft'},onAdd:function(){
    var b=L.DomUtil.create('button');
    b.style.cssText='width:30px;height:30px;background:#fff;border:none;cursor:pointer;font-size:15px;display:flex;align-items:center;justify-content:center;border-radius:4px;box-shadow:0 1px 5px rgba(0,0,0,.2)';
    b.innerHTML='⛶';b.title='სრული ეკრანი';
    L.DomEvent.disableClickPropagation(b);
    L.DomEvent.on(b,'click',function(){
        var el=document.getElementById('rideMap');
        if(!document.fullscreenElement){el.requestFullscreen();b.innerHTML='✕';}
        else{document.exitFullscreen();b.innerHTML='⛶';}
    });
    document.addEventListener('fullscreenchange',function(){
        if(!document.fullscreenElement)b.innerHTML='⛶';
        setTimeout(function(){rmap.invalidateSize();},200);
    });
    return b;
}});
rmap.addControl(new FsCtrl());

// ── Icons ─────────────────────────────────────────────────────────────────────
var puIcon=L.divIcon({html:'<div style="font-size:26px;filter:drop-shadow(0 2px 4px rgba(79,70,229,.5))">📍</div>',iconSize:[30,30],iconAnchor:[15,30],className:''});
var dsIcon=L.divIcon({html:'<div style="font-size:26px;filter:drop-shadow(0 2px 4px rgba(239,68,68,.5))">🏁</div>',iconSize:[30,30],iconAnchor:[15,30],className:''});
var drIcon=L.divIcon({html:'<div style="font-size:38px;line-height:1;filter:drop-shadow(0 3px 6px rgba(0,0,0,.45))">🚕</div>',iconSize:[44,44],iconAnchor:[22,22],className:''});
var wpIcon=L.divIcon({html:'<div style="background:#f59e0b;width:14px;height:14px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 5px rgba(245,158,11,.5)"></div>',iconSize:[20,20],iconAnchor:[10,10],className:''});

// ── Static markers ────────────────────────────────────────────────────────────
var pc=<?= ($hasCoords) ? json_encode([(float)$ride['pickup_lat'],(float)$ride['pickup_lng']]) : 'null' ?>;
var dc=<?= ($hasCoords) ? json_encode([(float)$ride['dest_lat'],(float)$ride['dest_lng']]) : 'null' ?>;
var rl=null,drMk=null;

function geocode(addr,cb){fetch('https://nominatim.openstreetmap.org/search?format=json&q='+encodeURIComponent(addr)+'&limit=1',{headers:{'Accept-Language':'ka,en'}}).then(function(r){return r.json();}).then(function(d){if(d.length)cb([+d[0].lat,+d[0].lon]);}).catch(function(){});}

function drawStaticRoute(){
    if(!pc||!dc)return;
    L.marker(pc,{icon:puIcon}).addTo(rmap).bindTooltip('<?= e(addslashes($ride['pickup_address'])) ?>',{direction:'top'});
    L.marker(dc,{icon:dsIcon}).addTo(rmap).bindTooltip('<?= e(addslashes($ride['destination'])) ?>',{direction:'top'});
    fetch('https://router.project-osrm.org/route/v1/driving/'+pc[1]+','+pc[0]+';'+dc[1]+','+dc[0]+'?overview=full&geometries=geojson')
        .then(function(r){return r.json();}).then(function(d){
            if(d.routes&&d.routes[0]){
                rl=L.geoJSON(d.routes[0].geometry,{style:{color:'#4f46e5',weight:4,opacity:.75,dashArray:''}}).addTo(rmap);
                rmap.fitBounds(rl.getBounds(),{padding:[60,80]});
            }
        }).catch(function(){
            var line=L.polyline([pc,dc],{color:'#4f46e5',weight:3,dashArray:'8,5'}).addTo(rmap);
            rmap.fitBounds(line.getBounds(),{padding:[60,80]});
        });
}

if(pc&&dc){drawStaticRoute();}
else{
    <?php if(!$hasCoords): ?>
    geocode(<?= json_encode($ride['pickup_address']) ?>,function(p){pc=p;
        geocode(<?= json_encode($ride['destination']) ?>,function(d){dc=d;drawStaticRoute();});});
    <?php endif ?>
}

// ── Driver tooltip ────────────────────────────────────────────────────────────
function driverTip(loc){
    var sc=loc.speed!=null&&loc.speed<2?'#64748b':'#3b82f6';
    var spIcon=loc.speed!=null&&loc.speed<2?'🅿':'⚡';
    var carLine='';
    if(loc.car||loc.car_color||loc.car_number){
        carLine='<div style="font-size:11.5px;color:#334155;margin:3px 0;display:flex;align-items:center;gap:6px">';
        if(loc.car_color)carLine+='<span style="width:11px;height:11px;border-radius:50%;background:'+loc.car_color+';border:1.5px solid #e2e8f0;flex-shrink:0;display:inline-block"></span>';
        carLine+='<span>'+(loc.car||'')+(loc.car_number?' · <b>'+loc.car_number+'</b>':'')+'</span></div>';
    }
    var spLine=loc.speed!=null?'<div style="font-size:11.5px;font-weight:700;color:'+sc+';margin:3px 0">'+spIcon+' '+loc.speed+' კმ/სთ</div>':'';
    var freshDot=loc.fresh?'<span style="color:#10b981">● ლაივ</span>':'<span style="color:#f59e0b">● ბოლო ცნობილი</span>';
    return '<div style="min-width:170px;font-family:inherit">'
        +'<div style="font-size:13.5px;font-weight:800;margin-bottom:3px">🧑‍✈️ '+loc.name+'</div>'
        +carLine+spLine
        +'<div style="font-size:10.5px;margin-top:4px">'+freshDot+'</div>'
        +'</div>';
}

// ── Live driver location ──────────────────────────────────────────────────────
<?php if($driver): ?>
function fetchDriver(){
    fetch(<?= json_encode($base.'/api/taxi/driver-location/'.$rideNum) ?>)
        .then(function(r){return r.json();}).then(function(d){
            var loc=d.location;
            if(loc&&loc.lat){
                if(drMk){drMk.setLatLng([loc.lat,loc.lng]);drMk.setTooltipContent(driverTip(loc));}
                else{drMk=L.marker([loc.lat,loc.lng],{icon:drIcon,zIndexOffset:1000}).addTo(rmap).bindTooltip(driverTip(loc),{direction:'top',offset:[0,-18],opacity:1,permanent:false,className:'adm-driver-tip'});}
            }
            var t=new Date();
            var el=document.getElementById('rideMapUpdated');
            if(el)el.textContent='განახლდა '+t.getHours()+':'+String(t.getMinutes()).padStart(2,'0')+':'+String(t.getSeconds()).padStart(2,'0');
        }).catch(function(){});
}
fetchDriver();
setInterval(fetchDriver,10000);
<?php endif ?>

})();
</script>

<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;max-width:1000px">
<div style="display:flex;flex-direction:column;gap:16px">
    <div class="card">
        <div class="card-header" style="justify-content:space-between">
            <div>
                <span style="font-family:monospace;font-size:18px;font-weight:900"><?= e($ride['ride_number'])?></span>
                <span style="background:<?=$sc?>22;color:<?=$sc?>;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:8px"><?=$taxi->statusLabel($ride['status'])?></span>
            </div>
            <div style="font-size:12px;color:var(--muted)"><?= date('d M Y, H:i',strtotime($ride['created_at']))?></div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13.5px">
            <div><span style="color:var(--muted)">Customer</span><br><strong><?= e($ride['customer_name']?:'—')?></strong><br><a href="tel:<?= e($ride['customer_phone'])?>"><?= e($ride['customer_phone'])?></a></div>
            <div><span style="color:var(--muted)">Car Type</span><br><strong><?= e($taxi->carTypes()[$ride['car_type']]??$ride['car_type'])?></strong> · <?=(int)$ride['passengers']?> pax</div>
            <div><span style="color:var(--muted)">Pickup</span><br><strong><?= e($ride['pickup_address'])?></strong></div>
            <div><span style="color:var(--muted)">Destination</span><br><strong><?= e($ride['destination'])?></strong></div>
            <?php if($ride['scheduled_at']): ?>
            <div><span style="color:var(--muted)">Scheduled</span><br><?= e(date('d M Y, H:i',strtotime($ride['scheduled_at'])))?></div>
            <?php endif ?>
            <?php if($ride['distance_km']): ?>
            <div><span style="color:var(--muted)">Distance</span><br><?= number_format((float)$ride['distance_km'],1)?> km</div>
            <?php endif ?>
            <div><span style="color:var(--muted)">Est. Price</span><br><strong style="font-size:18px;color:#10b27c"><?= $taxi->formatPrice((float)$ride['estimated_price'])?></strong></div>
            <?php if($ride['actual_price']): ?>
            <div style="grid-column:span 2">
                <span style="color:var(--muted)">Actual Price</span><br>
                <div style="display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;margin-top:4px">
                    <strong style="font-size:22px;color:#4f46e5"><?= $taxi->formatPrice((float)$ride['actual_price'])?></strong>
                    <?php
                    $waitFeeStored = isset($ride['waiting_fee']) && $ride['waiting_fee'] !== null ? (float)$ride['waiting_fee'] : 0.0;
                    $baseFareStored = round((float)$ride['actual_price'] - $waitFeeStored, 2);
                    if($waitFeeStored > 0):
                    ?>
                    <span style="font-size:12px;color:var(--muted)">
                        = <?= $taxi->formatPrice($baseFareStored) ?> მგზავრობა
                        + <span style="color:#f59e0b;font-weight:700"><?= $taxi->formatPrice($waitFeeStored) ?> მოლოდინი</span>
                    </span>
                    <?php endif ?>
                </div>
            </div>
            <?php endif ?>
            <?php if($ride['status']==='completed' && isset($ride['driver_earnings']) && $ride['driver_earnings']!==null):
                $fare     = (float)($ride['actual_price'] ?? $ride['estimated_price']);
                $dEarn    = (float)$ride['driver_earnings'];
                $platCut  = round($fare - $dEarn, 2);
                $pct      = $fare > 0 ? round($platCut/$fare*100,1) : 0;
            ?>
            <div style="grid-column:span 2;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:12px 14px;display:flex;gap:20px;font-size:13px">
                <div><span style="color:#64748b">Platform (<?= $pct?>%)</span><br><strong style="color:#6d28d9;font-size:16px"><?= $taxi->formatPrice($platCut)?></strong></div>
                <div><span style="color:#64748b">Driver earnings</span><br><strong style="color:#10b981;font-size:16px"><?= $taxi->formatPrice($dEarn)?></strong></div>
            </div>
            <?php endif ?>
            <div><span style="color:var(--muted)">Payment</span><br><?= e(ucfirst($ride['payment_method']))?> · <span style="color:<?=$ride['payment_status']==='paid'?'#10b981':'#f59e0b'?>;font-weight:700"><?=$ride['payment_status']==='paid'?'✓ Paid':'⏳ Unpaid'?></span></div>
            <?php if($ride['customer_note']): ?><div style="grid-column:span 2"><span style="color:var(--muted)">Note</span><br><?= e($ride['customer_note'])?></div><?php endif ?>
            <?php
            $waitSecs  = (int)($ride['waiting_seconds'] ?? 0);
            $waitingNow = !empty($ride['waiting_started_at']);
            $waitLive   = $waitingNow ? max(0, time() - strtotime((string)$ride['waiting_started_at'])) : 0;
            $totalSecs  = $waitSecs + $waitLive;
            $waitFreeS  = (float)$taxi->setting('waiting_free_minutes','3') * 60;
            $waitRateS  = (float)$taxi->setting('waiting_rate_per_min','0.3');
            $billableMins = max(0, ($totalSecs - $waitFreeS) / 60);
            $waitFee    = round($billableMins * $waitRateS, 2);
            if($totalSecs > 0 || $waitingNow):
                $wMin = floor($totalSecs/60); $wSec = $totalSecs%60;
            ?>
            <div style="grid-column:span 2;background:<?= $waitingNow?'#fef3c7':'#f8fafc' ?>;border:1px solid <?= $waitingNow?'#fde68a':'#e2e8f0' ?>;border-radius:8px;padding:12px 14px">
                <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">
                    ⏱ მოლოდინი<?= $waitingNow?' <span style="color:#d97706;animation:none">● ახლა</span>':'' ?>
                </div>
                <div style="display:flex;gap:20px;flex-wrap:wrap;font-size:13.5px">
                    <div><span style="color:var(--muted)">დრო</span><br>
                        <strong id="adminWaitTime"><?= sprintf('%02d:%02d', $wMin, $wSec) ?></strong>
                        <?= $waitingNow?'<span style="font-size:11px;color:#d97706"> (მიმდინარე)</span>':'' ?>
                    </div>
                    <?php if($waitFee > 0): ?>
                    <div><span style="color:var(--muted)">მოლოდინის თანხა</span><br>
                        <strong style="color:#4f46e5;font-size:16px" id="adminWaitFee">+<?= $taxi->formatPrice($waitFee) ?></strong>
                    </div>
                    <?php endif ?>
                    <div><span style="color:var(--muted)">ტარიფი</span><br>
                        <span style="font-size:12px"><?= (int)$taxi->setting('waiting_free_minutes','3') ?>წთ უფასო · <?= $taxi->formatPrice($waitRateS) ?>/წთ</span>
                    </div>
                </div>
            </div>
            <?php endif ?>
            <?php
            if($waitingNow):
                $wsNow = $waitLive;
            ?>
            <script>
            (function(){
                var base=Date.now()-<?= $wsNow ?>000;
                var prevSecs=<?= $waitSecs ?>;
                var freeS=<?= $waitFreeS ?>;
                var rate=<?= $waitRateS ?>;
                var sym=<?= json_encode($sym) ?>;
                function tick(){
                    var live=Math.floor((Date.now()-base)/1000);
                    var total=prevSecs+live;
                    var m=Math.floor(total/60),s=total%60;
                    var el=document.getElementById('adminWaitTime');
                    if(el) el.textContent=String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');
                    var billable=Math.max(0,(total-freeS)/60);
                    var fee=Math.round(billable*rate*100)/100;
                    var ef=document.getElementById('adminWaitFee');
                    if(ef&&fee>0) ef.textContent='+'+sym+fee.toFixed(2);
                }
                setInterval(tick,1000); tick();
            })();
            </script>
            <?php endif ?>
        </div>
    </div>
    <?php
    $currentOffer = $taxi->getCurrentOffer((int)$ride['id']);
    ?>
    <?php if($ride['status'] === 'pending' && !empty($currentOffer)): ?>
    <div class="card" style="border-color:#fde68a">
        <div class="card-header" style="background:#fef3c7">
            <h3>🔍 Dispatching…</h3>
            <span style="font-size:13px;color:#92400e">Offer sent to driver</span>
        </div>
        <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;font-size:13.5px">
            <div>
                <div style="font-weight:700"><?= e($currentOffer['driver']['name'] ?? '—') ?></div>
                <div style="color:var(--muted)"><?= e($currentOffer['driver']['car_model'] ?? '') ?></div>
                <div style="color:var(--muted)">Expires in <?= (int)$currentOffer['expires_in'] ?>s</div>
            </div>
            <form method="POST" action="<?= e($base) ?>/manage/taxi/rides/<?= (int)$ride['id'] ?>/dispatch">
                <button type="submit" class="btn btn-ghost" style="font-size:12px">🔄 Re-dispatch now</button>
            </form>
        </div>
    </div>
    <?php elseif($ride['status'] === 'pending'): ?>
    <div class="card" style="border-color:#fde68a">
        <div class="card-header" style="background:#fef3c7"><h3>🔍 Waiting for driver</h3></div>
        <div class="card-body" style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:13px;color:#92400e">No offer active — all drivers may be busy</span>
            <form method="POST" action="<?= e($base) ?>/manage/taxi/rides/<?= (int)$ride['id'] ?>/dispatch">
                <button type="submit" class="btn btn-primary" style="font-size:12px">🔄 Dispatch Now</button>
            </form>
        </div>
    </div>
    <?php endif ?>

    <?php if(!empty($ride['driver'])): ?>
    <div class="card">
        <div class="card-header"><h3>Assigned Driver</h3></div>
        <div class="card-body" style="display:flex;gap:16px;align-items:center">
            <div style="width:48px;height:48px;border-radius:50%;background:var(--accent)22;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0">🧑‍✈️</div>
            <div style="font-size:13.5px">
                <div style="font-weight:700"><?= e($ride['driver']['name'])?></div>
                <div style="color:var(--muted)"><?= e($ride['driver']['car_model'])?> · <?= e($ride['driver']['car_number'])?></div>
                <div><a href="tel:<?= e($ride['driver']['phone'])?>"><?= e($ride['driver']['phone'])?></a></div>
                <?php if (!empty($ride['driver']['driver_token'])): ?>
                <div style="margin-top:4px"><a href="<?= e($base) ?>/taxi/driver/<?= e($ride['driver']['driver_token']) ?>" target="_blank" style="font-size:12px">🔗 Driver Portal</a></div>
                <?php endif ?>
            </div>
        </div>
    </div>
    <?php endif ?>
</div>

<div style="display:flex;flex-direction:column;gap:16px">
    <div class="card">
        <div class="card-header"><h3>Update Ride</h3></div>
        <div class="card-body">
            <form method="POST" action="<?= e($base)?>/manage/taxi/rides/<?=(int)$ride['id']?>/update">
                <div class="form-group"><label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach($taxi->allStatuses() as $s): ?>
                        <option value="<?=$s?>" <?=$s===$ride['status']?'selected':''?>><?=$taxi->statusLabel($s)?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Assign Driver</label>
                    <select name="driver_id" class="form-select">
                        <option value="">— Unassigned —</option>
                        <?php foreach($drivers as $d): ?>
                        <option value="<?=(int)$d['id']?>" <?=(int)$d['id']===(int)($ride['driver_id']??0)?'selected':''?>><?= e($d['name'])?> (<?= e($d['car_model'])?>)</option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Actual Price</label>
                    <input type="number" name="actual_price" class="form-input" value="<?= e((string)($ride['actual_price']??''))?>" min="0" step="0.01" placeholder="Leave empty = estimated">
                </div>
                <div class="form-group"><label class="form-label">Payment</label>
                    <select name="payment_status" class="form-select">
                        <option value="unpaid" <?=$ride['payment_status']==='unpaid'?'selected':''?>>Unpaid</option>
                        <option value="paid"   <?=$ride['payment_status']==='paid'?'selected':''?>>Paid</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0"><label class="form-label">Driver Note</label>
                    <textarea name="driver_note" class="form-input" rows="2"><?= e($ride['driver_note'])?></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:14px;font-size:13px">Update</button>
            </form>
        </div>
    </div>
    <a href="<?= e($base)?>/manage/taxi/rides" class="btn btn-ghost" style="text-align:center;justify-content:center">← All Rides</a>
</div>
</div>
