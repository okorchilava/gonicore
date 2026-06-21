<?php
$slug      = $taxi->setting('page_slug','taxi');
$sym       = $taxi->setting('currency_symbol','₾');
$sc        = $taxi->statusColor($ride['status']);
$rated     = isset($_GET['rated']);
$canCancel = in_array($ride['status'],['pending','accepted','driver_assigned'],true) && empty($ride['cancelled_by']);
$canRate   = $ride['status']==='completed' && empty($ride['rating']) && !$rated;
$steps     = ['pending','accepted','driver_assigned','in_progress','completed'];
$stepIcons = ['⏳','✅','🧑‍✈️','🚕','🏁'];
$stepLabels= ['Pending','Accepted','On Way','In Ride','Done'];
$curIdx    = array_search($ride['status'],$steps,true);
$pct       = $curIdx===false ? 0 : ($curIdx>=count($steps)-1 ? 100 : round($curIdx/(count($steps)-1)*100));
$statusIcon= match($ride['status']){
    'completed'=>'🏁','cancelled'=>'❌','in_progress'=>'🚕','driver_assigned'=>'🧑‍✈️',default=>'⏳'};
$hasMap    = in_array($ride['status'],['driver_assigned','in_progress'],true);
$hasDriver = !empty($ride['driver']) && in_array($ride['status'],['driver_assigned','in_progress','completed'],true);
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
body{overflow:hidden}
.gc-main{padding:0!important;max-width:none!important}

:root{
  --tr-bg:#f1f5f9;--tr-surface:#fff;--tr-border:#e2e8f0;--tr-border2:#f1f5f9;
  --tr-text:#0f172a;--tr-muted:#64748b;
  --tr-accent:#4f46e5;--tr-green:#10b981;--tr-amber:#f59e0b;--tr-red:#ef4444;
  --tr-shadow:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.08);
}

.tr-root{display:flex;flex-direction:column;margin-top:72px;height:calc(100vh - 72px);background:var(--tr-bg)}

.tr-topbar{background:var(--tr-surface);border-bottom:1px solid var(--tr-border);padding:0 20px;height:52px;display:flex;align-items:center;gap:12px;flex-shrink:0;z-index:100;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.tr-num{font-family:'Courier New',monospace;font-size:14px;font-weight:900;color:var(--tr-accent);letter-spacing:.5px}
.tr-tbsep{width:1px;height:20px;background:var(--tr-border)}
.tr-status-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;font-size:12.5px;font-weight:700;border:1.5px solid}
.tr-tb-time{font-size:12px;color:var(--tr-muted)}
.tr-tb-right{margin-left:auto;display:flex;gap:8px;align-items:center}
.tr-tb-btn{padding:6px 14px;border-radius:8px;font-size:12.5px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;border:1.5px solid var(--tr-border);background:var(--tr-bg);color:var(--tr-text);transition:all .15s;white-space:nowrap}
.tr-tb-btn:hover{border-color:#c7d2fe;background:#eef2ff;color:var(--tr-accent)}

.tr-body{display:grid;grid-template-columns:1fr 380px;flex:1;overflow:hidden}

.tr-map-col{position:relative;background:#cdd8e0;overflow:hidden}
#trackMap{width:100%;height:100%;z-index:0}
.tr-map-badge{position:absolute;z-index:900;pointer-events:none}
.tr-map-top{top:14px;left:14px;right:14px}
.tr-map-bottom{bottom:14px;left:14px;right:14px}
.tr-stat-row{display:grid;gap:8px}
.tr-stat-row.two{grid-template-columns:1fr 1fr}
.tr-stat-row.three{grid-template-columns:1fr 1fr 1fr}
.tr-stat-box{background:rgba(255,255,255,.94);backdrop-filter:blur(10px);border-radius:12px;padding:11px 14px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.12)}
.tr-stat-val{font-size:17px;font-weight:900;margin-bottom:1px;color:var(--tr-text)}
.tr-stat-lbl{font-size:10px;color:var(--tr-muted);text-transform:uppercase;letter-spacing:.5px;font-weight:700}

.tr-search-badge{position:absolute;top:14px;left:14px;right:14px;z-index:900;pointer-events:none;display:flex;justify-content:center}
.tr-search-pill{background:rgba(255,249,235,.97);border:1.5px solid #fbbf24;border-radius:999px;padding:9px 20px;display:inline-flex;align-items:center;gap:10px;box-shadow:0 4px 20px rgba(245,158,11,.25)}
.tr-search-dot{width:10px;height:10px;border-radius:50%;background:#f59e0b;flex-shrink:0;animation:sdot 1.4s ease-in-out infinite}
@keyframes sdot{0%,100%{box-shadow:0 0 0 0 rgba(245,158,11,.5)}60%{box-shadow:0 0 0 8px rgba(245,158,11,0)}}

.tr-end-overlay{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;z-index:900;background:linear-gradient(150deg,#0f172a 0%,#1e1b4b 100%)}
.tr-end-card{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:24px;padding:36px 32px;text-align:center;color:#fff;max-width:300px}
.tr-end-icon{font-size:56px;margin-bottom:14px;display:block}
.tr-end-title{font-size:24px;font-weight:900;margin-bottom:6px}
.tr-end-sub{font-size:13px;color:#94a3b8;margin-bottom:16px}
.tr-end-price{font-size:40px;font-weight:900;color:#34d399}
.tr-cancel-overlay{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;z-index:900;background:rgba(15,23,42,.6);backdrop-filter:blur(4px)}
.tr-cancel-card{background:#fff;border-radius:20px;padding:32px 28px;text-align:center;max-width:300px;box-shadow:0 20px 60px rgba(0,0,0,.3)}

.tr-live-pill{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.94);backdrop-filter:blur(8px);border-radius:999px;padding:5px 12px;font-size:11.5px;font-weight:700;color:var(--tr-text);box-shadow:0 2px 8px rgba(0,0,0,.1)}
.live-dot{width:7px;height:7px;border-radius:50%;background:var(--tr-green);animation:lpulse 1.5s infinite;display:inline-block}
@keyframes lpulse{0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.4)}60%{box-shadow:0 0 0 5px rgba(16,185,129,0)}}
@keyframes mylocpulse{0%{transform:translateX(-50%) scale(1);opacity:.7}100%{transform:translateX(-50%) scale(2.6);opacity:0}}
@keyframes breathe{0%,100%{transform:scale(1)}50%{transform:scale(1.15)}}

.tr-panel{background:var(--tr-surface);border-left:1px solid var(--tr-border);overflow-y:auto;display:flex;flex-direction:column}
.tr-panel::-webkit-scrollbar{width:4px}
.tr-panel::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}

.tr-prog-wrap{padding:18px 20px 14px;border-bottom:1px solid var(--tr-border)}
.tr-prog{display:flex;align-items:flex-start;position:relative}
.tr-line-bg{position:absolute;top:14px;left:0;width:100%;height:2px;background:var(--tr-border);z-index:0}
.tr-line-fill{position:absolute;top:14px;left:0;height:2px;background:linear-gradient(to right,var(--tr-accent),var(--tr-green));z-index:1;transition:width .6s ease}
.tr-step{flex:1;text-align:center;position:relative;z-index:2}
.tr-dot{width:28px;height:28px;border-radius:50%;margin:0 auto 5px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;background:var(--tr-surface);border:2px solid #cbd5e1;transition:all .3s}
.tr-step.done .tr-dot{background:var(--tr-accent);border-color:var(--tr-accent);color:#fff}
.tr-step.cur .tr-dot{background:var(--tr-green);border-color:var(--tr-green);color:#fff;animation:dotpulse 2s infinite}
@keyframes dotpulse{0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.2)}50%{box-shadow:0 0 0 8px rgba(16,185,129,0)}}
.tr-step-lbl{font-size:9px;color:var(--tr-muted);font-weight:700;line-height:1.3;text-transform:uppercase;letter-spacing:.3px}
.tr-step.done .tr-step-lbl,.tr-step.cur .tr-step-lbl{color:var(--tr-text)}

.tr-driver{padding:16px 20px;border-bottom:1px solid var(--tr-border);display:flex;align-items:center;gap:14px;background:linear-gradient(to bottom,#fafbff,var(--tr-surface))}
.tr-dr-av{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--tr-accent),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;box-shadow:0 4px 12px rgba(79,70,229,.3)}
.tr-dr-info{flex:1;min-width:0}
.tr-dr-name{font-size:15px;font-weight:800;margin-bottom:2px;color:var(--tr-text)}
.tr-dr-car{font-size:12px;color:var(--tr-muted);display:flex;align-items:center;gap:5px;flex-wrap:wrap}
.color-dot{width:9px;height:9px;border-radius:50%;border:1px solid rgba(0,0,0,.15);flex-shrink:0;display:inline-block}
.tr-dr-rating{font-size:12px;margin-top:3px;font-weight:700;color:var(--tr-amber)}
.tr-dr-phone{font-size:12px;color:var(--tr-muted);margin-top:3px}

.tr-section{border-bottom:1px solid var(--tr-border)}
.tr-section-title{padding:10px 20px 6px;font-size:10px;font-weight:800;color:var(--tr-muted);text-transform:uppercase;letter-spacing:.7px}
.tr-row{display:flex;justify-content:space-between;align-items:flex-start;padding:9px 20px;border-bottom:1px solid var(--tr-border2);font-size:13px;gap:12px}
.tr-row:last-child{border-bottom:none}
.tr-row .lbl{color:var(--tr-muted);flex-shrink:0;font-size:12px}
.tr-row .val{font-weight:600;text-align:right;word-break:break-word;color:var(--tr-text)}

.tr-actions{position:sticky;bottom:0;background:var(--tr-surface);border-top:1px solid var(--tr-border);padding:10px 14px;display:flex;gap:8px;z-index:50;box-shadow:0 -3px 12px rgba(0,0,0,.06);flex-wrap:wrap}
.tr-act{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 16px;border-radius:999px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none;border:1.5px solid;transition:all .15s;white-space:nowrap;flex:1;min-width:0;line-height:1}
.tr-act:hover{transform:translateY(-1px)}
.tr-act:active{transform:none}
.tr-act-call{background:#f0fdf4;color:#15803d;border-color:#86efac}
.tr-act-call:hover{background:#dcfce7;border-color:#4ade80}
.tr-act-cancel{background:#fef2f2;color:var(--tr-red);border-color:#fca5a5}
.tr-act-cancel:hover{background:#fee2e2;border-color:#f87171}
.tr-act-new{background:var(--tr-accent);color:#fff;border-color:var(--tr-accent);box-shadow:0 2px 8px rgba(79,70,229,.25)}
.tr-act-new:hover{background:#4338ca;border-color:#4338ca}

.star-row{display:flex;justify-content:center;gap:8px;margin:14px 0}
.star-btn{background:none;border:none;font-size:34px;cursor:pointer;opacity:.2;transition:all .15s;line-height:1;padding:0}
.star-btn.on{opacity:1;transform:scale(1.1)}

#cancelModal{display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:9999;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px)}
#cancelModal.open{display:flex}
.cancel-sheet{background:#fff;border-radius:20px;width:100%;max-width:420px;padding:26px;animation:sheetIn .2s cubic-bezier(.2,1,.3,1)}
@keyframes sheetIn{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:none}}
.cancel-reason-btn{width:100%;padding:10px 14px;border:1.5px solid var(--tr-border);border-radius:10px;font-size:13px;cursor:pointer;font-family:inherit;background:#fff;text-align:left;transition:all .15s;margin-bottom:6px;display:block}
.cancel-reason-btn:hover{border-color:var(--tr-red);background:#fef2f2;color:var(--tr-red)}
.cancel-keep-btn{width:100%;padding:10px;border:none;background:none;font-size:13px;font-weight:700;color:var(--tr-muted);cursor:pointer;font-family:inherit;margin-top:4px;border-radius:10px;transition:all .15s}
.cancel-keep-btn:hover{background:var(--tr-bg);color:var(--tr-text)}

@media(max-width:900px){
  body{overflow:auto}
  .gc-main{padding:0!important}
  .tr-root{height:auto;margin-top:0}
  .tr-topbar{height:auto;min-height:48px;padding:8px 12px;flex-wrap:wrap;gap:6px}
  .tr-tbsep{display:none}
  .tr-tb-time{display:none}
  .tr-tb-right{margin-left:auto}
  .tr-num{font-size:12px}
  .tr-body{grid-template-columns:1fr;overflow:visible}
  .tr-map-col{height:50vh;min-height:240px;max-height:380px}
  .tr-panel{overflow:visible}
  .tr-prog-wrap{padding:12px 14px 10px}
  .tr-dot{width:24px;height:24px;font-size:10px;margin-bottom:4px}
  .tr-step-lbl{font-size:8px;letter-spacing:.1px}
  .tr-line-bg,.tr-line-fill{top:12px}
  .tr-driver{padding:12px 14px;gap:10px}
  .tr-dr-av{width:44px;height:44px;font-size:18px}
  .tr-dr-name{font-size:14px}
  .tr-section-title{padding:8px 14px 5px;font-size:9.5px}
  .tr-row{padding:8px 14px;font-size:12.5px}
  .tr-row .lbl{font-size:11.5px}
  .star-row{gap:6px}
  .star-btn{font-size:30px}
  .tr-actions{padding:10px 12px;gap:7px}
  .tr-act{padding:13px 10px;font-size:13px;border-radius:14px}
  .tr-stat-box{padding:8px 10px}
  .tr-stat-val{font-size:14px}
  .tr-stat-lbl{font-size:9px}
  .tr-search-pill{padding:8px 14px;font-size:13px}
  .cancel-sheet{padding:20px 18px}
  .cancel-reason-btn{padding:12px}
}
</style>

<div class="tr-root">

<!-- ── TOP BAR ───────────────────────────────────────────────────────────── -->
<div class="tr-topbar">
    <span class="tr-num"><?= e($ride['ride_number']) ?></span>
    <div class="tr-tbsep"></div>
    <div class="tr-status-chip" style="background:<?= $sc ?>18;border-color:<?= $sc ?>44;color:<?= $sc ?>">
        <?= $taxi->statusLabel($ride['status']) ?>
    </div>
    <div class="tr-tbsep"></div>
    <span class="tr-tb-time">Booked <?= date('d M Y · H:i',strtotime($ride['created_at'])) ?></span>
    <div class="tr-tb-right">
        <?php if($taxi->setting('phone')): ?>
        <a href="tel:<?= e($taxi->setting('phone')) ?>" class="tr-tb-btn" style="background:#4f46e5;color:#fff">📞 Support</a>
        <?php endif ?>
        <a href="<?= e($base).'/'.$slug ?>" class="tr-tb-btn" style="background:var(--tr-bg);color:var(--tr-text);border:1.5px solid var(--tr-border)">+ New Ride</a>
    </div>
</div>

<!-- ── BODY ──────────────────────────────────────────────────────────────── -->
<div class="tr-body">

<!-- Left: MAP -->
<div class="tr-map-col">
    <?php if($ride['status']==='pending'): ?>
    <div id="trackMap"></div>
    <div class="tr-search-badge">
        <div class="tr-search-pill">
            <span class="tr-search-dot"></span>
            <span style="font-size:14px;font-weight:700;color:#92400e">ვეძებთ მახლობელ მძღოლს…</span>
            <?php if(!empty($currentOffer)&&!empty($currentOffer['driver'])): ?>
            <span style="font-size:12px;color:#4f46e5;font-weight:700"><span id="offerTimer"><?= (int)$currentOffer['expires_in'] ?></span>s</span>
            <?php endif ?>
        </div>
    </div>

    <?php elseif($ride['status']==='cancelled'): ?>
    <div id="trackMap"></div>
    <div class="tr-cancel-overlay">
        <div class="tr-cancel-card">
            <div style="font-size:52px;margin-bottom:10px">❌</div>
            <div style="font-size:20px;font-weight:800;color:#dc2626;margin-bottom:6px">მგზავრობა გაუქმდა</div>
            <?php if(!empty($ride['cancel_reason'])): ?>
            <div style="font-size:13px;color:#64748b;margin-bottom:18px"><?= e($ride['cancel_reason']) ?></div>
            <?php endif ?>
            <a href="<?= e($base).'/'.$taxi->setting('page_slug','taxi') ?>" style="display:inline-block;background:#4f46e5;color:#fff;border-radius:10px;padding:10px 24px;font-size:14px;font-weight:700;text-decoration:none">+ ახალი მგზავრობა</a>
        </div>
    </div>

    <?php elseif($ride['status']==='completed'): ?>
    <div class="tr-end-overlay">
        <div class="tr-end-card">
            <div class="tr-end-icon">🏁</div>
            <div class="tr-end-title">Ride Completed</div>
            <div class="tr-end-sub">Thank you for using <?= e($taxi->setting('brand_name','GoniTaxi')) ?>!</div>
            <div style="margin-top:16px;font-size:28px;font-weight:900;color:var(--tr-accent)"><?= $taxi->formatPrice((float)($ride['actual_price']??$ride['estimated_price'])) ?></div>
        </div>
    </div>

    <?php else: ?>
    <!-- Live map -->
    <div id="trackMap"></div>

    <!-- Stats overlay -->
    <?php if($ride['status']==='driver_assigned'): ?>
    <div class="tr-map-badge tr-map-top">
        <div class="tr-stat-row two">
            <div class="tr-stat-box"><div class="tr-stat-val" style="color:#7c3aed" id="statDist">—</div><div class="tr-stat-lbl">მანძილი</div></div>
            <div class="tr-stat-box"><div class="tr-stat-val" style="color:var(--tr-amber)" id="statEta">—</div><div class="tr-stat-lbl">სავარ. დრო</div></div>
        </div>
    </div>
    <!-- Waiting banner (hidden until driver starts waiting) -->
    <div id="waitBanner" style="display:none;position:absolute;top:80px;left:14px;right:14px;z-index:910">
        <div style="background:rgba(245,158,11,.97);backdrop-filter:blur(8px);border-radius:16px;padding:14px 16px;box-shadow:0 6px 24px rgba(245,158,11,.45)">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                <div style="font-size:24px;animation:breathe 2s ease-in-out infinite;flex-shrink:0">⏱</div>
                <div style="flex:1">
                    <div style="font-size:13.5px;font-weight:800;color:#fff">მძღოლი გელოდებათ!</div>
                    <div style="font-size:11.5px;color:rgba(255,255,255,.85);margin-top:1px" id="waitBannerText">—</div>
                </div>
                <div style="font-size:22px;font-weight:900;color:#fff;font-variant-numeric:tabular-nums" id="waitBannerTime">00:00</div>
            </div>
            <div id="waitBannerFeeWrap" style="display:none;background:rgba(0,0,0,.18);border-radius:10px;padding:8px 14px;text-align:center">
                <div style="font-size:11px;color:rgba(255,255,255,.75);font-weight:600;margin-bottom:2px">დამატებითი გადასახადი</div>
                <div style="font-size:28px;font-weight:900;color:#fff;letter-spacing:-.5px;line-height:1" id="waitBannerFee"></div>
            </div>
        </div>
    </div>
    <?php elseif($ride['status']==='in_progress'): ?>
    <div class="tr-map-badge tr-map-top">
        <div class="tr-stat-row three">
            <div class="tr-stat-box"><div class="tr-stat-val" id="statTotal">—</div><div class="tr-stat-lbl">სულ</div></div>
            <div class="tr-stat-box"><div class="tr-stat-val" style="color:var(--tr-green)" id="statDist">—</div><div class="tr-stat-lbl">დარჩა</div></div>
            <div class="tr-stat-box"><div class="tr-stat-val" style="color:var(--tr-amber)" id="statEta">—</div><div class="tr-stat-lbl">სავარ.</div></div>
        </div>
    </div>
    <?php endif ?>

    <!-- Live label -->
    <div class="tr-map-badge tr-map-bottom" style="right:auto">
        <div class="tr-live-pill">
            <span class="live-dot"></span><span id="locAge">Locating driver…</span>
        </div>
    </div>
    <?php endif ?>
</div>

<!-- Right: INFO PANEL -->
<div class="tr-panel">

    <!-- Progress bar -->
    <?php if($ride['status']!=='cancelled'): ?>
    <div class="tr-prog-wrap">
        <div class="tr-prog">
            <div class="tr-line-bg"></div>
            <div class="tr-line-fill" style="width:<?= $pct ?>%"></div>
            <?php foreach($steps as $i=>$s):
                $cls='';
                if($curIdx!==false){ if($i<$curIdx) $cls='done'; elseif($i===$curIdx) $cls='cur'; }
            ?>
            <div class="tr-step <?= $cls ?>">
                <div class="tr-dot"><?= $cls==='done'?'✓':$stepIcons[$i] ?></div>
                <div class="tr-step-lbl"><?= $stepLabels[$i] ?></div>
            </div>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>

    <!-- Driver card -->
    <?php if($hasDriver): ?>
    <div class="tr-driver">
        <div class="tr-dr-av">🧑‍✈️</div>
        <div class="tr-dr-info">
            <div class="tr-dr-name"><?= e($ride['driver']['name']) ?></div>
            <?php if(!empty($ride['driver']['car_model'])): ?>
            <div class="tr-dr-car">
                <?php if(!empty($ride['driver']['car_color'])): ?>
                <span class="color-dot" style="background:<?= e($ride['driver']['car_color']) ?>"></span>
                <?php endif ?>
                <?= e($ride['driver']['car_model']) ?>
                <?= !empty($ride['driver']['car_number'])?' · '.e($ride['driver']['car_number']):'' ?>
                <?php if(!empty($ride['driver']['car_color'])): ?> · <?= e(ucfirst($ride['driver']['car_color'])) ?><?php endif ?>
            </div>
            <?php endif ?>
            <?php $avgR=(float)($ride['driver']['avg_rating']??0); if($avgR>0): ?>
            <div class="tr-dr-rating">★ <?= number_format($avgR,1) ?> · <?= (int)($ride['driver']['total_trips']??0) ?> trips</div>
            <?php endif ?>
            <?php if(!empty($ride['driver']['phone'])): ?>
            <div class="tr-dr-phone">📱 <?= e($ride['driver']['phone']) ?></div>
            <?php endif ?>
        </div>
    </div>
    <?php endif ?>

    <!-- Ride info -->
    <div class="tr-section">
        <div class="tr-section-title">Ride Details</div>
        <div class="tr-row"><span class="lbl">📍 Pickup</span><span class="val"><?= e($ride['pickup_address']) ?></span></div>
        <div class="tr-row"><span class="lbl">🏁 Destination</span><span class="val"><?= e($ride['destination']) ?></span></div>
        <div class="tr-row"><span class="lbl">🚗 Car</span><span class="val"><?= e($taxi->carTypes()[$ride['car_type']]??$ride['car_type']) ?> · <?= (int)$ride['passengers'] ?> pax</span></div>
        <?php if($ride['distance_km']): ?>
        <div class="tr-row"><span class="lbl">📏 Distance</span><span class="val"><?= number_format((float)$ride['distance_km'],1) ?> km</span></div>
        <?php endif ?>
        <?php if($ride['scheduled_at']): ?>
        <div class="tr-row"><span class="lbl">🕐 Scheduled</span><span class="val"><?= date('d M Y, H:i',strtotime($ride['scheduled_at'])) ?></span></div>
        <?php endif ?>
    </div>

    <!-- Payment -->
    <div class="tr-section">
        <div class="tr-section-title">Payment</div>
        <div class="tr-row">
            <span class="lbl">💰 Fare</span>
            <span class="val" style="color:var(--tr-accent);font-size:20px;font-weight:900"><?= $taxi->formatPrice((float)($ride['actual_price']??$ride['estimated_price'])) ?></span>
        </div>
        <?php if(!empty($ride['actual_price'])&&$ride['actual_price']!=$ride['estimated_price']): ?>
        <div class="tr-row"><span class="lbl">Est.</span><span class="val" style="color:var(--tr-muted);text-decoration:line-through"><?= $taxi->formatPrice((float)$ride['estimated_price']) ?></span></div>
        <?php endif ?>
        <div class="tr-row">
            <span class="lbl">💳 Method</span>
            <span class="val"><?= ucfirst($ride['payment_method']) ?> ·
            <span style="font-weight:700;color:<?= $ride['payment_status']==='paid'?'#10b981':'#f59e0b' ?>">
                <?= $ride['payment_status']==='paid'?'✓ Paid':'Pending' ?>
            </span></span>
        </div>
        <?php if(!empty($ride['rating'])): ?>
        <div class="tr-row">
            <span class="lbl">⭐ Your rating</span>
            <span class="val" style="color:var(--tr-amber);font-size:16px;letter-spacing:2px">
                <?php for($i=1;$i<=5;$i++) echo $i<=(int)$ride['rating']?'★':'☆'; ?>
            </span>
        </div>
        <?php endif ?>
    </div>

    <!-- Rate -->
    <?php if($canRate): ?>
    <div class="tr-section" style="border-left:3px solid var(--tr-amber)">
        <div class="tr-section-title" style="color:var(--tr-amber)">⭐ Rate your ride</div>
        <form method="POST" action="<?= e($base) ?>/taxi/track/<?= e($ride['ride_number']) ?>/rate" style="padding:12px 20px">
            <div style="font-size:13px;color:var(--tr-muted);margin-bottom:4px;text-align:center">How was your experience?</div>
            <div class="star-row" id="starRow">
                <?php for($i=1;$i<=5;$i++): ?>
                <button type="button" class="star-btn <?= $i<=5?'on':'' ?>" data-v="<?= $i ?>" onclick="setStar(<?= $i ?>)">★</button>
                <?php endfor ?>
            </div>
            <input type="hidden" name="rating" id="ratingInput" value="5">
            <button type="submit" class="tr-btn tr-btn-accent">Submit Rating</button>
        </form>
    </div>
    <?php endif ?>

    <?php if($rated): ?>
    <div style="margin:12px 20px;background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:12px 16px;text-align:center">
        <span style="font-size:18px">⭐</span>
        <span style="font-weight:700;color:#15803d;margin-left:8px">Thank you for your rating!</span>
    </div>
    <?php endif ?>

    <!-- Actions -->
    <?php $showActions = $canCancel || ($hasDriver && !empty($ride['driver']['phone'])) || in_array($ride['status'],['completed','cancelled'],true); ?>
    <?php if($showActions): ?>
    <div class="tr-actions">
        <?php if($hasDriver && !empty($ride['driver']['phone'])): ?>
        <a href="tel:<?= e($ride['driver']['phone']) ?>" class="tr-act tr-act-call">📞 მძღოლი</a>
        <?php endif ?>
        <?php if($canCancel): ?>
        <form method="POST" action="<?= e($base) ?>/taxi/track/<?= e($ride['ride_number']) ?>/cancel" id="cancelForm" style="flex:1;display:flex">
            <input type="hidden" name="reason" id="cancelReason" value="">
            <button type="button" class="tr-act tr-act-cancel" onclick="showCancel()" style="width:100%">✕ გაუქმება</button>
        </form>
        <?php endif ?>
        <?php if($ride['status']==='completed'||$ride['status']==='cancelled'): ?>
        <a href="<?= e($base).'/'.$slug ?>" class="tr-act tr-act-new">+ ახალი</a>
        <?php endif ?>
    </div>
    <?php endif ?>
</div><!-- /right panel -->

</div><!-- /tr-body -->
</div><!-- /tr-root -->

<!-- ── CANCEL MODAL ──────────────────────────────────────────────────────── -->
<?php if($canCancel): ?>
<div id="cancelModal">
    <div class="cancel-sheet">
        <div style="font-size:17px;font-weight:800;margin-bottom:4px;color:var(--tr-text)">✕ მგზავრობა გაუქმდეს?</div>
        <div style="font-size:13px;color:var(--tr-muted);margin-bottom:16px">მიზეზი:</div>
        <?php foreach(['Changed my mind','Taking too long','Found another transport','Wrong address entered','Other'] as $reason): ?>
        <button type="button" class="cancel-reason-btn" onclick='pickReason(<?= json_encode($reason) ?>)'><?= e($reason) ?></button>
        <?php endforeach ?>
        <button type="button" class="cancel-keep-btn" onclick="closeCancel()">← Keep Ride</button>
    </div>
</div>
<script>
function showCancel(){document.getElementById('cancelModal').classList.add('open');}
function closeCancel(){document.getElementById('cancelModal').classList.remove('open');}
function pickReason(r){document.getElementById('cancelReason').value=r;document.getElementById('cancelForm').submit();}
document.getElementById('cancelModal').addEventListener('click',function(e){if(e.target===this)closeCancel();});
</script>
<?php endif ?>

<!-- ── STAR RATING JS ─────────────────────────────────────────────────────── -->
<?php if($canRate): ?>
<script>
var curStar=5;
function setStar(v,commit){
    if(commit===undefined||commit){curStar=v;document.getElementById('ratingInput').value=v;}
    document.querySelectorAll('.star-btn').forEach(function(b){b.classList.toggle('on',+b.dataset.v<=v);});
}
setStar(5);
document.querySelectorAll('.star-btn').forEach(function(btn){
    btn.addEventListener('mouseover',function(){setStar(+btn.dataset.v,false);});
    btn.addEventListener('mouseleave',function(){setStar(curStar,false);});
});
</script>
<?php endif ?>

<!-- ── PENDING POLLING ────────────────────────────────────────────────────── -->
<?php if($ride['status']==='pending'): ?>
<script>
(function(){
    var t=<?= !empty($currentOffer)?(int)$currentOffer['expires_in']:0 ?>;
    var el=document.getElementById('offerTimer');
    if(el&&t>0){var iv=setInterval(function(){t--;el.textContent=t>0?t:0;if(t<=0)clearInterval(iv);},1000);}
    (function poll(){setTimeout(function(){fetch('<?= e($base) ?>/api/taxi/ride-status/<?= e($ride['ride_number']) ?>').then(function(r){return r.json();}).then(function(d){if(d.status&&d.status!=='pending')window.location.reload();else poll();}).catch(function(){poll();});},5000);})();
})();
</script>
<?php endif ?>

<!-- ── PENDING MAP (background) ──────────────────────────────────────────── -->
<?php if($ride['status']==='pending'): ?>
<script>
(function(){
    var pm=L.map('trackMap',{zoomControl:false,attributionControl:false,dragging:false,touchZoom:false,scrollWheelZoom:false,doubleClickZoom:false,boxZoom:false,keyboard:false});
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(pm);
    pm.setView([41.6938,44.8015],18);
    var puIcon=L.divIcon({html:'<div style="position:relative;width:56px;height:64px;display:flex;align-items:flex-end;justify-content:center"><div style="position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:50px;height:50px;border-radius:50%;background:rgba(79,70,229,.22);animation:mylocpulse 1.6s ease-out infinite"></div><div style="font-size:46px;line-height:1;filter:drop-shadow(0 3px 6px rgba(0,0,0,.3));position:relative;z-index:1">🧍</div></div>',iconSize:[56,64],iconAnchor:[28,64],className:''});
    var dsIcon=L.divIcon({html:'<div style="background:#ef4444;width:28px;height:28px;border-radius:50%;border:2.5px solid #fff;display:flex;align-items:center;justify-content:center;font-size:13px;box-shadow:0 2px 8px rgba(239,68,68,.4)">🏁</div>',iconAnchor:[14,14],className:''});
    var pc=<?= (!empty($ride['pickup_lat'])&&!empty($ride['pickup_lng']))?json_encode([(float)$ride['pickup_lat'],(float)$ride['pickup_lng']]):'null' ?>;
    var pd=<?= (!empty($ride['dest_lat'])&&!empty($ride['dest_lng']))?json_encode([(float)$ride['dest_lat'],(float)$ride['dest_lng']]):'null' ?>;
    function gc(addr,cb){fetch('https://nominatim.openstreetmap.org/search?format=json&q='+encodeURIComponent(addr)+'&limit=1',{headers:{'Accept-Language':'ka,en'}}).then(function(r){return r.json();}).then(function(d){if(d.length>0)cb([+d[0].lat,+d[0].lon]);}).catch(function(){});}
    function fit(){if(pc&&pd){L.marker(pc,{icon:puIcon}).addTo(pm);L.marker(pd,{icon:dsIcon}).addTo(pm);pm.fitBounds(L.latLngBounds([pc,pd]),{padding:[60,60]});}else if(pc){L.marker(pc,{icon:puIcon}).addTo(pm);pm.setView(pc,18);}}
    if(pc&&pd){fit();}
    else if(pc){L.marker(pc,{icon:puIcon}).addTo(pm);pm.setView(pc,18);gc(<?= json_encode($ride['destination']) ?>,function(d){pd=d;L.marker(pd,{icon:dsIcon}).addTo(pm);pm.fitBounds(L.latLngBounds([pc,pd]),{padding:[60,60]});});}
    else{gc(<?= json_encode($ride['pickup_address']) ?>,function(p){pc=p;L.marker(pc,{icon:puIcon}).addTo(pm);pm.setView(pc,18);gc(<?= json_encode($ride['destination']) ?>,function(d){pd=d;L.marker(pd,{icon:dsIcon}).addTo(pm);pm.fitBounds(L.latLngBounds([pc,pd]),{padding:[60,60]});});});}
})();
</script>
<?php endif ?>

<!-- ── CANCELLED MAP (background, centered on customer) ───────────────────── -->
<?php if($ride['status']==='cancelled'): ?>
<script>
(function(){
    var cm=L.map('trackMap',{zoomControl:false,attributionControl:false,dragging:false,touchZoom:false,scrollWheelZoom:false,doubleClickZoom:false,boxZoom:false,keyboard:false});
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(cm);
    cm.setView([41.6938,44.8015],18);
    var meIcon=L.divIcon({html:'<div style="background:#4f46e5;width:18px;height:18px;border-radius:50%;border:3px solid #fff;box-shadow:0 0 0 4px rgba(79,70,229,.3)"></div>',iconAnchor:[9,9],className:''});
    if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(function(pos){
            cm.setView([pos.coords.latitude,pos.coords.longitude],18,{animate:false});
            L.marker([pos.coords.latitude,pos.coords.longitude],{icon:meIcon}).addTo(cm);
        },function(){},{ enableHighAccuracy:true,timeout:6000 });
    }
})();
</script>
<?php endif ?>

<!-- ── LIVE MAP JS ───────────────────────────────────────────────────────── -->
<?php if($hasMap): ?>
<script>
var trackMap=L.map('trackMap',{zoomControl:true,attributionControl:false});
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(trackMap);
trackMap.setView([41.6938,44.8015],18);

var WAIT_FREE = <?= (float)$taxi->setting('waiting_free_minutes','3') ?>;
var WAIT_RATE = <?= (float)$taxi->setting('waiting_rate_per_min','0.3') ?>;
var SYM_TR    = <?= json_encode($sym) ?>;
var waitClientStart = null, waitClientIv = null;

function startWaitClient(serverSecs){
    if(waitClientIv) clearInterval(waitClientIv);
    var base = Date.now() - serverSecs * 1000;
    waitClientStart = base;
    waitClientIv = setInterval(function(){ tickWaitClient(base); }, 1000);
    tickWaitClient(base);
    var wb = document.getElementById('waitBanner');
    if(wb) wb.style.display = 'block';
}
function stopWaitClient(){
    if(waitClientIv){ clearInterval(waitClientIv); waitClientIv=null; }
    waitClientStart = null;
    var wb = document.getElementById('waitBanner');
    if(wb) wb.style.display = 'none';
}
function tickWaitClient(base){
    var elapsed = Math.floor((Date.now() - base) / 1000);
    var m = Math.floor(elapsed/60), s = elapsed%60;
    var billable = Math.max(0, m - WAIT_FREE);
    var fee = Math.round(billable * WAIT_RATE * 100) / 100;
    var timeStr = String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');
    var el  = document.getElementById('waitBannerTime');
    var ef  = document.getElementById('waitBannerFee');
    var efw = document.getElementById('waitBannerFeeWrap');
    var et  = document.getElementById('waitBannerText');
    var freeRemain = Math.max(0, Math.ceil(WAIT_FREE*60 - elapsed));
    if(el) el.textContent = timeStr;
    if(fee > 0){
        if(ef)  ef.textContent  = '+' + SYM_TR + fee.toFixed(2);
        if(efw) efw.style.display = 'block';
        if(et)  et.textContent  = 'მოლოდინის ტარიფი ემატება';
    } else {
        if(efw) efw.style.display = 'none';
        if(et)  et.textContent  = freeRemain > 0 ? 'კიდევ '+freeRemain+'წმ უფასოა' : 'ელოდება…';
    }
}

var rideNum=<?= json_encode($ride['ride_number']) ?>,apiBase=<?= json_encode($base) ?>;
var rideStatus=<?= json_encode($ride['status']) ?>;
var sp=<?= (!empty($ride['pickup_lat'])&&!empty($ride['pickup_lng']))?json_encode([(float)$ride['pickup_lat'],(float)$ride['pickup_lng']]):'null' ?>;
var sd=<?= (!empty($ride['dest_lat'])&&!empty($ride['dest_lng']))?json_encode([(float)$ride['dest_lat'],(float)$ride['dest_lng']]):'null' ?>;
var pc=sp,dc=sd,dm=null,pm=null,dsm=null,rl=null,totM=null,lastOsrm=0;

var drIcon=L.divIcon({html:'<div style="font-size:44px;line-height:1;filter:drop-shadow(0 3px 7px rgba(0,0,0,.45))">🚕</div>',iconSize:[52,52],iconAnchor:[26,26],className:''});
var puIcon=L.divIcon({html:'<div style="position:relative;width:56px;height:64px;display:flex;align-items:flex-end;justify-content:center"><div style="position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:50px;height:50px;border-radius:50%;background:rgba(79,70,229,.22);animation:mylocpulse 1.6s ease-out infinite"></div><div style="font-size:46px;line-height:1;filter:drop-shadow(0 3px 6px rgba(0,0,0,.3));position:relative;z-index:1">🧍</div></div>',iconSize:[56,64],iconAnchor:[28,64],className:''});
var dsIcon=L.divIcon({html:'<div style="background:#ef4444;width:28px;height:28px;border-radius:50%;border:2.5px solid #fff;display:flex;align-items:center;justify-content:center;font-size:13px;box-shadow:0 2px 8px rgba(239,68,68,.4)">🏁</div>',iconAnchor:[14,14],className:''});

function fmtKm(m){return (m/1000).toFixed(1)+' km';}
function fmtMin(s){var m=Math.ceil(s/60);return m<60?m+' წთ':Math.floor(m/60)+'სთ '+(m%60)+'წთ';}

function geocode(addr,cb){
    fetch('https://nominatim.openstreetmap.org/search?format=json&q='+encodeURIComponent(addr)+'&limit=1',{headers:{'Accept-Language':'ka,en'}})
        .then(function(r){return r.json();}).then(function(d){if(d.length>0)cb(+d[0].lat,+d[0].lon);}).catch(function(){});
}
function ensurePickup(cb){
    if(pc){if(!pm)pm=L.marker(pc,{icon:puIcon}).addTo(trackMap);cb();return;}
    geocode(<?= json_encode($ride['pickup_address']) ?>,function(lat,lng){pc=[lat,lng];pm=L.marker(pc,{icon:puIcon}).addTo(trackMap);cb();});
}
function ensureDest(cb){
    if(dc){if(!dsm)dsm=L.marker(dc,{icon:dsIcon}).addTo(trackMap);cb();return;}
    geocode(<?= json_encode($ride['destination']) ?>,function(lat,lng){dc=[lat,lng];dsm=L.marker(dc,{icon:dsIcon}).addTo(trackMap);cb();});
}
function drawRoute(from,to,color,onDone){
    var now=Date.now();if(now-lastOsrm<10000&&rl&&!onDone)return;
    if(onDone||(now-lastOsrm>=10000))lastOsrm=now;
    fetch('https://router.project-osrm.org/route/v1/driving/'+from[1]+','+from[0]+';'+to[1]+','+to[0]+'?overview=full&geometries=geojson')
        .then(function(r){return r.json();}).then(function(data){
            if(rl)trackMap.removeLayer(rl);
            if(data.routes&&data.routes[0]){
                rl=L.geoJSON(data.routes[0].geometry,{style:{color:color,weight:5,opacity:.9}}).addTo(trackMap);
                if(onDone)onDone(data.routes[0].distance,data.routes[0].duration);
            }
        }).catch(function(){if(rl)trackMap.removeLayer(rl);rl=L.polyline([from,to],{color:color,weight:3,dashArray:'8,5'}).addTo(trackMap);if(onDone)onDone(null,null);});
}
function osrmLeft(from,to,cb){
    var now=Date.now();if(now-lastOsrm<10000)return;lastOsrm=now;
    fetch('https://router.project-osrm.org/route/v1/driving/'+from[1]+','+from[0]+';'+to[1]+','+to[0]+'?overview=false')
        .then(function(r){return r.json();}).then(function(d){if(d.routes&&d.routes[0])cb(d.routes[0].distance,d.routes[0].duration);}).catch(function(){});
}

if(rideStatus==='in_progress'){
    ensurePickup(function(){ensureDest(function(){
        drawRoute(pc,dc,'#4f46e5',function(m,s){
            if(m){totM=m;document.getElementById('statTotal').textContent=fmtKm(m);}
            trackMap.fitBounds(L.latLngBounds([pc,dc]),{padding:[60,100]});
        });
    });});
} else {
    ensureDest(function(){});
}

function animateMarker(marker,toLat,toLng){
    var from=marker.getLatLng(),steps=20,step=0;
    var dlat=(toLat-from.lat)/steps,dlng=(toLng-from.lng)/steps;
    var iv=setInterval(function(){step++;marker.setLatLng([from.lat+dlat*step,from.lng+dlng*step]);if(step>=steps)clearInterval(iv);},50);
}
function fetchDriverLoc(){
    fetch(apiBase+'/api/taxi/driver-location/'+rideNum)
        .then(function(r){return r.json();}).then(function(d){
            var loc=d.location;
            if(loc&&loc.lat){
                if(dm)animateMarker(dm,loc.lat,loc.lng);
                else dm=L.marker([loc.lat,loc.lng],{icon:drIcon}).addTo(trackMap);
                var age=document.getElementById('locAge');
                if(age)age.textContent=loc.fresh?'● Live':'Last known';
                if(rideStatus==='driver_assigned'){
                    ensurePickup(function(){
                        drawRoute([loc.lat,loc.lng],pc,'#8b5cf6',function(m,s){
                            if(m){document.getElementById('statDist').textContent=fmtKm(m);document.getElementById('statEta').textContent=fmtMin(s);}
                        });
                        trackMap.fitBounds(L.latLngBounds([[loc.lat,loc.lng],pc]),{padding:[60,100]});
                    });
                } else if(rideStatus==='in_progress'){
                    ensureDest(function(){
                        osrmLeft([loc.lat,loc.lng],dc,function(m,s){
                            document.getElementById('statDist').textContent=fmtKm(m);
                            document.getElementById('statEta').textContent=fmtMin(s);
                            if(totM)document.getElementById('statTotal').textContent=fmtKm(totM);
                        });
                    });
                }
            }
            if(d.status&&d.status!==rideStatus){rideStatus=d.status;window.location.reload();}
            // Waiting banner
            if(d.waiting_seconds !== null && d.waiting_seconds !== undefined){
                if(!waitClientStart) startWaitClient(d.waiting_seconds);
            } else if(d.waiting_seconds === null) {
                stopWaitClient();
            }
        }).catch(function(){});
}
fetchDriverLoc();
setInterval(fetchDriverLoc,8000);

// ── Fast waiting poll (every 3s, independent of GPS) ─────────────────────────
(function pollWaiting(){
    setTimeout(function(){
        fetch(apiBase+'/api/taxi/ride-status/'+rideNum)
            .then(function(r){return r.json();})
            .then(function(d){
                if(d.waiting_seconds !== null && d.waiting_seconds !== undefined){
                    if(!waitClientStart) startWaitClient(d.waiting_seconds);
                } else if(d.waiting_seconds === null){
                    stopWaitClient();
                }
            })
            .catch(function(){})
            .finally(function(){ pollWaiting(); });
    }, 3000);
})();

<?php
// If waiting is already active when page loads — restore immediately
$waitingNowSecs = null;
if (!empty($ride['waiting_started_at'])) {
    $waitingNowSecs = max(0, time() - strtotime((string)$ride['waiting_started_at']));
}
?>
<?php if($waitingNowSecs !== null): ?>
startWaitClient(<?= $waitingNowSecs ?>);
<?php endif ?>
</script>
<?php endif ?>
