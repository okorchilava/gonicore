<?php
$doneRideId = (int)($_GET['done'] ?? 0);
$done       = $doneRideId > 0;
$state      = 'waiting';
if ($activeRide) {
    $state = $activeRide['status'] === 'in_progress' ? 'ride_in_progress' : 'ride_assigned';
} elseif ($offer && !$offer['expired']) {
    $state = 'offer_pending';
}
$driverId       = (int)$driver['id'];
$balance        = (float)($driver['balance'] ?? 0);
$isOnline       = !empty($driver['is_online']);
$avgRating      = (float)($driver['avg_rating'] ?? 0);
$totalTrips     = (int)($driver['total_trips'] ?? 0);
$recentRides    = $taxi->getDriverRides($driverId, 10);
$sym            = $taxi->setting('currency_symbol','₾');
$commPct        = $taxi->commissionPct();
$unreadCount    = $taxi->countUnreadNotifications($driverId);
$lastRide       = $done ? $taxi->getRide($doneRideId) : null;
$carColor       = $driver['car_color'] ?? '';
$earnings       = $taxi->getDriverEarningsBreakdown($driverId);
$acceptRate     = (float)($driver['acceptance_rate'] ?? 0);
$cancelRate     = (float)($driver['cancellation_rate'] ?? 0);

// Offer data for JS (when page loads with active offer)
$offerData = null;
if ($state === 'offer_pending' && $offer && $offer['ride']) {
    $r = $offer['ride'];
    $offerData = [
        'ride_id'    => $offer['ride_id'],
        'expires_in' => max(1, (int)$offer['expires_in']),
        'pickup'     => $r['pickup_address'] ?? '',
        'destination'=> $r['destination'] ?? '',
        'car_type'   => $taxi->carTypes()[$r['car_type'] ?? ''] ?? ($r['car_type'] ?? ''),
        'passengers' => (int)($r['passengers'] ?? 1),
        'price'      => (float)($r['estimated_price'] ?? 0),
        'note'       => $r['customer_note'] ?? '',
    ];
}

function dp_stars(float $r): string {
    $s = '';
    $full = (int)round($r);
    for ($i = 1; $i <= 5; $i++) $s .= $i <= $full ? '★' : '☆';
    return $s;
}
?>
<!DOCTYPE html>
<html lang="ka">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($driver['name']) ?> · Driver Portal</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0b0f1a;--surface:#111827;--s2:#1a2235;--s3:#1e2d42;
  --border:#1f2d45;--border2:#263347;
  --text:#f1f5f9;--muted:#64748b;
  --accent:#6366f1;--green:#22c55e;--amber:#f59e0b;--red:#ef4444;--blue:#3b82f6;
  --sidebar:280px;--panel:340px;
}
html,body{height:100%;font-family:'Inter',system-ui,-apple-system,sans-serif;background:var(--bg);color:var(--text);overflow:hidden}

/* ── TOPBAR ── */
.topbar{height:56px;background:var(--surface);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 20px;gap:14px;flex-shrink:0;z-index:200}
.tb-logo{font-size:18px;font-weight:900;display:flex;align-items:center;gap:8px}
.tb-sep{width:1px;height:26px;background:var(--border)}
.tb-status{display:flex;align-items:center;gap:8px}
.live-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.live-dot.on{background:var(--green);animation:lpulse 2s infinite}
.live-dot.off{background:var(--muted)}
@keyframes lpulse{0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,.4)}60%{box-shadow:0 0 0 6px rgba(34,197,94,0)}}
.toggle{position:relative;width:44px;height:24px;cursor:pointer;flex-shrink:0}
.toggle input{display:none}
.toggle-track{position:absolute;inset:0;border-radius:24px;background:var(--s3);transition:background .2s}
.toggle-thumb{position:absolute;width:18px;height:18px;border-radius:50%;background:#fff;top:3px;left:3px;transition:transform .2s;box-shadow:0 1px 4px rgba(0,0,0,.5)}
.toggle input:checked~.toggle-track{background:var(--green)}
.toggle input:checked~.toggle-thumb{transform:translateX(20px)}
.tb-right{margin-left:auto;display:flex;align-items:center;gap:12px}
.tb-bal-num{font-size:17px;font-weight:800;color:var(--green)}
.tb-bal-lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px}
.tb-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
.tb-name{font-size:13px;font-weight:700}
.tb-sub{font-size:11px;color:var(--muted);margin-top:1px}
.logout-btn{font-size:12px;color:var(--muted);background:none;border:1px solid var(--border);border-radius:8px;padding:5px 11px;cursor:pointer;font-family:inherit;transition:all .15s;white-space:nowrap}
.logout-btn:hover{color:var(--red);border-color:var(--red)}

/* ── NOTIFICATIONS ── */
.notif-btn{position:relative;width:36px;height:36px;border-radius:50%;background:var(--s2);border:1px solid var(--border);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;transition:background .15s}
.notif-btn:hover{background:var(--s3)}
.notif-badge{position:absolute;top:-3px;right:-3px;min-width:17px;height:17px;border-radius:999px;background:var(--red);color:#fff;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;padding:0 4px;border:2px solid var(--surface);display:none}
.notif-badge.show{display:flex}
.notif-drawer{position:fixed;top:0;right:-360px;width:360px;height:100vh;background:var(--surface);border-left:1px solid var(--border);z-index:9000;display:flex;flex-direction:column;transition:right .25s cubic-bezier(.4,0,.2,1);box-shadow:-8px 0 32px rgba(0,0,0,.4)}
.notif-drawer.open{right:0}
.notif-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:8999;display:none;backdrop-filter:blur(2px)}
.notif-overlay.show{display:block}
.notif-head{padding:16px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.notif-head-title{font-size:14px;font-weight:800;display:flex;align-items:center;gap:8px}
.notif-close{background:none;border:none;color:var(--muted);font-size:20px;cursor:pointer;line-height:1;padding:2px 6px;border-radius:6px}
.notif-close:hover{color:var(--text);background:var(--s2)}
.notif-list{flex:1;overflow-y:auto;padding:8px 0}
.notif-list::-webkit-scrollbar{width:4px}
.notif-list::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}
.notif-item{padding:14px 18px;border-bottom:1px solid var(--border2);cursor:default;transition:background .1s}
.notif-item.unread{background:rgba(99,102,241,.06);border-left:3px solid var(--accent)}
.notif-item:hover{background:var(--s2)}
.notif-item-title{font-size:13px;font-weight:700;margin-bottom:5px;display:flex;align-items:center;gap:6px}
.notif-item-body{font-size:12px;color:var(--muted);line-height:1.6}
.notif-item-time{font-size:11px;color:var(--muted);margin-top:5px}
.notif-empty{padding:40px 20px;text-align:center;color:var(--muted);font-size:13px}
.notif-finance-row{display:flex;justify-content:space-between;align-items:center;padding:4px 0;font-size:12px}
.notif-finance-row .lbl{color:var(--muted)}
@media(max-width:900px){.notif-drawer{width:100%;right:-100%}}
.color-dot{width:9px;height:9px;border-radius:50%;border:1px solid rgba(255,255,255,.2);flex-shrink:0;display:inline-block}

/* ── LAYOUT ── */
.layout{display:flex;height:calc(100vh - 56px)}
.sidebar{width:var(--sidebar);background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;overflow-y:auto;overflow-x:hidden;flex-shrink:0}
.sidebar::-webkit-scrollbar{width:4px}
.sidebar::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}
.map-col{flex:1;position:relative;background:#0b1929;min-width:0;overflow:hidden}
#driverMap{width:100%;height:100%}
.right-panel{width:var(--panel);background:var(--surface);border-left:1px solid var(--border);display:flex;flex-direction:column;overflow-y:auto;flex-shrink:0}
.right-panel::-webkit-scrollbar{width:4px}
.right-panel::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}

/* ── SIDEBAR ── */
.sb-profile{padding:18px;border-bottom:1px solid var(--border)}
.sb-av{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:10px}
.sb-name{font-size:14px;font-weight:800;margin-bottom:3px}
.sb-car{font-size:12px;color:var(--muted);display:flex;align-items:center;gap:5px;flex-wrap:wrap}
.sb-rating{display:flex;align-items:center;gap:6px;margin-top:7px}
.stars{color:var(--amber);font-size:12px;letter-spacing:1px}
.sb-stats{display:grid;grid-template-columns:1fr 1fr;gap:1px;background:var(--border);border-bottom:1px solid var(--border)}
.sb-stat{background:var(--surface);padding:13px 14px;text-align:center}
.sb-stat-val{font-size:18px;font-weight:800;margin-bottom:2px}
.sb-stat-lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px}
.sb-section{padding:13px 16px;border-bottom:1px solid var(--border)}
.sb-section-title{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.6px;margin-bottom:9px}
.earn-row{display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid rgba(31,45,69,.4)}
.earn-row:last-child{border-bottom:none}
.earn-dot{width:6px;height:6px;border-radius:50%;background:var(--green);flex-shrink:0}
.earn-info{flex:1;min-width:0}
.earn-from{font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.earn-date{font-size:10.5px;color:var(--muted)}
.earn-amt{font-size:13px;font-weight:700;color:var(--green);flex-shrink:0}

/* ── MAP OVERLAYS ── */
/* Isolate Leaflet's internal z-index stack (200-800) inside the map div */
#driverMap{position:relative;z-index:0}
.map-badge{position:absolute;z-index:900;pointer-events:none}
.dist-bar{top:16px;left:16px;right:16px;background:rgba(11,15,26,.9);backdrop-filter:blur(8px);border:1px solid var(--border2);border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:12px}
.dist-icon{font-size:22px}
.dist-title{font-size:13px;font-weight:700}
.dist-val{font-size:15px;font-weight:800;color:var(--green);margin-top:2px}
.stats-bar{top:16px;left:16px;right:16px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px}
.sb-box{background:rgba(11,15,26,.9);backdrop-filter:blur(8px);border:1px solid var(--border2);border-radius:12px;padding:10px 14px;text-align:center}
.sb-val{font-size:15px;font-weight:800;margin-bottom:2px}
.sb-lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px}
.waiting-pill-wrap{position:absolute;bottom:32px;left:50%;transform:translateX(-50%);z-index:900;pointer-events:none;white-space:nowrap}
.waiting-pill{background:rgba(11,15,26,.92);backdrop-filter:blur(8px);border:1px solid var(--border2);border-radius:999px;padding:12px 28px;text-align:center}
.waiting-icon{font-size:20px;animation:breathe 3s ease-in-out infinite;display:inline-block}
@keyframes breathe{0%,100%{transform:scale(1)}50%{transform:scale(1.1)}}
.waiting-txt{font-size:14px;font-weight:700;margin-top:2px}
.waiting-sub{font-size:11px;color:var(--muted);margin-top:2px}

/* ── MAP ACTION BAR ── */
.map-action-bar{position:absolute;bottom:24px;left:16px;right:16px;z-index:900;display:flex;gap:10px;pointer-events:all}
.map-act-btn{flex:1;padding:12px 14px;border:none;border-radius:14px;font-size:13.5px;font-weight:800;cursor:pointer;font-family:inherit;text-align:center;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 6px 20px rgba(0,0,0,.35);transition:opacity .15s,transform .1s;line-height:1}
.map-act-btn:active{opacity:.85;transform:scale(.97)}
.map-act-nav{background:rgba(59,130,246,.92);color:#fff;backdrop-filter:blur(6px);flex:0 0 auto;min-width:52px;max-width:70px;font-size:20px;padding:12px 10px}
.map-act-nav:hover{background:rgba(37,99,235,.95)}
.map-act-wait{background:rgba(15,23,42,.82);color:#f1f5f9;backdrop-filter:blur(6px);font-size:12.5px}
.map-act-wait:hover{background:rgba(15,23,42,.95)}
.map-act-wait.active{background:rgba(245,158,11,.92)!important;color:#fff!important;animation:waitpulse 2s ease-in-out infinite}
@keyframes waitpulse{0%,100%{box-shadow:0 6px 20px rgba(0,0,0,.35)}50%{box-shadow:0 6px 20px rgba(245,158,11,.5),0 0 16px rgba(245,158,11,.3)}}
.map-act-board{background:rgba(34,197,94,.92);color:#fff;backdrop-filter:blur(6px)}
.map-act-board:hover{background:rgba(22,163,74,.95)}

/* ── OFFER CARD ── */
#offerModal{position:absolute;bottom:20px;left:50%;transform:translateX(-50%);z-index:1000;display:none;width:calc(100% - 24px);max-width:500px;pointer-events:all}
.offer-card{background:var(--surface);border:2px solid var(--amber);border-radius:22px;overflow:hidden;animation:offerIn .3s cubic-bezier(.2,1,.3,1);box-shadow:0 12px 40px rgba(0,0,0,.6),0 0 0 1px rgba(245,158,11,.3)}
@keyframes offerIn{from{opacity:0;transform:translateY(20px) scale(.96)}to{opacity:1;transform:none}}
.offer-head{background:linear-gradient(135deg,rgba(245,158,11,.15),rgba(245,158,11,.05));padding:12px 16px;border-bottom:1px solid rgba(245,158,11,.2);display:flex;justify-content:space-between;align-items:center}
.offer-title{font-size:14px;font-weight:900;color:var(--amber);letter-spacing:.2px}
.offer-sub{font-size:11px;color:var(--muted);margin-top:2px}
.timer-ring{position:relative;width:50px;height:50px;flex-shrink:0}
.timer-ring svg{transform:rotate(-90deg);display:block}
.timer-num{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:900;font-family:monospace;color:var(--amber)}
.offer-main{display:grid;grid-template-columns:auto 1fr;border-bottom:1px solid var(--border)}
.offer-price-wrap{padding:14px 16px;border-right:1px solid var(--border);display:flex;flex-direction:column;justify-content:center;min-width:110px}
.price-big{font-size:28px;font-weight:900;color:var(--green);line-height:1}
.price-earn{font-size:11px;color:var(--muted);margin-top:4px}
.price-earn strong{color:var(--green)}
.offer-locs{padding:10px 14px;display:flex;flex-direction:column;gap:8px}
.offer-loc{display:flex;align-items:flex-start;gap:8px;font-size:13px}
.offer-loc .lbl{color:var(--muted);flex-shrink:0;font-size:14px;margin-top:1px}
.offer-loc .val{font-weight:600;word-break:break-word;line-height:1.4;color:#e2e8f0}
.offer-actions{display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:12px 14px;background:rgba(0,0,0,.2)}

/* ── DONE OVERLAY ── */
.done-overlay{position:absolute;inset:0;z-index:1100;background:rgba(11,15,26,.96);display:flex;align-items:center;justify-content:center;padding:24px}
.done-card{background:var(--surface);border:1px solid var(--green);border-radius:20px;width:100%;max-width:400px;overflow:hidden;text-align:center}
.done-top{padding:28px 24px 20px}
.done-icon{font-size:52px;margin-bottom:12px}
.done-title{font-size:22px;font-weight:800;margin-bottom:4px}
.done-sub{font-size:13px;color:var(--muted)}
.done-earn{font-size:48px;font-weight:900;color:var(--green);padding:14px 0}
.done-grid{display:grid;grid-template-columns:1fr 1fr 1fr;border-top:1px solid var(--border)}
.done-cell{padding:13px 8px;text-align:center;border-right:1px solid var(--border)}
.done-cell:last-child{border-right:none}
.done-cell-lbl{font-size:10px;color:var(--muted);margin-bottom:4px}
.done-cell-val{font-size:14px;font-weight:700}

/* ── RIGHT PANEL ── */
.rp-head{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center}
.rp-title{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--muted)}
.rp-row{display:flex;justify-content:space-between;align-items:flex-start;padding:10px 16px;border-bottom:1px solid var(--border2);font-size:13px;gap:10px}
.rp-row .lbl{color:var(--muted);flex-shrink:0;font-size:12px}
.rp-row .val{font-weight:600;text-align:right;word-break:break-word}
.rp-actions{padding:14px;display:flex;flex-direction:column;gap:8px;margin-top:auto}
.rp-btn{display:block;width:100%;padding:11px;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;text-align:center;text-decoration:none;transition:opacity .15s,transform .1s}
.rp-btn:active{transform:scale(.98)}
.btn-green{background:var(--green);color:#fff}
.btn-blue{background:var(--blue);color:#fff}
.btn-outline{background:transparent;border:1.5px solid var(--border);color:var(--muted)}

/* ── RESPONSIVE ── */
@media(max-width:900px){
  html,body{overflow:auto;height:auto}

  /* Topbar — compact, single row */
  .topbar{height:52px;padding:0 12px;flex-wrap:nowrap;gap:8px;overflow:hidden}
  .tb-sep{display:none}
  .tb-sub{display:none}
  .tb-bal-lbl{display:none}
  .tb-bal-num{font-size:14px}
  .tb-name{display:none}
  .tb-avatar{width:28px;height:28px;font-size:12px;flex-shrink:0}
  .tb-logo{font-size:14px;gap:5px}
  .logout-btn{font-size:11px;padding:4px 8px;white-space:nowrap}
  .tb-right{gap:8px}

  /* Layout — map first, ride panel second, sidebar last */
  .layout{flex-direction:column;height:auto}
  .map-col{height:65vh;min-height:340px;max-height:520px;order:1;flex-shrink:0}
  #driverMap{height:65vh!important;min-height:340px!important}
  .right-panel{width:100%;border-left:none;border-top:1px solid var(--border);order:2}
  .sidebar{width:100%;border-right:none;border-top:1px solid var(--border);order:3}

  /* Sidebar — compact on mobile */
  .sb-profile{padding:14px}
  .sb-stats{grid-template-columns:1fr 1fr}
  .sb-stat{padding:10px}
  .sb-stat-val{font-size:15px}

  /* Waiting pill */
  .waiting-pill-wrap{bottom:16px;left:12px;right:12px;width:auto;transform:none}
  .waiting-pill{border-radius:16px;padding:10px 18px}
  .waiting-txt{font-size:13px}

  /* Offer card — bottom sheet style */
  #offerModal{bottom:0;left:0;right:0;width:100%;transform:none;padding:0;max-width:100%}
  .offer-card{border-radius:20px 20px 0 0;border-left:none;border-right:none;border-bottom:none}

  /* Right panel actions — sticky */
  .rp-actions{position:sticky;bottom:0;background:var(--surface);border-top:1px solid var(--border);padding:12px;margin-top:0}
  .rp-btn{padding:13px;font-size:14px}
  .rp-row{padding:9px 14px;font-size:12.5px}

  /* Stats bars on map */
  .dist-bar{padding:10px 14px}
  .stats-bar{grid-template-columns:1fr 1fr 1fr;gap:6px}
  .sb-box{padding:8px 10px}
  .sb-val{font-size:13px}
}
@keyframes shake{0%,100%{transform:rotate(0)}25%{transform:rotate(-8deg)}75%{transform:rotate(8deg)}}
@keyframes fadeInDown{from{opacity:0;transform:translateX(-50%) translateY(-10px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}
@keyframes mylocpulse{0%{transform:translateX(-50%) scale(1);opacity:.7}100%{transform:translateX(-50%) scale(2.6);opacity:0}}
</style>
</head>
<body>

<!-- ════ TOPBAR ════════════════════════════════════════════════════════════ -->
<div class="topbar">
    <div class="tb-logo">🚕 Driver Portal</div>
    <div class="tb-sep"></div>
    <div class="tb-avatar">🧑‍✈️</div>
    <div>
        <div class="tb-name"><?= e($driver['name']) ?></div>
        <div class="tb-sub">
            <?php if($carColor): ?><span class="color-dot" style="background:<?= e($carColor) ?>;vertical-align:middle;margin-right:3px"></span><?php endif ?>
            <?= e($taxi->carTypes()[$driver['car_type']] ?? '') ?>
            <?= $driver['car_model'] ? ' · '.e($driver['car_model']) : '' ?>
            <?= $driver['car_number'] ? ' · '.e($driver['car_number']) : '' ?>
        </div>
    </div>
    <div class="tb-sep"></div>
    <div class="tb-status">
        <span class="live-dot <?= $isOnline?'on':'off' ?>" id="liveDot"></span>
        <span id="statusTxt" style="font-size:13px;font-weight:600;color:<?= $isOnline?'var(--green)':'var(--muted)' ?>"><?= $isOnline?'Online':'Offline' ?></span>
    </div>
    <label class="toggle" title="Toggle online">
        <input type="checkbox" id="onlineToggle" <?= $isOnline?'checked':'' ?>>
        <div class="toggle-track"></div>
        <div class="toggle-thumb"></div>
    </label>
    <div class="tb-right">
        <div>
            <div class="tb-bal-num"><?= number_format($balance,2).$sym ?></div>
            <div class="tb-bal-lbl">Balance · <?= $totalTrips ?> trips</div>
        </div>
        <button class="notif-btn" id="notifBtn" onclick="openNotifDrawer()" title="შეტყობინებები">
            🔔
            <span class="notif-badge <?= $unreadCount > 0 ? 'show' : '' ?>" id="notifBadge"><?= $unreadCount > 0 ? $unreadCount : '' ?></span>
        </button>
        <a href="<?= e($base) ?>/taxi/driver/logout"><button class="logout-btn">Logout ↗</button></a>
    </div>
</div>

<!-- ════ LAYOUT ════════════════════════════════════════════════════════════ -->
<div class="layout">

<!-- ── SIDEBAR ──────────────────────────────────────────────────────────── -->
<aside class="sidebar">
    <div class="sb-profile">
        <div class="sb-av">🧑‍✈️</div>
        <div class="sb-name"><?= e($driver['name']) ?></div>
        <div class="sb-car">
            <?php if($carColor): ?><span class="color-dot" style="background:<?= e($carColor) ?>"></span><?php endif ?>
            <?= e($taxi->carTypes()[$driver['car_type']] ?? $driver['car_type']) ?>
            <?= $driver['car_model'] ? ' · '.e($driver['car_model']) : '' ?>
        </div>
        <?php if($avgRating > 0): ?>
        <div class="sb-rating">
            <span class="stars"><?= dp_stars($avgRating) ?></span>
            <span style="font-size:13px;font-weight:700"><?= number_format($avgRating,1) ?></span>
            <span style="font-size:11px;color:var(--muted)">(<?= $totalTrips ?> rides)</span>
        </div>
        <?php endif ?>
    </div>

    <div class="sb-stats">
        <div class="sb-stat">
            <div class="sb-stat-val" style="color:var(--green)"><?= number_format($balance,2).$sym ?></div>
            <div class="sb-stat-lbl">Balance</div>
        </div>
        <div class="sb-stat">
            <div class="sb-stat-val" style="color:var(--accent)"><?= $totalTrips ?></div>
            <div class="sb-stat-lbl">Total Trips</div>
        </div>
    </div>

    <!-- Earnings Dashboard -->
    <div class="sb-section">
        <div class="sb-section-title">💰 შემოსავლები</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
            <div style="background:var(--s2);border-radius:10px;padding:10px;text-align:center">
                <div style="font-size:17px;font-weight:900;color:var(--green)"><?= number_format($earnings['today'],2).$sym ?></div>
                <div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-top:2px">დღეს · <?= $earnings['today_rides'] ?> მგზ.</div>
            </div>
            <div style="background:var(--s2);border-radius:10px;padding:10px;text-align:center">
                <div style="font-size:17px;font-weight:900;color:var(--blue)"><?= number_format($earnings['week'],2).$sym ?></div>
                <div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-top:2px">კვირა · <?= $earnings['week_rides'] ?> მგზ.</div>
            </div>
            <div style="background:var(--s2);border-radius:10px;padding:10px;text-align:center">
                <div style="font-size:17px;font-weight:900;color:var(--amber)"><?= number_format($earnings['month'],2).$sym ?></div>
                <div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-top:2px">თვე · <?= $earnings['month_rides'] ?> მგზ.</div>
            </div>
            <div style="background:var(--s2);border-radius:10px;padding:10px;text-align:center">
                <div style="font-size:17px;font-weight:900;color:var(--text)"><?= number_format($earnings['total'],2).$sym ?></div>
                <div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-top:2px">სულ · <?= $totalTrips ?> მგზ.</div>
            </div>
        </div>
    </div>

    <!-- Driver Stats -->
    <div class="sb-section">
        <div class="sb-section-title">📊 სტატისტიკა</div>
        <div style="display:flex;flex-direction:column;gap:8px">
            <?php
            $arColor = $acceptRate >= 80 ? 'var(--green)' : ($acceptRate >= 60 ? 'var(--amber)' : 'var(--red)');
            $crColor = $cancelRate <= 5 ? 'var(--green)' : ($cancelRate <= 15 ? 'var(--amber)' : 'var(--red)');
            ?>
            <div style="background:var(--s2);border-radius:10px;padding:10px 12px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <span style="font-size:12px;color:var(--muted)">Acceptance Rate</span>
                    <span style="font-size:14px;font-weight:800;color:<?= $arColor ?>"><?= number_format($acceptRate,1) ?>%</span>
                </div>
                <div style="height:4px;background:var(--border);border-radius:999px">
                    <div style="height:4px;background:<?= $arColor ?>;border-radius:999px;width:<?= min(100,$acceptRate) ?>%;transition:width .3s"></div>
                </div>
            </div>
            <div style="background:var(--s2);border-radius:10px;padding:10px 12px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <span style="font-size:12px;color:var(--muted)">Cancellation Rate</span>
                    <span style="font-size:14px;font-weight:800;color:<?= $crColor ?>"><?= number_format($cancelRate,1) ?>%</span>
                </div>
                <div style="height:4px;background:var(--border);border-radius:999px">
                    <div style="height:4px;background:<?= $crColor ?>;border-radius:999px;width:<?= min(100,$cancelRate) ?>%;transition:width .3s"></div>
                </div>
            </div>
            <?php if($avgRating > 0): ?>
            <div style="background:var(--s2);border-radius:10px;padding:10px 12px;display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:12px;color:var(--muted)">Rating</span>
                <span style="font-size:14px;font-weight:800;color:var(--amber)">★ <?= number_format($avgRating,2) ?></span>
            </div>
            <?php endif ?>
        </div>
    </div>

    <?php if(!empty($recentRides)): ?>
    <div class="sb-section">
        <div class="sb-section-title">Recent Earnings</div>
        <?php foreach(array_slice($recentRides,0,8) as $rr):
            $rFare   = (float)($rr['actual_price'] ?? $rr['estimated_price'] ?? 0);
            $rEarned = $rr['driver_earnings'] !== null ? (float)$rr['driver_earnings'] : $taxi->calcDriverEarnings($rFare);
        ?>
        <div class="earn-row">
            <span class="earn-dot"></span>
            <div class="earn-info">
                <div class="earn-from"><?= e(mb_substr($rr['pickup_address'],0,26)) ?></div>
                <div class="earn-date">→ <?= e(mb_substr($rr['destination'],0,20)) ?> · <?= date('d M',strtotime($rr['updated_at'])) ?></div>
            </div>
            <div class="earn-amt"><?= number_format($rEarned,2).$sym ?></div>
        </div>
        <?php endforeach ?>
    </div>
    <?php else: ?>
    <div style="padding:20px;text-align:center;color:var(--muted);font-size:13px">No completed rides yet</div>
    <?php endif ?>
</aside>

<!-- ── MAP ──────────────────────────────────────────────────────────────── -->
<div class="map-col">
    <div id="driverMap"></div>

    <!-- Offer card -->
    <div id="offerModal">
        <div class="offer-card">
            <div class="offer-head">
                <div>
                    <div class="offer-title">🚕 ახალი მგზავრობის მოთხოვნა</div>
                    <div class="offer-sub">გასვლამდე უპასუხე</div>
                </div>
                <div class="timer-ring">
                    <svg width="50" height="50" viewBox="0 0 60 60">
                        <circle cx="30" cy="30" r="25" fill="none" stroke="var(--s3)" stroke-width="5"/>
                        <circle cx="30" cy="30" r="25" fill="none" stroke="var(--amber)" stroke-width="5"
                            stroke-dasharray="157.1" stroke-dashoffset="0" stroke-linecap="round" id="timerArc"/>
                    </svg>
                    <div class="timer-num" id="timerNum">30s</div>
                </div>
            </div>
            <div class="offer-main">
                <div class="offer-price-wrap">
                    <div class="price-big" id="offerPrice">—</div>
                    <div class="price-earn">თქვენი: <strong id="offerEarn">—</strong></div>
                </div>
                <div class="offer-locs">
                    <div class="offer-loc"><span class="lbl">📍</span><span class="val" id="offerPickup">—</span></div>
                    <div class="offer-loc"><span class="lbl">🏁</span><span class="val" id="offerDest">—</span></div>
                    <div class="offer-loc" id="offerCarRow"><span class="lbl">🚗</span><span class="val" id="offerCar">—</span></div>
                    <div class="offer-loc" id="offerNoteRow" style="display:none"><span class="lbl">📝</span><span class="val" id="offerNote" style="color:var(--amber);font-style:italic">—</span></div>
                </div>
            </div>
            <div class="offer-actions">
                <form method="POST" id="acceptForm">
                    <button type="submit" class="rp-btn btn-green" style="width:100%;font-size:15px;padding:13px">✅ მიღება</button>
                </form>
                <form method="POST" id="declineForm">
                    <button type="submit" class="rp-btn btn-outline" style="width:100%;font-size:15px;padding:13px">✕ უარი</button>
                </form>
            </div>
        </div>
    </div>

    <?php if($done && $lastRide):
        $fare       = (float)($lastRide['actual_price'] ?? $lastRide['estimated_price'] ?? 0);
        $earned     = $lastRide['driver_earnings'] !== null ? (float)$lastRide['driver_earnings'] : $taxi->calcDriverEarnings($fare);
        $plat       = round($fare - $earned, 2);
        $waitFee    = isset($lastRide['waiting_fee']) && $lastRide['waiting_fee'] !== null ? (float)$lastRide['waiting_fee'] : 0.0;
        $baseFare   = round($fare - $waitFee, 2);
    ?>
    <!-- DONE overlay -->
    <div class="done-overlay">
        <div class="done-card">
            <div class="done-top">
                <div class="done-icon">🏁</div>
                <div class="done-title">Ride Complete!</div>
                <div class="done-sub">
                    <?php if($waitFee > 0): ?>
                    <?= number_format($baseFare,2).$sym ?> + <span style="color:var(--amber)">⏱<?= number_format($waitFee,2).$sym ?></span> მოლოდინი
                    <?php else: ?>
                    Great job. Waiting for next ride…
                    <?php endif ?>
                </div>
                <div class="done-earn"><?= number_format($earned,2).$sym ?></div>
            </div>
            <div class="done-grid">
                <div class="done-cell">
                    <div class="done-cell-lbl">Total Fare</div>
                    <div class="done-cell-val"><?= number_format($fare,2).$sym ?></div>
                </div>
                <div class="done-cell">
                    <div class="done-cell-lbl">Platform (<?= $commPct ?>%)</div>
                    <div class="done-cell-val" style="color:var(--amber)"><?= number_format($plat,2).$sym ?></div>
                </div>
                <div class="done-cell">
                    <div class="done-cell-lbl">Balance</div>
                    <div class="done-cell-val" style="color:var(--green)"><?= number_format($balance,2).$sym ?></div>
                </div>
            </div>
        </div>
    </div>
    <script>setTimeout(function(){window.location.href=window.location.pathname;},7000);</script>

    <?php elseif($state==='ride_assigned'): ?>
    <!-- Heading to pickup -->
    <div class="map-badge dist-bar">
        <span class="dist-icon">🧑‍✈️</span>
        <div>
            <div class="dist-title">Heading to pickup</div>
            <div class="dist-val" id="distToPickup">Calculating…</div>
        </div>
    </div>
    <!-- Map action buttons -->
    <?php $pickupDest = (!empty($activeRide['pickup_lat'])&&!empty($activeRide['pickup_lng'])) ? $activeRide['pickup_lat'].','.$activeRide['pickup_lng'] : urlencode($activeRide['pickup_address']); ?>
    <div class="map-action-bar">
        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $pickupDest ?>" target="_blank" class="map-act-btn map-act-nav" title="Navigate to Pickup">🗺</a>
        <button type="button" class="map-act-btn map-act-wait" id="waitBtn" onclick="toggleWaiting()">⏱ მოლოდინი</button>
        <form method="POST" action="<?= e($base) ?>/taxi/driver/<?= e($encodedToken) ?>/start/<?= (int)$activeRide['id'] ?>" style="flex:1;display:flex">
            <button type="submit" class="map-act-btn map-act-board" style="width:100%">✅ On Board</button>
        </form>
    </div>

    <?php elseif($state==='ride_in_progress'): ?>
    <!-- In progress stats -->
    <div class="map-badge stats-bar">
        <div class="sb-box"><div class="sb-val" id="tripTotal">—</div><div class="sb-lbl">სულ</div></div>
        <div class="sb-box"><div class="sb-val" style="color:var(--green)" id="tripLeft">—</div><div class="sb-lbl">დარჩა</div></div>
        <div class="sb-box"><div class="sb-val" style="color:var(--amber)" id="tripEta">—</div><div class="sb-lbl">სავარ.</div></div>
    </div>

    <?php else: ?>
    <!-- Waiting pill -->
    <div class="waiting-pill-wrap">
        <div class="waiting-pill">
            <div class="waiting-icon"><?= $isOnline?'🚕':'😴' ?></div>
            <div class="waiting-txt"><?= $isOnline?'Waiting for ride requests…':'You are offline' ?></div>
            <div class="waiting-sub"><?= $isOnline?'New offers will pop up automatically':'Toggle Online to start receiving rides' ?></div>
        </div>
    </div>
    <?php endif ?>

</div><!-- /map-col -->

<!-- ── RIGHT PANEL ──────────────────────────────────────────────────────── -->
<?php if($state==='ride_assigned'): ?>
<?php $ride = $activeRide; ?>
<div class="right-panel">
    <div class="rp-head"><span class="rp-title">Ride Details</span></div>
    <div class="rp-row"><span class="lbl">Ride #</span><span class="val" style="font-family:monospace;font-size:12px"><?= e($ride['ride_number']) ?></span></div>
    <div class="rp-row"><span class="lbl">📍 Pickup</span><span class="val"><?= e($ride['pickup_address']) ?></span></div>
    <div class="rp-row"><span class="lbl">🏁 Destination</span><span class="val"><?= e($ride['destination']) ?></span></div>
    <div class="rp-row">
        <span class="lbl">👤 Customer</span>
        <span class="val">
            <?= e($ride['customer_name'] ?: '—') ?>
            <?php if($ride['customer_phone']): ?>
            <br><a href="tel:<?= e($ride['customer_phone']) ?>" style="color:var(--blue);font-size:12px"><?= e($ride['customer_phone']) ?></a>
            <?php endif ?>
        </span>
    </div>
    <div class="rp-row"><span class="lbl">🚗 Car</span><span class="val"><?= e($taxi->carTypes()[$ride['car_type']]??$ride['car_type']) ?> · <?= (int)$ride['passengers'] ?> pax</span></div>
    <div class="rp-row"><span class="lbl">💰 Fare</span><span class="val"><?= $taxi->formatPrice((float)$ride['estimated_price']) ?></span></div>
    <div class="rp-row"><span class="lbl">💚 Your cut</span><span class="val" style="color:var(--green);font-size:16px;font-weight:800"><?= $taxi->formatPrice($taxi->calcDriverEarnings((float)$ride['estimated_price'])) ?></span></div>
    <?php if(!empty($ride['customer_note'])): ?>
    <div class="rp-row"><span class="lbl">📝 Note</span><span class="val" style="color:var(--amber);font-style:italic"><?= e($ride['customer_note']) ?></span></div>
    <?php endif ?>
    <?php
    $waitFree = (float)$taxi->setting('waiting_free_minutes','3');
    $waitRate = (float)$taxi->setting('waiting_rate_per_min','0.3');
    ?>
    <div class="rp-row" id="waitRow">
        <span class="lbl">⏱ მოლოდინი</span>
        <span class="val" style="text-align:right">
            <span style="font-size:11.5px;color:var(--muted)"><?= (int)$waitFree ?>წთ უფასო · <?= $taxi->formatPrice($waitRate) ?>/წთ</span>
            <div id="waitDisplay" style="color:var(--amber);font-weight:800;font-size:15px;margin-top:3px;display:none">00:00</div>
        </span>
    </div>
    <div style="padding:10px 14px;margin-top:auto">
        <div style="background:var(--s2);border-radius:8px;padding:8px 12px;font-size:11.5px;color:var(--muted);text-align:center;line-height:1.5">
            🗺 · ⏱ · ✅ ღილაკები <strong style="color:var(--text)">რუკის ქვეშ</strong>
        </div>
    </div>
</div>

<?php elseif($state==='ride_in_progress'): ?>
<?php $ride = $activeRide; ?>
<div class="right-panel">
    <div class="rp-head"><span class="rp-title">In Progress</span></div>
    <div class="rp-row"><span class="lbl">Ride #</span><span class="val" style="font-family:monospace;font-size:12px"><?= e($ride['ride_number']) ?></span></div>
    <div class="rp-row"><span class="lbl">📍 From</span><span class="val"><?= e($ride['pickup_address']) ?></span></div>
    <div class="rp-row"><span class="lbl">🏁 To</span><span class="val"><?= e($ride['destination']) ?></span></div>
    <div class="rp-row"><span class="lbl">👤 Passenger</span><span class="val"><?= e($ride['customer_name'] ?: '—') ?></span></div>
    <div class="rp-row"><span class="lbl">💰 Fare</span><span class="val"><?= $taxi->formatPrice((float)$ride['estimated_price']) ?></span></div>
    <div class="rp-row"><span class="lbl">💚 Your cut</span><span class="val" style="color:var(--green);font-size:16px;font-weight:800"><?= $taxi->formatPrice($taxi->calcDriverEarnings((float)$ride['estimated_price'])) ?></span></div>
    <div class="rp-actions">
        <?php $destCoord = (!empty($ride['dest_lat'])&&!empty($ride['dest_lng'])) ? $ride['dest_lat'].','.$ride['dest_lng'] : urlencode($ride['destination']); ?>
        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $destCoord ?>" target="_blank" class="rp-btn btn-blue">🗺 Navigate to Destination</a>
        <form method="POST" action="<?= e($base) ?>/taxi/driver/<?= e($encodedToken) ?>/complete/<?= (int)$ride['id'] ?>">
            <button type="submit" class="rp-btn btn-green" style="width:100%">✅ Complete Ride</button>
        </form>
    </div>
</div>
<?php endif ?>

</div><!-- /layout -->

<!-- ── CANCELLATION NOTICE (shown when customer cancels mid-ride) ──────── -->
<div id="rideCancel" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.97);backdrop-filter:blur(8px);align-items:center;justify-content:center;flex-direction:column;gap:16px;color:#fff;text-align:center;padding:32px">
    <div style="font-size:64px;animation:shake .5s ease">❌</div>
    <div style="font-size:24px;font-weight:900;line-height:1.2">მომხმარებელმა<br>მგზავრობა გააუქმა</div>
    <div style="font-size:14px;color:#94a3b8;max-width:260px;line-height:1.6">შეგიძლია გზა გააგრძელო — ახალი მოთხოვნა მოვა</div>
    <button onclick="window.location.reload()" style="margin-top:8px;background:#ef4444;color:#fff;border:none;padding:14px 36px;border-radius:14px;font-size:16px;font-weight:800;cursor:pointer;font-family:inherit;box-shadow:0 4px 20px rgba(239,68,68,.5);transition:opacity .15s">
        OK, გასაგებია
    </button>
</div>

<!-- ════ NOTIFICATION DRAWER ════════════════════════════════════════════════ -->
<div class="notif-overlay" id="notifOverlay" onclick="closeNotifDrawer()"></div>
<div class="notif-drawer" id="notifDrawer">
    <div class="notif-head">
        <div class="notif-head-title">🔔 შეტყობინებები <span id="notifUnreadCount" style="font-size:11px;font-weight:700;color:#fff;background:var(--red);padding:1px 7px;border-radius:999px;<?= $unreadCount > 0 ? '' : 'display:none' ?>"><?= $unreadCount ?></span></div>
        <button class="notif-close" onclick="closeNotifDrawer()">✕</button>
    </div>
    <div class="notif-list" id="notifList">
        <?php
        $notifs = $taxi->getDriverNotifications($driverId, 30);
        if (empty($notifs)):
        ?>
        <div class="notif-empty">📭<br>შეტყობინებები არ არის</div>
        <?php else: foreach ($notifs as $n):
            $isUnread = !(bool)$n['is_read'];
            $parts = explode(' | ', $n['body']);
        ?>
        <div class="notif-item <?= $isUnread ? 'unread' : '' ?>">
            <div class="notif-item-title">
                <?php if($n['type']==='settlement'): ?>💰<?php else: ?>📌<?php endif ?>
                <?= htmlspecialchars($n['title'], ENT_QUOTES) ?>
            </div>
            <div class="notif-item-body">
                <?php foreach ($parts as $part):
                    $part = trim($part);
                    if ($part === '') continue;
                    $isDebtLine  = str_contains($part, 'საიტის ვალი');
                    $isPayLine   = str_contains($part, 'გასარიცხი');
                    $color = $isDebtLine ? 'var(--green)' : ($isPayLine ? 'var(--red)' : 'inherit');
                ?>
                <div class="notif-finance-row">
                    <span class="lbl"><?= htmlspecialchars(explode(':', $part)[0] ?? $part, ENT_QUOTES) ?></span>
                    <span style="font-weight:700;color:<?= $color ?>"><?= htmlspecialchars(trim(explode(':', $part, 2)[1] ?? ''), ENT_QUOTES) ?></span>
                </div>
                <?php endforeach ?>
            </div>
            <div class="notif-item-time"><?= date('d.m.Y H:i', strtotime($n['created_at'])) ?></div>
        </div>
        <?php endforeach; endif ?>
    </div>
</div>

<!-- ════ SCRIPTS ════════════════════════════════════════════════════════════ -->
<script>
// ── Config ──────────────────────────────────────────────────────────────────
var API    = <?= json_encode($base) ?>;
var TOKEN  = <?= json_encode($token) ?>;
var STATE  = <?= json_encode($state) ?>;
var SYM    = <?= json_encode($sym) ?>;
var COMM   = <?= (float)$commPct ?>;
var ONLINE = <?= $isOnline ? 'true' : 'false' ?>;
var RIDE_NUMBER = <?= json_encode($activeRide['ride_number'] ?? null) ?>;
var RIDE_ID     = <?= (int)($activeRide['id'] ?? 0) ?>;

// ── Map ──────────────────────────────────────────────────────────────────────
var map = L.map('driverMap', {zoomControl:true, attributionControl:false});
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
map.setView([41.6938, 44.8015], 18);

var puIcon = L.divIcon({html:'<div style="position:relative;width:56px;height:64px;display:flex;align-items:flex-end;justify-content:center"><div style="position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:50px;height:50px;border-radius:50%;background:rgba(99,102,241,.22);animation:mylocpulse 1.6s ease-out infinite"></div><div style="font-size:46px;line-height:1;filter:drop-shadow(0 3px 6px rgba(0,0,0,.3));position:relative;z-index:1">🧍</div></div>',iconSize:[56,64],iconAnchor:[28,64],className:''});
var dsIcon = L.divIcon({html:'<div style="font-size:36px;line-height:1;filter:drop-shadow(0 2px 5px rgba(239,68,68,.5))">🏁</div>',iconSize:[40,40],iconAnchor:[20,40],className:''});
var drIcon = L.divIcon({html:'<div style="font-size:44px;line-height:1;filter:drop-shadow(0 3px 7px rgba(0,0,0,.45))">🚕</div>',iconSize:[52,52],iconAnchor:[26,26],className:''});
var taxiIcon = L.divIcon({html:'<div style="font-size:44px;line-height:1;filter:drop-shadow(0 3px 7px rgba(0,0,0,.45))">🚕</div>',iconSize:[52,52],iconAnchor:[26,26],className:''});

var drMarker=null, puMarker=null, dsMarker=null, routeLine=null;
var pc = <?= (!empty($activeRide['pickup_lat'])&&!empty($activeRide['pickup_lng'])) ? json_encode([(float)$activeRide['pickup_lat'],(float)$activeRide['pickup_lng']]) : 'null' ?>;
var dc = <?= (!empty($activeRide['dest_lat'])&&!empty($activeRide['dest_lng']))    ? json_encode([(float)$activeRide['dest_lat'],  (float)$activeRide['dest_lng']])   : 'null' ?>;
var totDist=null, lastOsrm=0;

function geocode(addr, cb) {
    fetch('https://nominatim.openstreetmap.org/search?format=json&q='+encodeURIComponent(addr)+'&limit=1', {headers:{'Accept-Language':'ka,en'}})
        .then(function(r){return r.json();}).then(function(d){if(d.length>0)cb([+d[0].lat,+d[0].lon]);}).catch(function(){});
}
function fmtKm(m){ return (m/1000).toFixed(1)+' km'; }
function fmtMin(s){ var m=Math.ceil(s/60); return m<60?m+' წთ':Math.floor(m/60)+'სთ '+(m%60)+'წთ'; }
function throttleOsrm(){ var now=Date.now(); if(now-lastOsrm<12000)return false; lastOsrm=now; return true; }

<?php if($state==='ride_assigned'): ?>
// ── Map: heading to pickup ───────────────────────────────────────────────────
function ensurePickup(cb){
    if(pc){if(!puMarker)puMarker=L.marker(pc,{icon:puIcon}).addTo(map);cb();return;}
    geocode(<?= json_encode($activeRide['pickup_address']??'') ?>,function(p){pc=p;puMarker=L.marker(pc,{icon:puIcon}).addTo(map);cb();});
}
if(dc){if(!dsMarker)dsMarker=L.marker(dc,{icon:dsIcon,opacity:.4}).addTo(map);}
else{geocode(<?= json_encode($activeRide['destination']??'') ?>,function(d){dc=d;dsMarker=L.marker(dc,{icon:dsIcon,opacity:.4}).addTo(map);});}

function drawPickupRoute(){
    if(!pc||!drMarker||!throttleOsrm())return;
    var dl=drMarker.getLatLng();
    fetch('https://router.project-osrm.org/route/v1/driving/'+dl.lng+','+dl.lat+';'+pc[1]+','+pc[0]+'?overview=full&geometries=geojson')
        .then(function(r){return r.json();}).then(function(d){
            if(routeLine)map.removeLayer(routeLine);
            if(d.routes&&d.routes[0]){
                routeLine=L.geoJSON(d.routes[0].geometry,{style:{color:'#6366f1',weight:5,opacity:.9}}).addTo(map);
                map.fitBounds(routeLine.getBounds(),{padding:[60,80]});
                var el=document.getElementById('distToPickup');
                if(el)el.textContent=fmtKm(d.routes[0].distance)+' · ~'+Math.ceil(d.routes[0].duration/60)+' min';
            }
        }).catch(function(){
            if(routeLine)map.removeLayer(routeLine);
            if(pc&&drMarker){var dl2=drMarker.getLatLng();routeLine=L.polyline([[dl2.lat,dl2.lng],pc],{color:'#6366f1',weight:4,dashArray:'8,5'}).addTo(map);}
        });
}
ensurePickup(function(){});

<?php elseif($state==='ride_in_progress'): ?>
// ── Map: in-progress route ───────────────────────────────────────────────────
if(pc&&dc){
    puMarker=L.marker(pc,{icon:puIcon}).addTo(map);
    dsMarker=L.marker(dc,{icon:dsIcon}).addTo(map);
    fetch('https://router.project-osrm.org/route/v1/driving/'+pc[1]+','+pc[0]+';'+dc[1]+','+dc[0]+'?overview=full&geometries=geojson')
        .then(function(r){return r.json();}).then(function(d){
            if(d.routes&&d.routes[0]){
                routeLine=L.geoJSON(d.routes[0].geometry,{style:{color:'#3b82f6',weight:5,opacity:.9}}).addTo(map);
                totDist=d.routes[0].distance;
                document.getElementById('tripTotal').textContent=fmtKm(totDist);
                map.fitBounds(routeLine.getBounds(),{padding:[60,80]});
            }
        }).catch(function(){if(pc&&dc){routeLine=L.polyline([pc,dc],{color:'#3b82f6',weight:4,dashArray:'8,5'}).addTo(map);map.fitBounds(L.latLngBounds([pc,dc]),{padding:[60,80]});}});
} else {
    var _pa=<?= json_encode($activeRide['pickup_address']??'') ?>, _da=<?= json_encode($activeRide['destination']??'') ?>;
    if(_pa&&!pc)geocode(_pa,function(p){pc=p;if(!puMarker)puMarker=L.marker(pc,{icon:puIcon}).addTo(map);});
    if(_da&&!dc)geocode(_da,function(p){dc=p;if(!dsMarker)dsMarker=L.marker(dc,{icon:dsIcon}).addTo(map);});
}
function updateLeft(lat,lng){
    if(!dc||!throttleOsrm())return;
    fetch('https://router.project-osrm.org/route/v1/driving/'+lng+','+lat+';'+dc[1]+','+dc[0]+'?overview=false')
        .then(function(r){return r.json();}).then(function(d){
            if(d.routes&&d.routes[0]){
                document.getElementById('tripLeft').textContent=fmtKm(d.routes[0].distance);
                document.getElementById('tripEta').textContent=fmtMin(d.routes[0].duration);
            }
        }).catch(function(){});
}

<?php else: ?>
// ── Map: waiting — show online taxis ────────────────────────────────────────
var onlineMarkers=[];
function loadOnline(){
    fetch(API+'/api/taxi/online-drivers').then(function(r){return r.json();}).then(function(data){
        onlineMarkers.forEach(function(m){map.removeLayer(m);});
        onlineMarkers=[];
        (data.drivers||[]).forEach(function(d){
            onlineMarkers.push(L.marker([d.lat,d.lng],{icon:taxiIcon,interactive:false}).addTo(map));
        });
    }).catch(function(){});
}
loadOnline();
setInterval(loadOnline, 20000);
<?php endif ?>

// ── Offer display (PHP state = offer_pending) ────────────────────────────────
<?php if($offerData): ?>
(function(){
    var o  = <?= json_encode($offerData) ?>;
    var t  = Math.max(1, o.expires_in);
    var tot= t, len=157.1;
    var arc=document.getElementById('timerArc'), num=document.getElementById('timerNum');
    var price = parseFloat(o.price)||0;
    var earn  = Math.round(price*(1-COMM/100)*100)/100;
    document.getElementById('offerPrice').textContent  = SYM+price.toFixed(2);
    document.getElementById('offerEarn').textContent   = SYM+earn.toFixed(2);
    document.getElementById('offerPickup').textContent = o.pickup||'—';
    document.getElementById('offerDest').textContent   = o.destination||'—';
    document.getElementById('offerCar').textContent    = (o.car_type||'')+(o.passengers?' · '+o.passengers+' pax':'');
    if(o.note){document.getElementById('offerNote').textContent=o.note;document.getElementById('offerNoteRow').style.display='';}
    var ENC = <?= json_encode($encodedToken) ?>;
    document.getElementById('acceptForm').action  = API+'/taxi/driver/'+ENC+'/accept/'+o.ride_id;
    document.getElementById('declineForm').action = API+'/taxi/driver/'+ENC+'/decline/'+o.ride_id;
    var pill=document.querySelector('.waiting-pill-wrap'); if(pill)pill.style.display='none';
    document.getElementById('offerModal').style.display='block';
    if(num)num.textContent=t+'s';
    var iv=setInterval(function(){
        t--;
        if(arc)arc.setAttribute('stroke-dashoffset',len*(1-t/tot));
        if(num)num.textContent=t+'s';
        if(t<=8){if(arc)arc.setAttribute('stroke','var(--red)');if(num)num.style.color='var(--red)';}
        if(t<=0){clearInterval(iv);window.location.reload();}
    },1000);
    try{var a=new(window.AudioContext||window.webkitAudioContext)();[0,.15,.3].forEach(function(d){var oc=a.createOscillator(),g=a.createGain();oc.connect(g);g.connect(a.destination);oc.frequency.value=880;g.gain.setValueAtTime(.2,a.currentTime+d);g.gain.exponentialRampToValueAtTime(.001,a.currentTime+d+.25);oc.start(a.currentTime+d);oc.stop(a.currentTime+d+.3);});}catch(e){}
})();
<?php endif ?>

// ── Polling (waiting state: reload page when offer arrives) ──────────────────
<?php if($state==='waiting'): ?>
(function poll(){
    setTimeout(function(){
        fetch(API+'/api/taxi/driver-offer/'+TOKEN)
            .then(function(r){ return r.json(); })
            .then(function(d){ if(d.offer) window.location.reload(); else poll(); })
            .catch(function(){ poll(); });
    }, 4000);
})();
<?php endif ?>

// ── Polling (active ride: detect customer cancellation in real-time) ──────────
<?php if($state==='ride_assigned' || $state==='ride_in_progress'): ?>
(function pollCancel(){
    setTimeout(function(){
        fetch(API+'/api/taxi/driver-ride-status/'+TOKEN)
            .then(function(r){ return r.json(); })
            .then(function(d){
                if(d.status === 'cancelled'){
                    document.getElementById('rideCancel').style.display = 'flex';
                } else {
                    pollCancel();
                }
            })
            .catch(function(){ pollCancel(); });
    }, 5000);
})();
<?php endif ?>

// ── Waiting timer ─────────────────────────────────────────────────────────────
<?php if($state==='ride_assigned'): ?>
var WAIT_FREE = <?= (float)$taxi->setting('waiting_free_minutes','3') ?>;
var WAIT_RATE = <?= (float)$taxi->setting('waiting_rate_per_min','0.3') ?>;
var waitStart = null, waitIv = null;

function toggleWaiting(){
    var btn = document.getElementById('waitBtn');
    if(!waitStart){
        fetch(API+'/api/taxi/driver-waiting/'+TOKEN+'/'+RIDE_ID,{
            method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({waiting:true})
        }).catch(function(){});
        waitStart = Date.now();
        btn.classList.add('active');
        waitIv = setInterval(tickWait, 1000);
        tickWait();
    } else {
        fetch(API+'/api/taxi/driver-waiting/'+TOKEN+'/'+RIDE_ID,{
            method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({waiting:false})
        }).catch(function(){});
        clearInterval(waitIv); waitIv = null; waitStart = null;
        btn.classList.remove('active');
        btn.innerHTML = '⏱ მოლოდინი';
        var wd = document.getElementById('waitDisplay');
        if(wd){ wd.style.display='none'; wd.textContent='00:00'; }
    }
}
<?php if($state==='ride_assigned' && !empty($activeRide['waiting_started_at'])): ?>
// Restore waiting state after page reload
(function(){
    waitStart = <?= strtotime((string)$activeRide['waiting_started_at']) ?> * 1000;
    var btn = document.getElementById('waitBtn');
    if(btn) btn.classList.add('active');
    waitIv = setInterval(tickWait, 1000);
    tickWait();
})();
<?php endif ?>
function tickWait(){
    if(!waitStart) return;
    var elapsed = Math.floor((Date.now() - waitStart) / 1000);
    var m = Math.floor(elapsed / 60), s = elapsed % 60;
    var billable = Math.max(0, m - WAIT_FREE);
    var fee = Math.round(billable * WAIT_RATE * 100) / 100;
    var timeStr = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    var feeStr  = fee > 0 ? ' · ' + SYM + fee.toFixed(2) : '';
    var btn = document.getElementById('waitBtn');
    if(btn) btn.innerHTML = '⏱ ' + timeStr + feeStr;
    var wd = document.getElementById('waitDisplay');
    if(wd){ wd.style.display=''; wd.textContent = timeStr + (fee>0 ? ' · '+SYM+fee.toFixed(2) : ' (უფასო)'); }
}
<?php endif ?>

// ── Notifications ────────────────────────────────────────────────────────────
var NOTIF_TOKEN = <?= json_encode($token) ?>;

function openNotifDrawer() {
    document.getElementById('notifDrawer').classList.add('open');
    document.getElementById('notifOverlay').classList.add('show');
    // Mark all as read
    fetch(API + '/api/taxi/driver-notifications/' + NOTIF_TOKEN + '/read-all', {method:'POST'})
        .then(function() {
            document.getElementById('notifBadge').classList.remove('show');
            document.getElementById('notifBadge').textContent = '';
            var uc = document.getElementById('notifUnreadCount');
            if (uc) uc.style.display = 'none';
            document.querySelectorAll('.notif-item.unread').forEach(function(el) {
                el.classList.remove('unread');
            });
        }).catch(function(){});
}

function closeNotifDrawer() {
    document.getElementById('notifDrawer').classList.remove('open');
    document.getElementById('notifOverlay').classList.remove('show');
}

// Poll for new notifications every 60s
(function pollNotifs() {
    setTimeout(function() {
        fetch(API + '/api/taxi/driver-notifications/' + NOTIF_TOKEN)
            .then(function(r) { return r.json(); })
            .then(function(d) {
                var count = d.unread || 0;
                var badge = document.getElementById('notifBadge');
                if (badge) {
                    if (count > 0) {
                        badge.textContent = count;
                        badge.classList.add('show');
                    } else {
                        badge.classList.remove('show');
                    }
                }
            })
            .catch(function(){})
            .finally(pollNotifs);
    }, 60000);
})();

// ── GPS sharing ──────────────────────────────────────────────────────────────
var isOnline = ONLINE;
if(navigator.geolocation){
    navigator.geolocation.watchPosition(
        function(pos){
            var lat=pos.coords.latitude, lng=pos.coords.longitude;
            if(isOnline){
                var spd=pos.coords.speed!=null?Math.round(pos.coords.speed*3.6*10)/10:null;
                fetch(API+'/api/taxi/driver-location/'+TOKEN, {
                    method:'POST', headers:{'Content-Type':'application/json'},
                    body:JSON.stringify({lat:lat,lng:lng,speed:spd})
                }).catch(function(){});
            }
            if(drMarker){
                drMarker.setLatLng([lat,lng]);
                if(!isOnline){ map.removeLayer(drMarker); drMarker=null; }
            } else if(isOnline){
                drMarker = L.marker([lat,lng],{icon:drIcon}).addTo(map);
            }
            // Center map on driver while waiting
            <?php if($state==='waiting'||$state==='offer_pending'): ?>
            if(isOnline && drMarker) map.setView([lat,lng], 18, {animate:true});
            <?php endif ?>
            // Update route
            <?php if($state==='ride_assigned'): ?>ensurePickup(drawPickupRoute);<?php endif ?>
            <?php if($state==='ride_in_progress'): ?>updateLeft(lat,lng);<?php endif ?>
        },
        function(){},
        {enableHighAccuracy:true, maximumAge:8000}
    );
}

// ── Online toggle ────────────────────────────────────────────────────────────
<?php if($state==='ride_assigned'||$state==='ride_in_progress'): ?>
(function(){
    var tog=document.getElementById('onlineToggle');
    tog.disabled=true;
    tog.closest('.toggle').title='მგზავრობა მიმდინარეობს — ოფლაინზე გადასვლა შეუძლებელია';
    tog.closest('.toggle').style.opacity='.4';
    tog.closest('.toggle').style.cursor='not-allowed';
})();
<?php endif ?>

document.getElementById('onlineToggle').addEventListener('change', function(){
    var tog=this;
    var on=tog.checked;
    if(!on){
        fetch(API+'/api/taxi/driver-online/'+TOKEN,{
            method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({online:false})
        }).then(function(r){return r.json();}).then(function(d){
            if(d.blocked){
                // Restore toggle to ON, show message
                tog.checked=true;
                var msg=document.createElement('div');
                msg.textContent=d.reason;
                msg.style.cssText='position:fixed;top:70px;left:50%;transform:translateX(-50%);background:#ef4444;color:#fff;padding:10px 22px;border-radius:999px;font-size:13px;font-weight:700;z-index:9999;box-shadow:0 4px 16px rgba(239,68,68,.4);pointer-events:none;animation:fadeInDown .25s ease';
                document.body.appendChild(msg);
                setTimeout(function(){msg.remove();},3500);
            } else {
                isOnline=false;
                document.getElementById('liveDot').className='live-dot off';
                document.getElementById('statusTxt').textContent='Offline';
                document.getElementById('statusTxt').style.color='var(--muted)';
                if(drMarker){map.removeLayer(drMarker);drMarker=null;}
                window.location.reload();
            }
        }).catch(function(){tog.checked=true;});
    } else {
        isOnline=true;
        document.getElementById('liveDot').className='live-dot on';
        document.getElementById('statusTxt').textContent='Online';
        document.getElementById('statusTxt').style.color='var(--green)';
        fetch(API+'/api/taxi/driver-online/'+TOKEN,{
            method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({online:true})
        }).then(function(){window.location.reload();}).catch(function(){});
    }
});
</script>
</body>
</html>