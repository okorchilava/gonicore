<?php
$pageTitle='GoniTaxi';
$activeNav='taxi';
$topbarActions='<a href="'.e($base).'/manage/taxi/rides" class="btn btn-primary" style="font-size:13px">All Rides</a>';
$onlineDrivers = array_filter($drivers, fn($d) => !empty($d['is_online']));
$busyDrivers   = array_filter($drivers, fn($d) => $d['status']==='busy');
$commPct = $taxi->commissionPct();
$platRev = (float)($stats['platRevenue'] ?? round((float)$stats['revenue'] * $commPct / 100, 2));
?>
<style>
.tx-stat{text-align:center;padding:20px 16px}
.tx-stat-val{font-size:28px;font-weight:900;margin-bottom:4px}
.tx-stat-lbl{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px}
.tx-stat-sub{font-size:11px;color:var(--muted);margin-top:3px}
.driver-pill{display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--border)}
.driver-pill:last-child{border-bottom:none}
.driver-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.schip{display:inline-block;font-size:10px;font-weight:700;padding:1px 7px;border-radius:999px}
</style>

<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px">
    <div class="card" style="margin-bottom:0"><div class="tx-stat">
        <div class="tx-stat-val" style="color:#4f46e5"><?= number_format((int)$stats['total']) ?></div>
        <div class="tx-stat-lbl">Total Rides</div>
    </div></div>
    <div class="card" style="margin-bottom:0"><div class="tx-stat">
        <div class="tx-stat-val" style="color:#f59e0b"><?= number_format((int)$stats['pending']) ?></div>
        <div class="tx-stat-lbl">Pending</div>
    </div></div>
    <div class="card" style="margin-bottom:0"><div class="tx-stat">
        <div class="tx-stat-val" style="color:#10b981"><?= number_format((int)$stats['completed']) ?></div>
        <div class="tx-stat-lbl">Completed</div>
    </div></div>
    <div class="card" style="margin-bottom:0"><div class="tx-stat">
        <div class="tx-stat-val" style="color:#10b981"><?= $taxi->formatPrice((float)$stats['revenue']) ?></div>
        <div class="tx-stat-lbl">Total Revenue</div>
        <div class="tx-stat-sub" style="color:#4f46e5;font-weight:700">Site: <?= $taxi->formatPrice($platRev) ?> (<?= $commPct ?>%)</div>
    </div></div>
    <div class="card" style="margin-bottom:0"><div class="tx-stat">
        <div class="tx-stat-val" style="color:#3b82f6">
            <?= count($onlineDrivers) ?><span style="font-size:16px;color:var(--muted)">/<?= count($drivers) ?></span>
        </div>
        <div class="tx-stat-lbl">Online</div>
        <div class="tx-stat-sub"><?= count($busyDrivers) ?> on ride</div>
    </div></div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
#adminMap{height:420px;border-radius:0 0 8px 8px}
#adminMap:fullscreen{height:100vh!important;border-radius:0}
.adm-driver-tip{background:#fff!important;border:1px solid #e2e8f0!important;border-radius:10px!important;padding:10px 14px!important;box-shadow:0 4px 20px rgba(0,0,0,.12)!important;font-family:inherit!important}
.adm-driver-tip::before{display:none!important}
.map-driver-legend{display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.map-leg-item{display:flex;align-items:center;gap:5px;font-size:11.5px;font-weight:600;color:var(--muted)}
@keyframes admpulse{0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.5)}60%{box-shadow:0 0 0 6px rgba(16,185,129,0)}}
.adm-live-dot{width:7px;height:7px;border-radius:50%;background:#10b981;display:inline-block;animation:admpulse 2s infinite;flex-shrink:0}
</style>

<div class="card" style="margin-bottom:20px">
    <div class="card-header" style="justify-content:space-between;align-items:center">
        <h3>🗺 ონლაინ მძღოლები — რუკა</h3>
        <div style="display:flex;align-items:center;gap:12px">
            <span id="mapUpdatedAt" style="font-size:11px;color:var(--muted)"></span>
            <div class="map-driver-legend">
                <span class="map-leg-item"><span style="font-size:16px">🚕</span> Online</span>
                <span class="map-leg-item"><span style="font-size:16px">🚖</span> On Ride</span>
            </div>
            <div style="display:flex;align-items:center;gap:5px">
                <span class="adm-live-dot"></span>
                <span id="mapOnlineCnt" style="font-size:12px;font-weight:700;color:#10b981"><?= count($onlineDrivers) ?> online</span>
            </div>
        </div>
    </div>
    <div id="adminMap"></div>
</div>

<script>
(function(){
var amap=L.map('adminMap',{zoomControl:true,attributionControl:false}).setView([41.6938,44.8015],14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19}).addTo(amap);

var icoOnline=L.divIcon({html:'<div style="font-size:36px;line-height:1;filter:drop-shadow(0 2px 6px rgba(0,0,0,.4))">🚕</div>',iconSize:[42,42],iconAnchor:[21,21],className:''});
var icoBusy  =L.divIcon({html:'<div style="position:relative;display:inline-block"><div style="font-size:36px;line-height:1;filter:drop-shadow(0 2px 6px rgba(239,68,68,.5))">🚖</div><div style="position:absolute;top:0;right:0;width:11px;height:11px;border-radius:50%;background:#ef4444;border:2px solid #fff"></div></div>',iconSize:[44,44],iconAnchor:[22,22],className:''});

var markers={};
var API=<?= json_encode($base.'/api/taxi/online-drivers') ?>;

// ── Fullscreen control ──────────────────────────────────────────────────────
var FsControl=L.Control.extend({
    options:{position:'topleft'},
    onAdd:function(){
        var btn=L.DomUtil.create('button','leaflet-bar leaflet-control leaflet-control-custom');
        btn.title='სრული ეკრანი';
        btn.style.cssText='width:30px;height:30px;background:#fff;border:none;cursor:pointer;font-size:15px;display:flex;align-items:center;justify-content:center;border-radius:4px;box-shadow:0 1px 5px rgba(0,0,0,.2)';
        btn.innerHTML='⛶';
        L.DomEvent.disableClickPropagation(btn);
        L.DomEvent.on(btn,'click',function(){
            var el=document.getElementById('adminMap');
            if(!document.fullscreenElement){
                el.requestFullscreen();btn.innerHTML='✕';
            } else {
                document.exitFullscreen();btn.innerHTML='⛶';
            }
        });
        document.addEventListener('fullscreenchange',function(){
            if(!document.fullscreenElement)btn.innerHTML='⛶';
            else btn.innerHTML='✕';
            setTimeout(function(){amap.invalidateSize();},200);
        });
        return btn;
    }
});
amap.addControl(new FsControl());

function tooltipHtml(d){
    var sc=d.status==='busy'?'#dc2626':'#16a34a';
    var sb=d.status==='busy'?'#fee2e2':'#f0fdf4';
    var sl=d.status==='busy'?'🔴 მგზავრობაზე':'🟢 ონლაინ';
    var carLine='';
    if(d.car||d.car_color||d.car_number){
        carLine='<div style="font-size:11.5px;color:#334155;margin-bottom:5px;display:flex;align-items:center;gap:6px">';
        if(d.car_color) carLine+='<span style="width:11px;height:11px;border-radius:50%;background:'+d.car_color+';border:1.5px solid #e2e8f0;flex-shrink:0;display:inline-block"></span>';
        carLine+='<span>'+(d.car||'')+(d.car_number?' · <b>'+d.car_number+'</b>':'')+'</span></div>';
    }
    var speedLine='';
    if(d.speed!=null){
        var spIcon=d.speed<2?'🅿':'⚡';
        var spColor=d.speed<2?'#64748b':'#3b82f6';
        speedLine='<div style="font-size:11.5px;font-weight:700;margin-bottom:5px;color:'+spColor+'">'+spIcon+' '+d.speed+' კმ/სთ</div>';
    }
    return '<div style="min-width:170px;font-family:inherit;padding:2px 0">'
        +'<div style="font-size:13.5px;font-weight:800;margin-bottom:4px">🧑‍✈️ '+d.name+'</div>'
        +carLine+speedLine
        +'<span style="font-size:11px;font-weight:700;padding:2px 9px;border-radius:999px;background:'+sb+';color:'+sc+'">'+sl+'</span>'
        +'</div>';
}
function refresh(){
    fetch(API).then(function(r){return r.json();}).then(function(data){
        var list=data.drivers||[];
        var ids=list.map(function(d){return d.id;});
        Object.keys(markers).forEach(function(id){
            if(!ids.includes(+id)){amap.removeLayer(markers[id]);delete markers[id];}
        });
        list.forEach(function(d){
            var ico=d.status==='busy'?icoBusy:icoOnline;
            if(markers[d.id]){
                markers[d.id].setLatLng([d.lat,d.lng]);
                markers[d.id].setIcon(ico);
                markers[d.id].setTooltipContent(tooltipHtml(d));
            } else {
                markers[d.id]=L.marker([d.lat,d.lng],{icon:ico})
                    .addTo(amap)
                    .bindTooltip(tooltipHtml(d),{
                        direction:'top',
                        offset:[0,-20],
                        opacity:1,
                        className:'adm-driver-tip'
                    });
            }
        });
        document.getElementById('mapOnlineCnt').textContent=list.length+' online';
        var t=new Date();
        document.getElementById('mapUpdatedAt').textContent='განახლდა '+t.getHours()+':'+String(t.getMinutes()).padStart(2,'0')+':'+String(t.getSeconds()).padStart(2,'0');
        if(list.length>0 && Object.keys(markers).length===list.length){
            var pts=list.map(function(d){return[d.lat,d.lng];});
            if(pts.length===1) amap.setView(pts[0],16);
            else amap.fitBounds(L.latLngBounds(pts),{padding:[50,50]});
        }
    }).catch(function(){});
}
refresh();
setInterval(refresh,15000);
})();
</script>

<div style="display:grid;grid-template-columns:1fr 290px;gap:16px">

<div class="card">
    <div class="card-header" style="justify-content:space-between">
        <h3>Recent Rides</h3>
        <a href="<?= e($base)?>/manage/taxi/rides" style="font-size:13px;color:var(--accent)">View all →</a>
    </div>
    <?php if(empty($recent)): ?>
    <div class="empty"><div class="empty-icon">🚕</div><h3>No rides yet</h3></div>
    <?php else: ?>
    <table class="data-table">
    <thead><tr><th>#</th><th>Customer</th><th>Route</th><th>Fare</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach($recent as $rd):
        $c=$taxi->statusColor($rd['status']);
        $fare=$taxi->formatPrice((float)($rd['actual_price']??$rd['estimated_price']));
    ?>
    <tr>
        <td style="font-family:monospace;font-size:11px;color:var(--muted)"><?= e($rd['ride_number']) ?></td>
        <td style="font-size:13px;font-weight:600"><?= e($rd['customer_name']?:$rd['customer_phone']) ?></td>
        <td style="font-size:12px;color:var(--muted);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($rd['pickup_address'])?> → <?= e($rd['destination'])?></td>
        <td style="font-size:13px;font-weight:700;color:#10b981"><?= $fare ?></td>
        <td><span style="background:<?=$c?>22;color:<?=$c?>;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:999px"><?= $taxi->statusLabel($rd['status'])?></span></td>
        <td><a href="<?= e($base)?>/manage/taxi/rides/<?=(int)$rd['id']?>" class="btn btn-ghost" style="font-size:12px;padding:4px 10px">→</a></td>
    </tr>
    <?php endforeach ?>
    </tbody></table>
    <?php endif ?>
</div>

<div style="display:flex;flex-direction:column;gap:12px">
    <div class="card">
        <div class="card-header" style="justify-content:space-between">
            <h3>Drivers</h3>
            <a href="<?= e($base)?>/manage/taxi/drivers" style="font-size:12px;color:var(--accent)">Manage →</a>
        </div>
        <?php if(empty($drivers)): ?>
        <div style="padding:16px;color:var(--muted);font-size:13px;text-align:center">No drivers added</div>
        <?php else: foreach($drivers as $d):
            $online=!empty($d['is_online']);
            $sc=$d['status']==='busy'?'#ef4444':($online?'#10b981':'#94a3b8');
            $sl=$d['status']==='busy'?'On Ride':($online?'Online':'Offline');
        ?>
        <div class="driver-pill">
            <div class="driver-dot" style="background:<?= $sc ?>"></div>
            <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($d['name']) ?></div>
                <div style="font-size:11px;color:var(--muted)"><?= e($d['car_model']?:($taxi->carTypes()[$d['car_type']]??'')) ?></div>
            </div>
            <div style="text-align:right;flex-shrink:0">
                <span class="schip" style="background:<?= $sc ?>22;color:<?= $sc ?>"><?= $sl ?></span>
                <?php if((float)($d['avg_rating']??0)>0): ?>
                <div style="font-size:10px;color:#f59e0b;margin-top:2px">★ <?= number_format((float)$d['avg_rating'],1) ?> · <?= (int)($d['total_trips']??0) ?> trips</div>
                <?php endif ?>
            </div>
        </div>
        <?php endforeach; endif ?>
    </div>

    <div class="card">
        <div class="card-header"><h3>Quick Links</h3></div>
        <div style="display:flex;flex-direction:column;gap:6px;padding:14px 16px">
            <a href="<?= e($base)?>/manage/taxi/drivers"  class="btn btn-ghost" style="font-size:12.5px;justify-content:center">🧑‍✈️ Manage Drivers</a>
            <a href="<?= e($base)?>/manage/taxi/routes"   class="btn btn-ghost" style="font-size:12.5px;justify-content:center">🗺 Fixed Routes</a>
            <a href="<?= e($base)?>/manage/taxi/tariffs"  class="btn btn-ghost" style="font-size:12.5px;justify-content:center">💵 Tariffs</a>
            <a href="<?= e($base)?>/manage/taxi/settings" class="btn btn-ghost" style="font-size:12.5px;justify-content:center">⚙ Settings</a>
        </div>
    </div>
</div>

</div>
