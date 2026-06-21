<!DOCTYPE html>
<html lang="ka">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($driver['name']) ?> · კურიერი</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#f1f5f9;--surface:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;
  --green:#22c55e;--amber:#f59e0b;--blue:#3b82f6;--red:#ef4444;--purple:#8b5cf6;
  --dark:#0b0f1a;--dark-s:#111827;--dark-b:#1f2d45;
}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;padding-bottom:32px}
a,a:hover{text-decoration:none}

/* ── Topbar ──────────────────────────────────────────────────────────────── */
.topbar{
  background:linear-gradient(135deg,#1e293b,#0f172a);
  color:#fff;padding:14px 18px;
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:100;
}
.topbar-left{display:flex;align-items:center;gap:10px}
.topbar-name{font-size:17px;font-weight:900}
.topbar-vehicle{font-size:11px;color:#94a3b8;margin-top:1px}
.live-dot{width:9px;height:9px;border-radius:50%;background:var(--green);flex-shrink:0;animation:lb 2s infinite}
@keyframes lb{0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,.4)}60%{box-shadow:0 0 0 6px rgba(34,197,94,0)}}

/* ── Cards ───────────────────────────────────────────────────────────────── */
.main{max-width:520px;margin:0 auto;padding:18px 14px;display:flex;flex-direction:column;gap:14px}
.card{background:var(--surface);border-radius:18px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);border:1.5px solid var(--border)}

/* ── Status header card ──────────────────────────────────────────────────── */
.status-card{background:linear-gradient(135deg,#1e293b,#0f172a);color:#fff;border:none}
.status-card-inner{padding:22px 20px 18px}
.status-hero{font-size:52px;margin-bottom:8px;display:inline-block}
.status-label{font-size:22px;font-weight:900;margin-bottom:4px}
.status-sub{font-size:13px;color:#94a3b8}
.status-num{font-size:11px;font-family:monospace;color:#64748b;margin-top:6px}

/* ── Progress steps ──────────────────────────────────────────────────────── */
.steps-track{padding:18px 20px 14px;display:flex;align-items:center;gap:0}
.step-item{display:flex;flex-direction:column;align-items:center;flex:1;position:relative}
.step-item:not(:last-child)::after{
  content:'';position:absolute;top:12px;left:calc(50% + 14px);
  right:calc(-50% + 14px);height:2px;background:var(--border);z-index:0;
}
.step-item.done:not(:last-child)::after{background:var(--green)}
.step-dot2{width:24px;height:24px;border-radius:50%;border:2.5px solid var(--border);background:var(--bg);z-index:1;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:var(--muted);position:relative}
.step-dot2.done{background:var(--green);border-color:var(--green);color:#fff}
.step-dot2.active{background:var(--amber);border-color:var(--amber);color:#fff;animation:stepPulse 2s infinite}
@keyframes stepPulse{0%,100%{box-shadow:0 0 0 0 rgba(245,158,11,.35)}60%{box-shadow:0 0 0 7px rgba(245,158,11,0)}}
.step-name{font-size:9.5px;color:var(--muted);margin-top:5px;text-align:center;font-weight:600;white-space:nowrap}
.step-name.active{color:var(--amber);font-weight:800}
.step-name.done{color:var(--green)}

/* ── Detail rows ─────────────────────────────────────────────────────────── */
.detail-section{padding:16px 18px;display:flex;flex-direction:column;gap:0}
.detail-row{display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)}
.detail-row:last-child{border-bottom:none}
.detail-icon{font-size:17px;flex-shrink:0;margin-top:1px;width:24px;text-align:center}
.detail-content{flex:1}
.detail-lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px}
.detail-val{font-size:13px;font-weight:700;color:var(--text);word-break:break-word}
.detail-note{font-size:12px;color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:8px 10px;margin-top:8px}

/* ── Items list ──────────────────────────────────────────────────────────── */
.items-section{padding:4px 0 0}
.items-header{padding:12px 18px 6px;font-size:10px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.4px}
.item-row{display:flex;justify-content:space-between;align-items:center;padding:9px 18px;border-bottom:1px solid var(--border);font-size:13px}
.item-row:last-child{border-bottom:none}
.item-name{font-weight:600}
.item-qty{font-size:11px;color:var(--muted);margin-left:4px}
.item-price{font-weight:700;color:var(--muted)}

/* ── Map ─────────────────────────────────────────────────────────────────── */
.map-card{overflow:hidden;padding:0;border-radius:18px}
#courierMap{width:100%;height:220px}

/* ── Action buttons ──────────────────────────────────────────────────────── */
.actions-card{padding:14px 16px;display:flex;flex-direction:column;gap:10px;background:var(--surface)}
.act-btn{
  width:100%;padding:15px;border:none;border-radius:14px;
  font-size:15px;font-weight:800;cursor:pointer;font-family:inherit;
  transition:opacity .15s;display:flex;align-items:center;justify-content:center;gap:8px
}
.act-btn:hover{opacity:.85}
.act-btn:disabled{opacity:.5;cursor:default}
.act-pickup{background:var(--blue);color:#fff}
.act-deliver{background:var(--green);color:#fff}
.nav-btn{
  background:#f1f5f9;color:var(--blue);border:1.5px solid var(--blue);
  border-radius:14px;padding:13px;font-size:14px;font-weight:700;cursor:pointer;
  font-family:inherit;text-decoration:none;
  display:flex;align-items:center;justify-content:center;gap:7px;
  transition:background .15s;
}
.nav-btn:hover{background:#dbeafe}
.spinner{display:inline-block;width:18px;height:18px;border:3px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── Proximity row ───────────────────────────────────────────────────────── */
.prox-row{display:flex;align-items:center;justify-content:center;gap:8px;font-size:12px;font-weight:600;padding:6px 0;min-height:30px;transition:color .3s}
.prox-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;transition:background .3s}

/* ── Waiting state ───────────────────────────────────────────────────────── */
.waiting-card{padding:40px 20px;text-align:center}
.waiting-emoji{font-size:56px;margin-bottom:14px}
.waiting-title{font-size:17px;font-weight:800;margin-bottom:6px}
.waiting-sub{font-size:13px;color:var(--muted)}
.waiting-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--amber);animation:lb 1.5s infinite;margin:0 2px}

/* ── Handoff PIN card ────────────────────────────────────────────────────── */
.pin-card{background:linear-gradient(135deg,#1e3a5f 0%,#0f172a 100%);border:none;color:#fff;padding:20px 22px}
.pin-card-label{font-size:10px;text-transform:uppercase;letter-spacing:.8px;color:#94a3b8;font-weight:700;margin-bottom:10px}
.pin-digits{display:flex;gap:10px;justify-content:center;margin-bottom:12px}
.pin-box{width:58px;height:72px;background:rgba(255,255,255,.1);border:2px solid rgba(255,255,255,.2);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:34px;font-weight:900;font-family:monospace;letter-spacing:0}
.pin-hint{font-size:12px;color:#94a3b8;text-align:center;line-height:1.4}

/* ── Offer overlay ───────────────────────────────────────────────────────── */
#offerOverlay{
  position:fixed;inset:0;z-index:9000;
  background:rgba(0,0,0,.72);backdrop-filter:blur(4px);
  display:flex;align-items:flex-end;justify-content:center;
  transition:opacity .3s;
}
#offerOverlay.hidden{display:none}
#offerCard{
  width:100%;max-width:520px;
  background:#fff;
  border-radius:24px 24px 0 0;
  padding:24px 18px 36px;
  box-shadow:0 -8px 40px rgba(0,0,0,.3);
  animation:slideUp .35s cubic-bezier(.22,1,.36,1);
}
@keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
.offer-handle{width:40px;height:4px;background:#e2e8f0;border-radius:2px;margin:0 auto 20px}
.offer-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px}
.offer-title{font-size:19px;font-weight:900}
.offer-timer{font-size:30px;font-weight:900;color:var(--amber);min-width:46px;text-align:right;transition:color .3s}
.offer-timer.urgent{color:var(--red);animation:timerPulse .5s ease-in-out infinite}
@keyframes timerPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.15)}}
.offer-num-lbl{font-size:11px;color:var(--muted);font-family:monospace;margin-bottom:14px}
.offer-progress{height:4px;border-radius:2px;background:#f1f5f9;margin-bottom:18px;overflow:hidden}
.offer-progress-bar{height:100%;background:var(--amber);transition:width .9s linear,background .3s}
.offer-progress-bar.urgent{background:var(--red)}
.offer-chips{display:flex;gap:8px;margin-bottom:16px}
.offer-chip{flex:1;background:#f8fafc;border:1.5px solid var(--border);border-radius:12px;padding:10px 8px;text-align:center}
.offer-chip-val{font-size:17px;font-weight:900;color:var(--blue)}
.offer-chip-lbl{font-size:9.5px;color:var(--muted);margin-top:2px;text-transform:uppercase;letter-spacing:.3px}
.offer-info{background:#f8fafc;border-radius:14px;overflow:hidden;margin-bottom:18px;border:1.5px solid var(--border)}
.offer-row{display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border-bottom:1px solid var(--border)}
.offer-row:last-child{border-bottom:none}
.offer-row-icon{font-size:17px;flex-shrink:0;margin-top:1px}
.offer-row-label{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.3px;margin-bottom:2px}
.offer-row-val{font-size:13px;font-weight:700}
.offer-btns{display:flex;gap:10px}
.offer-btn{flex:1;padding:15px;border:none;border-radius:14px;font-size:15px;font-weight:800;cursor:pointer;font-family:inherit;transition:opacity .15s}
.offer-btn:hover{opacity:.85}
.offer-btn-decline{background:#f1f5f9;color:var(--muted);border:1.5px solid var(--border)}
.offer-btn-accept{background:var(--green);color:#fff}

/* ── Toast ───────────────────────────────────────────────────────────────── */
.toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(10px);z-index:9999;background:#0f172a;color:#fff;padding:11px 20px;border-radius:12px;font-size:13px;font-weight:700;box-shadow:0 4px 20px rgba(0,0,0,.2);opacity:0;transition:all .25s;pointer-events:none;white-space:nowrap}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
</style>
</head>
<body>

<?php
/* ── Offer data ─────────────────────────────────────────────────────────── */
$_offerSec = (!empty($offer) && !empty($offer['expires_at']))
    ? (int)strtotime($offer['expires_at']) - time()
    : -1;
$hasOffer  = !empty($offer) && !empty($offerOrder) && $_offerSec > 0;
$offerId   = $hasOffer ? (int)$offer['id']                    : 0;
$offerSec  = $hasOffer ? $_offerSec : 0;
$oVendor   = $hasOffer ? ($offerOrder['vendor'] ?? null)       : null;
$oPickup   = $hasOffer ? ($oVendor ? ($oVendor['address'] ?? '') : ($offerOrder['pickup_address'] ?? ''))   : '';
$oDeliver  = $hasOffer ? ($offerOrder['delivery_address'] ?? '') : '';
$oTotal    = $hasOffer ? number_format((float)$offerOrder['price'],2).$sym : '';
$oNote     = $hasOffer ? ($offerOrder['customer_note'] ?? '')  : '';
$oNum      = $hasOffer ? ($offerOrder['order_number']  ?? '')  : '';

$distTxt = '—'; $etaTxt = '—';
if ($hasOffer && !empty($oVendor['lat']) && !empty($offerOrder['delivery_lat'])) {
    $dlat = deg2rad((float)$oVendor['lat']);  $dlng = deg2rad((float)$oVendor['lng']);
    $elat = deg2rad((float)$offerOrder['delivery_lat']); $elng = deg2rad((float)$offerOrder['delivery_lng']);
    $a = sin(($elat-$dlat)/2)**2 + cos($dlat)*cos($elat)*sin(($elng-$dlng)/2)**2;
    $dist = 2*6371*asin(sqrt($a));
    $distTxt = round($dist,1).' კმ';
    $etaTxt  = '~'.max(1,(int)ceil($dist*1000/416)).' წთ';
}

/* ── Active order display (defaults prevent undefined-var warnings) ─────── */
$pLat = null; $pLng = null; $dLat = null; $dLng = null;
$vendor = null; $vAddr = ''; $dAddr = ''; $st = ''; $steps = [];
$statusIcons  = ['accepted'=>'✅','picked_up'=>'📦','in_transit'=>'🛵','delivered'=>'🏁'];
$statusLabels = ['accepted'=>'მიღებულია','picked_up'=>'შეკვეთა აღებულია','in_transit'=>'გზაშია','delivered'=>'ჩაბარდა!'];
$statusSubs   = [
    'accepted'   => 'გაემართე ობიექტისკენ',
    'picked_up'  => 'გაემართე მომხმარებელთან',
    'in_transit' => 'გაემართე მომხმარებელთან',
    'delivered'  => 'შეკვეთა წარმატებით ჩაბარდა',
];
if ($order) {
    $st       = $order['status'];
    $vendor   = $order['vendor'] ?? null;
    $vAddr    = $vendor ? ($vendor['address'] ?? '') : ($order['pickup_address'] ?? '');
    $dAddr    = $order['delivery_address'] ?? '';
    $pLat     = $order['pickup_lat'] ?? null;
    $pLng     = $order['pickup_lng'] ?? null;
    $dLat     = $order['delivery_lat'] ?? null;
    $dLng     = $order['delivery_lng'] ?? null;
    $steps = [
        ['key'=>'accepted',  'label'=>'მიღება',   'done'=>in_array($st,['accepted','picked_up','in_transit','delivered'])],
        ['key'=>'picked_up', 'label'=>'აღება',    'done'=>in_array($st,['picked_up','in_transit','delivered'])],
        ['key'=>'delivered', 'label'=>'ჩაბარება', 'done'=>$st==='delivered'],
    ];
}
?>

<!-- ── Offer overlay ──────────────────────────────────────────────────── -->
<div id="offerOverlay" class="<?= $hasOffer ? '' : 'hidden' ?>">
  <div id="offerCard">
    <div class="offer-handle"></div>
    <div class="offer-header">
      <div class="offer-title">🚨 ახალი შეკვეთა!</div>
      <div class="offer-timer" id="offerTimer"><?= $offerSec ?></div>
    </div>
    <div class="offer-num-lbl" id="offerNum"><?= e($oNum) ?></div>
    <div class="offer-progress"><div class="offer-progress-bar" id="offerBar" style="width:<?= $offerSec>0?round($offerSec/30*100):0 ?>%"></div></div>

    <div class="offer-chips">
      <div class="offer-chip"><div class="offer-chip-val" id="offerDist"><?= e($distTxt) ?></div><div class="offer-chip-lbl">მანძილი</div></div>
      <div class="offer-chip"><div class="offer-chip-val" id="offerEta"><?= e($etaTxt) ?></div><div class="offer-chip-lbl">სავარ. დრო</div></div>
      <div class="offer-chip"><div class="offer-chip-val" id="offerTotal"><?= e($oTotal) ?></div><div class="offer-chip-lbl">ჯამი</div></div>
    </div>

    <div class="offer-info">
      <div class="offer-row">
        <span class="offer-row-icon">🏪</span>
        <div><div class="offer-row-label">ობიექტი / აღება</div><div class="offer-row-val" id="offerPickup"><?= e($oPickup) ?></div></div>
      </div>
      <div class="offer-row">
        <span class="offer-row-icon">📍</span>
        <div><div class="offer-row-label">მიტანის მისამართი</div><div class="offer-row-val" id="offerDeliver"><?= e($oDeliver) ?></div></div>
      </div>
      <?php if($oNote): ?>
      <div class="offer-row">
        <span class="offer-row-icon">📝</span>
        <div><div class="offer-row-label">შენიშვნა</div><div class="offer-row-val" style="color:#92400e"><?= e($oNote) ?></div></div>
      </div>
      <?php endif ?>
    </div>

    <div class="offer-btns">
      <button class="offer-btn offer-btn-decline" onclick="declineOffer()">✕ უარი</button>
      <button class="offer-btn offer-btn-accept"  onclick="acceptOffer()">✓ მიღება</button>
    </div>
  </div>
</div>

<!-- ── Topbar ─────────────────────────────────────────────────────────── -->
<div class="topbar">
  <div class="topbar-left">
    <span class="live-dot"></span>
    <div>
      <div class="topbar-name">🛵 <?= e($driver['name']) ?></div>
      <div class="topbar-vehicle"><?= e($driver['vehicle_type'] ?? 'Courier') ?><?= !empty($driver['vehicle_num']) ? ' · '.e($driver['vehicle_num']) : '' ?></div>
    </div>
  </div>
  <button id="onlineToggleBtn" onclick="toggleOnline()" style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;padding:6px 10px;border-radius:20px;transition:background .15s" onmouseover="this.style.background='rgba(255,255,255,.08)'" onmouseout="this.style.background='none'">
    <span id="onlineDot" style="width:9px;height:9px;border-radius:50%;flex-shrink:0;transition:background .3s;background:<?= $driver['is_online'] ? '#22c55e' : '#64748b' ?>;<?= $driver['is_online'] ? 'animation:lb 2s infinite' : '' ?>"></span>
    <span id="onlineLbl" style="font-size:12px;font-weight:700;color:<?= $driver['is_online'] ? '#86efac' : '#64748b' ?>"><?= $driver['is_online'] ? 'Online' : 'Offline' ?></span>
  </button>
</div>

<!-- ── Main content ───────────────────────────────────────────────────── -->
<div class="main">

<?php if (!$order): ?>
  <!-- No active order -->
  <div class="card">
    <div class="waiting-card">
      <div class="waiting-emoji">😴</div>
      <div class="waiting-title">შეკვეთა არ არის</div>
      <div class="waiting-sub">ახალ შეკვეთას ელოდება<span class="waiting-dot"></span><span class="waiting-dot" style="animation-delay:.3s"></span><span class="waiting-dot" style="animation-delay:.6s"></span></div>
    </div>
  </div>

<?php else: ?>

  <!-- Status hero card -->
  <div class="card status-card">
    <div class="status-card-inner">
      <div class="status-hero" id="stHeroIcon"><?= $statusIcons[$st] ?? '🛵' ?></div>
      <div class="status-label" id="stHeroLabel"><?= $statusLabels[$st] ?? $st ?></div>
      <div class="status-sub" id="stHeroSub"><?= $statusSubs[$st] ?? '' ?></div>
      <div class="status-num"><?= e($order['order_number']) ?></div>
    </div>
    <!-- Progress steps -->
    <div class="steps-track" style="background:rgba(255,255,255,.04);padding:14px 20px 16px">
      <?php foreach ($steps as $i => $step):
        $isDone   = $step['done'];
        $isActive = !$step['done'] && ($i === 0 || $steps[$i-1]['done']);
      ?>
      <div class="step-item <?= $isDone?'done':'' ?>">
        <div class="step-dot2 <?= $isDone?'done':($isActive?'active':'') ?>">
          <?= $isDone ? '✓' : ($isActive ? '●' : '') ?>
        </div>
        <div class="step-name <?= $isDone?'done':($isActive?'active':'') ?>"><?= e($step['label']) ?></div>
      </div>
      <?php endforeach ?>
    </div>
  </div>

  <!-- Handoff PIN card -->
  <?php if (!empty($handoffPin)): ?>
  <div class="card pin-card">
    <div class="pin-card-label">🔐 გადაბარების კოდი — ვენდორს უჩვენეთ</div>
    <div class="pin-digits">
      <?php foreach (str_split($handoffPin) as $digit): ?>
      <div class="pin-box"><?= $digit ?></div>
      <?php endforeach ?>
    </div>
    <div class="pin-hint">ვენდორი ამ კოდს შეიყვანს შეკვეთის<br>დასრულებისას — ჩაბარების დასტური</div>
  </div>
  <?php endif ?>

  <!-- Address details -->
  <div class="card">
    <div class="detail-section">
      <?php if ($vendor): ?>
      <div class="detail-row">
        <span class="detail-icon">🏪</span>
        <div class="detail-content">
          <div class="detail-lbl">ობიექტი</div>
          <div class="detail-val"><?= e($vendor['name'] ?? '') ?></div>
        </div>
      </div>
      <?php endif ?>
      <div class="detail-row">
        <span class="detail-icon">📦</span>
        <div class="detail-content">
          <div class="detail-lbl">შეკვეთის აღება</div>
          <div class="detail-val"><?= e($vAddr) ?></div>
        </div>
      </div>
      <div class="detail-row">
        <span class="detail-icon">📍</span>
        <div class="detail-content">
          <div class="detail-lbl">მიტანის მისამართი</div>
          <div class="detail-val"><?= e($dAddr) ?></div>
        </div>
      </div>
      <?php if ($order['customer_note']): ?>
      <div class="detail-row" style="border-bottom:none;padding-bottom:4px">
        <span class="detail-icon">📝</span>
        <div class="detail-content">
          <div class="detail-lbl">შენიშვნა</div>
          <div class="detail-note"><?= e($order['customer_note']) ?></div>
        </div>
      </div>
      <?php endif ?>
    </div>
  </div>

  <!-- Items -->
  <?php if (!empty($order['items'])): ?>
  <div class="card">
    <div class="items-header">📋 შეკვეთის შემადგენლობა · <?= count($order['items']) ?> პოზიცია</div>
    <?php foreach ($order['items'] as $item): ?>
    <div class="item-row">
      <span><span class="item-name"><?= e($item['name']) ?></span><span class="item-qty">×<?= (int)$item['quantity'] ?></span></span>
      <span class="item-price"><?= number_format((float)($item['item_total'] ?? 0), 2).$sym ?></span>
    </div>
    <?php endforeach ?>
    <div class="item-row" style="border-top:2px solid var(--border);margin-top:2px">
      <span style="font-weight:800;color:var(--text)">ჯამი</span>
      <span style="font-weight:900;color:var(--amber);font-size:15px"><?= number_format((float)$order['price'],2).$sym ?></span>
    </div>
  </div>
  <?php endif ?>

  <!-- Map -->
  <?php if ($pLat || $dLat): ?>
  <div class="card map-card">
    <div id="courierMap"></div>
  </div>
  <?php endif ?>

  <!-- Actions -->
  <div class="card actions-card">
    <?php
    $pickupCoord  = ($pLat&&$pLng) ? "$pLat,$pLng"  : urlencode($vAddr);
    $deliverCoord = ($dLat&&$dLng) ? "$dLat,$dLng"  : urlencode($dAddr);
    ?>
    <?php if ($st === 'accepted'): ?>
      <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $pickupCoord ?>" target="_blank" class="nav-btn">🗺 მიმართულება — ობიექტი</a>
      <?php if ($pLat && $pLng): ?>
      <div class="prox-row" id="proxRow" style="color:var(--muted)">
        <span class="prox-dot" id="proxDot" style="background:#cbd5e1"></span>
        <span id="proxTxt">GPS მდებარეობის განსაზღვრა…</span>
      </div>
      <?php endif ?>
      <button class="act-btn act-pickup" id="mainActBtn"
        <?= ($pLat && $pLng) ? 'disabled' : '' ?>
        onclick="updateStatus('picked_up', this)">📦 შეკვეთის აღება</button>
    <?php elseif ($st === 'picked_up' || $st === 'in_transit'): ?>
      <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $deliverCoord ?>" target="_blank" class="nav-btn">🗺 მიმართულება — მომხმარებელი</a>
      <button class="act-btn act-deliver" id="mainActBtn" onclick="updateStatus('delivered', this)">✅ ჩაბარდა</button>
    <?php endif ?>
  </div>

<?php endif ?>
</div><!-- .main -->

<div class="toast" id="toast"></div>

<?php if ($pLat || $dLat): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif ?>
<script>
var BASE         = <?= json_encode($base) ?>;
var TOKEN        = <?= json_encode($token) ?>;
var ORDER_ID     = <?= $order ? (int)$order['id'] : 'null' ?>;
var SYM          = <?= json_encode($sym) ?>;
var IS_ONLINE    = <?= (int)$driver['is_online'] ?>;
var ORDER_STATUS = <?= $order ? json_encode($st) : 'null' ?>;
// Vendor pickup coords for proximity gate (null = no geofence check)
var VENDOR_LAT   = <?= ($order && $pLat) ? (float)$pLat : 'null' ?>;
var VENDOR_LNG   = <?= ($order && $pLng) ? (float)$pLng : 'null' ?>;
var PICKUP_RADIUS = 200; // metres

/* ── Map ─────────────────────────────────────────────────────────────────── */
<?php if ($order && ($pLat || $dLat)): ?>
var map = L.map('courierMap',{zoomControl:false,attributionControl:false,dragging:false,touchZoom:true}).setView([41.6938,44.8015],13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
var bounds = [];
<?php if ($pLat && $pLng): ?>
var puMk = L.marker([<?= (float)$pLat ?>,<?= (float)$pLng ?>],{
  icon:L.divIcon({html:'<div style="font-size:26px">🏪</div>',iconSize:[32,32],iconAnchor:[16,16],className:''})
}).bindPopup('ობიექტი').addTo(map);
bounds.push([<?= (float)$pLat ?>,<?= (float)$pLng ?>]);
<?php endif ?>
<?php if ($dLat && $dLng): ?>
var dsMk = L.marker([<?= (float)$dLat ?>,<?= (float)$dLng ?>],{
  icon:L.divIcon({html:'<div style="font-size:26px">🏠</div>',iconSize:[32,32],iconAnchor:[16,16],className:''})
}).bindPopup('მომხმარებელი').addTo(map);
bounds.push([<?= (float)$dLat ?>,<?= (float)$dLng ?>]);
<?php endif ?>
if(bounds.length>1) map.fitBounds(bounds,{padding:[24,24]});
else if(bounds.length===1) map.setView(bounds[0],15);
<?php endif ?>

/* ── Driver GPS → server + proximity gate ───────────────────────────────── */
var drMarker = null;

function updateProximity(lat, lng) {
    if (ORDER_STATUS !== 'accepted' || VENDOR_LAT === null || VENDOR_LNG === null) return;
    var dist   = haversine(lat, lng, VENDOR_LAT, VENDOR_LNG);
    var inZone = dist <= PICKUP_RADIUS;
    var btn  = document.getElementById('mainActBtn');
    var dot  = document.getElementById('proxDot');
    var txt  = document.getElementById('proxTxt');
    var row  = document.getElementById('proxRow');
    if (btn) btn.disabled = !inZone;
    if (dot) dot.style.background = inZone ? '#22c55e' : '#f59e0b';
    if (txt) {
        if (inZone) {
            txt.textContent = '✓ ობიექტთან ხართ — შეიძლება აღება';
            if (row) row.style.color = '#166534';
        } else {
            var m = Math.round(dist);
            txt.textContent = '📍 ობიექტამდე ~' + m + 'მ — მიახლოვდით (<200მ)';
            if (row) row.style.color = 'var(--muted)';
        }
    }
}

if(navigator.geolocation){
    navigator.geolocation.watchPosition(function(pos){
        var lat=pos.coords.latitude, lng=pos.coords.longitude;
        <?php if ($order && ($pLat || $dLat)): ?>
        if(!drMarker){
            drMarker = L.marker([lat,lng],{icon:L.divIcon({html:'<div style="font-size:30px">🛵</div>',iconSize:[36,36],iconAnchor:[18,18],className:''})}).addTo(map);
        } else { drMarker.setLatLng([lat,lng]); }
        <?php endif ?>
        updateProximity(lat, lng);
        fetch(BASE+'/api/delivery/courier/'+TOKEN+'/location',{
            method:'POST', headers:{'Content-Type':'application/json'},
            body:JSON.stringify({lat:lat,lng:lng})
        }).catch(function(){});
    }, function(err){
        console.warn('Geolocation error:', err.code, err.message);
        // GPS unavailable — unlock pickup button so courier isn't permanently blocked
        var btn = document.getElementById('mainActBtn');
        var txt = document.getElementById('proxTxt');
        var dot = document.getElementById('proxDot');
        if (btn) btn.disabled = false;
        if (dot) dot.style.background = '#94a3b8';
        if (txt) txt.textContent = '⚠ GPS მიუწვდომელია — ხელით შეგიძლიათ';
    }, {enableHighAccuracy:true, maximumAge:8000, timeout:15000});
}

/* ── Toast ───────────────────────────────────────────────────────────────── */
function toast(msg,isErr){
    var t=document.getElementById('toast');
    t.textContent=msg;t.className='toast'+(isErr?' error':'')+' show';
    setTimeout(function(){t.className='toast'+(isErr?' error':'');},3000);
}

/* ── Active order status ─────────────────────────────────────────────────── */
function updateStatus(status, btn){
    if(!ORDER_ID) return;
    btn.disabled=true;
    btn.innerHTML='<span class="spinner"></span>';
    fetch(BASE+'/api/delivery/courier/'+TOKEN+'/order/'+ORDER_ID+'/status',{
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({status:status})
    }).then(function(){ location.reload(); })
    .catch(function(){ toast('შეცდომა',true); btn.disabled=false; });
}

/* ── Haversine ───────────────────────────────────────────────────────────── */
function haversine(lat1,lng1,lat2,lng2){
    var R=6371000,toR=Math.PI/180;
    var dLat=(lat2-lat1)*toR,dLng=(lng2-lng1)*toR;
    var a=Math.sin(dLat/2)*Math.sin(dLat/2)+Math.cos(lat1*toR)*Math.cos(lat2*toR)*Math.sin(dLng/2)*Math.sin(dLng/2);
    return 2*R*Math.asin(Math.sqrt(a));
}

/* ── Offer overlay ───────────────────────────────────────────────────────── */
var currentOffer  = <?= $hasOffer ? json_encode(['id'=>$offerId,'expires_at'=>$offer['expires_at'],'seconds_left'=>$offerSec,'order'=>$offerOrder]) : 'null' ?>;
var offerTimerIv  = null;
if(currentOffer && currentOffer.seconds_left > 0){ startOfferTimer(currentOffer.seconds_left); }

function showOffer(offerData){
    var o = offerData.order;
    currentOffer = offerData;
    // Use server-supplied seconds_left to avoid timezone parsing issues with date strings
    var secLeft = (typeof offerData.seconds_left !== 'undefined')
        ? offerData.seconds_left
        : Math.round((new Date(offerData.expires_at).getTime()-Date.now())/1000);
    // If offer already expired by the time it arrived, skip — next poll will send a fresh one
    if(secLeft <= 0){ return; }

    document.getElementById('offerNum').textContent      = o.order_number || '';
    document.getElementById('offerPickup').textContent   = (o.vendor&&o.vendor.address) ? o.vendor.address : (o.pickup_address||'');
    document.getElementById('offerDeliver').textContent  = o.delivery_address || '';
    document.getElementById('offerTotal').textContent    = parseFloat(o.price||0).toFixed(2)+SYM;

    var distEl=document.getElementById('offerDist'), etaEl=document.getElementById('offerEta');
    if(o.vendor&&o.vendor.lat&&o.delivery_lat){
        var dm=haversine(parseFloat(o.vendor.lat),parseFloat(o.vendor.lng),parseFloat(o.delivery_lat),parseFloat(o.delivery_lng));
        distEl.textContent=(dm/1000).toFixed(1)+' კმ';
        etaEl.textContent='~'+Math.max(1,Math.ceil(dm/416))+' წთ';
    } else { distEl.textContent='—'; etaEl.textContent='—'; }

    startOfferTimer(secLeft);
    document.getElementById('offerOverlay').classList.remove('hidden');
}

function hideOffer(){
    document.getElementById('offerOverlay').classList.add('hidden');
    clearInterval(offerTimerIv); offerTimerIv=null; currentOffer=null;
}

function startOfferTimer(sec){
    clearInterval(offerTimerIv);
    var remaining=sec, total=30;
    function tick(){
        var timerEl=document.getElementById('offerTimer'), barEl=document.getElementById('offerBar');
        if(!timerEl) return;
        timerEl.textContent=remaining;
        barEl.style.width=Math.max(0,remaining/total*100)+'%';
        var urgent=remaining<=8;
        timerEl.classList.toggle('urgent',urgent);
        barEl.classList.toggle('urgent',urgent);
        if(remaining<=0){ clearInterval(offerTimerIv); hideOffer(); return; }
        remaining--;
    }
    tick(); offerTimerIv=setInterval(tick,1000);
}

function acceptOffer(){
    if(!currentOffer) return;
    var id=currentOffer.id; hideOffer();
    fetch(BASE+'/api/delivery/courier/'+TOKEN+'/offer/'+id+'/accept',{method:'POST'})
        .then(function(r){return r.json();})
        .then(function(d){ if(d.ok){location.reload();}else{toast('შეცდომა: '+(d.error||''),true);} })
        .catch(function(){location.reload();});
}

function declineOffer(){
    if(!currentOffer) return;
    var id=currentOffer.id; hideOffer();
    fetch(BASE+'/api/delivery/courier/'+TOKEN+'/offer/'+id+'/decline',{method:'POST'}).catch(function(){});
}

/* ── Online / Offline toggle ────────────────────────────────────────────── */
function updateOnlineUI(){
    var dot = document.getElementById('onlineDot');
    var lbl = document.getElementById('onlineLbl');
    if(dot){
        dot.style.background  = IS_ONLINE ? '#22c55e' : '#64748b';
        dot.style.animation   = IS_ONLINE ? 'lb 2s infinite' : 'none';
    }
    if(lbl){
        lbl.textContent  = IS_ONLINE ? 'Online' : 'Offline';
        lbl.style.color  = IS_ONLINE ? '#86efac' : '#64748b';
    }
}

function toggleOnline(){
    var btn = document.getElementById('onlineToggleBtn');
    if(btn) btn.style.opacity = '.5';
    fetch(BASE+'/api/delivery/courier/'+TOKEN+'/online', {method:'POST'})
        .then(function(r){return r.json();})
        .then(function(d){
            if(btn) btn.style.opacity = '1';
            if(d.ok){ IS_ONLINE = d.is_online; updateOnlineUI(); }
        }).catch(function(){ if(btn) btn.style.opacity='1'; });
}

/* ── Polling every 5 s ───────────────────────────────────────────────────── */
var currentOrderStatus = <?= $order ? json_encode($order['status']) : 'null' ?>;

function doPoll(){
    fetch(BASE+'/api/delivery/courier/'+TOKEN+'/poll')
        .then(function(r){return r.json();})
        .then(function(d){
            if(!d.ok) return;
            if(typeof d.is_online !== 'undefined' && d.is_online !== IS_ONLINE){
                IS_ONLINE = d.is_online;
                updateOnlineUI();
            }
            if(d.offer && (!currentOffer||currentOffer.id!==d.offer.id)) showOffer(d.offer);
            if(!d.offer && currentOffer) hideOffer();
            if(d.active_order && !ORDER_ID) { location.reload(); return; }
            if(d.active_order && ORDER_ID && d.active_order.status !== currentOrderStatus) location.reload();
        }).catch(function(){});
}
setInterval(doPoll, 5000);
</script>
</body>
</html>
