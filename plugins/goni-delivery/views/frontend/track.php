<?php
$sym    = $sym    ?? '₾';
$slug   = $slug   ?? 'delivery';

/**
 * Maps (order_status + vendor_status) → one of the 6 display steps.
 *
 * IMPORTANT: vendor portal only updates vendor_status, never order_status.
 * order_status stays 'pending' until the courier picks up the order.
 * So early-stage progress is driven entirely by vendor_status.
 */
function gdtDisplayStep(string $os, string $vs): string {
    // Courier/delivery stages take hard priority
    if (in_array($os, ['picked_up','in_transit'], true)) return 'in_transit';
    if ($os === 'delivered')                             return 'delivered';
    if ($os === 'cancelled')                             return 'cancelled';
    // Pre-courier stages: read vendor_status regardless of order_status
    if ($vs === 'ready')                                 return 'ready';
    if ($vs === 'preparing')                             return 'preparing';
    if ($vs === 'accepted')                              return 'accepted';
    return 'pending';
}

// 'pickup' is a location-driven step injected between 'ready' and 'in_transit'.
// It is always hidden on initial PHP render; JS shows/updates it based on courier GPS.
$steps   = ['pending','accepted','preparing','ready','pickup','in_transit','delivered'];
$sLabels = ['pending'=>'მოლოდინი','accepted'=>'მიღებულია','preparing'=>'მზადდება','ready'=>'მზადაა','pickup'=>'ასაღებად','in_transit'=>'გზაშია','delivered'=>'ჩაბარდა'];
$sIcons  = ['pending'=>'⏳','accepted'=>'✅','preparing'=>'🍳','ready'=>'🔔','pickup'=>'🛵','in_transit'=>'🚗','delivered'=>'🏁'];

$displayStep = gdtDisplayStep($order['status'], $order['vendor_status'] ?? '');

// Progress % is computed on the 6 always-visible steps (pickup excluded until JS activates it)
$_visSteps   = array_values(array_filter($steps, fn($s) => $s !== 'pickup'));
$_visCurIdx  = array_search($displayStep, $_visSteps, true);
$curIdx      = array_search($displayStep, $steps, true);

$sc      = $delivery->statusColor($order['status']);
$scRgb   = implode(',', array_map('hexdec', str_split(ltrim($sc, '#'), 2)));
$isCancelled = $order['status'] === 'cancelled';
$isDelivered = $order['status'] === 'delivered';
$progressPct = ($_visCurIdx !== false && count($_visSteps) > 1)
    ? round($_visCurIdx / (count($_visSteps)-1) * 100)
    : 0;

$heroIcons  = ['pending'=>'⏳','accepted'=>'✅','preparing'=>'🍳','ready'=>'🔔','in_transit'=>'🚗','delivered'=>'🏁','cancelled'=>'❌'];
$heroLabels = ['pending'=>'მოლოდინში','accepted'=>'მიღებულია','preparing'=>'მზადდება','ready'=>'მზადაა!','in_transit'=>'გზაშია','delivered'=>'დასრულებული','cancelled'=>'გაუქმდა'];

$payPaid = $order['payment_status'] === 'paid';
$payMethod = match($order['payment_method'] ?? 'cash') {
    'cash'  => '💵 ნაღდი',
    'card'  => '💳 ბარათი',
    'bog'   => '💳 BOG Pay',
    default => ucfirst($order['payment_method'] ?? 'cash'),
};
?>
<style>
/* ─── Reset & base ──────────────────────────────────────────── */
.gdt *{box-sizing:border-box}
.gdt{
  --p:#f59e0b;--pg:#ef4444;--done:#10b981;--blue:#3b82f6;
  --text:#0f172a;--muted:#64748b;--border:#e2e8f0;
  --bg:#f1f5f9;--card:#fff;
  font-family:'Inter',system-ui,sans-serif;
  background:var(--bg);min-height:100vh;color:var(--text);
}
/* hide site chrome */
.gc-header,.gc-footer,footer,header,nav.site-nav{display:none!important}
.gc-main{padding:0!important;margin:0!important;max-width:none!important;width:100%!important}
body{background:#f1f5f9!important}

/* ─── Layout ────────────────────────────────────────────────── */
.gdt-wrap{max-width:560px;margin:0 auto;padding:0 0 60px}

/* ─── Top status hero ───────────────────────────────────────── */
.gdt-hero{
  background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);
  color:#fff;padding:36px 24px 32px;text-align:center;
  position:relative;overflow:hidden;
}
.gdt-hero::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse at 60% 20%,rgba(<?= $scRgb ?>,.25) 0%,transparent 70%);
  pointer-events:none;
}
.gdt-hero-icon{font-size:58px;line-height:1;margin-bottom:14px;position:relative}
.gdt-delivered-badge{font-size:30px;margin-top:6px;line-height:1;animation:gdtFadeIn .4s ease}
.gdt-hero-status{font-size:21px;font-weight:900;margin-bottom:6px;position:relative}
.gdt-hero-num{font-family:monospace;font-size:13px;color:rgba(255,255,255,.5);letter-spacing:2px;position:relative}
.gdt-hero-time{font-size:12px;color:rgba(255,255,255,.45);margin-top:6px;position:relative}

/* ─── Progress stepper ──────────────────────────────────────── */
.gdt-steps-card{background:var(--card);padding:24px 16px 20px;border-bottom:1px solid var(--border)}
.gdt-stepper{display:flex;align-items:flex-start;position:relative;padding:0 8px}
.gdt-line-bg{position:absolute;top:16px;left:10%;right:10%;height:3px;background:var(--border);border-radius:2px;z-index:0}
.gdt-line-fill{position:absolute;top:16px;left:10%;height:3px;background:var(--done);border-radius:2px;z-index:1;transition:width .6s ease}
.gdt-step{flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;position:relative;z-index:2}
.gdt-dot{
  width:32px;height:32px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:13px;font-weight:700;
  background:var(--border);color:var(--muted);border:2px solid var(--border);
  transition:all .3s;
}
.gdt-step.done .gdt-dot{background:var(--done);border-color:var(--done);color:#fff}
.gdt-step.current .gdt-dot{
  background:var(--p);border-color:var(--p);color:#fff;
  box-shadow:0 0 0 5px rgba(245,158,11,.18);
  animation:gdt-pulse 1.8s ease-in-out infinite;
}
@keyframes gdt-pulse{0%,100%{box-shadow:0 0 0 0 rgba(245,158,11,.3)}50%{box-shadow:0 0 0 8px rgba(245,158,11,0)}}
.gdt-step-lbl{font-size:10px;font-weight:600;text-align:center;color:var(--muted);line-height:1.3;max-width:56px}
.gdt-step.done .gdt-step-lbl,.gdt-step.current .gdt-step-lbl{color:var(--text)}

/* ─── Sections ──────────────────────────────────────────────── */
.gdt-section{background:var(--card);margin-top:10px}
.gdt-sec-head{
  padding:14px 20px;border-bottom:1px solid var(--border);
  font-size:12px;font-weight:800;text-transform:uppercase;
  letter-spacing:.5px;color:var(--muted);display:flex;align-items:center;gap:6px;
}
.gdt-row{display:flex;justify-content:space-between;align-items:center;padding:11px 20px;border-bottom:1px solid #f8fafc;font-size:13.5px;gap:12px}
.gdt-row:last-child{border-bottom:none}
.gdt-row-lbl{color:var(--muted);flex-shrink:0}
.gdt-row-val{font-weight:600;text-align:right;word-break:break-word}

/* ─── Items ──────────────────────────────────────────────────── */
.gdt-item{display:flex;justify-content:space-between;align-items:flex-start;padding:11px 20px;border-bottom:1px solid #f8fafc;gap:10px}
.gdt-item:last-child{border-bottom:none}
.gdt-item-name{font-size:13.5px;font-weight:700;flex:1}
.gdt-item-qty{font-size:12px;color:var(--muted);font-weight:600;margin-top:2px}
.gdt-item-mod{font-size:11px;color:var(--muted);margin-top:3px;line-height:1.4}
.gdt-item-price{font-size:14px;font-weight:800;color:var(--text);white-space:nowrap}

/* ─── Totals ──────────────────────────────────────────────────── */
.gdt-totals{padding:4px 0}
.gdt-total-row{display:flex;justify-content:space-between;padding:9px 20px;font-size:13.5px}
.gdt-total-row.big{border-top:1.5px solid var(--border);margin-top:4px;padding-top:13px;font-size:16px;font-weight:900;color:var(--text)}
.gdt-total-row .gdt-row-lbl{color:var(--muted)}

/* ─── Vendor strip ────────────────────────────────────────────── */
.gdt-vendor{display:flex;align-items:center;gap:14px;padding:16px 20px}
.gdt-vendor-logo{width:48px;height:48px;border-radius:12px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:22px;overflow:hidden;flex-shrink:0;border:1.5px solid var(--border)}
.gdt-vendor-logo img{width:100%;height:100%;object-fit:cover}
.gdt-vendor-name{font-size:15px;font-weight:800}
.gdt-vendor-sub{font-size:12px;color:var(--muted);margin-top:2px}

/* ─── Payment badge ───────────────────────────────────────────── */
.gdt-pay-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:700}
.gdt-pay-paid{background:#dcfce7;color:#15803d}
.gdt-pay-unpaid{background:#fef9c3;color:#92400e}

/* ─── Cancelled ────────────────────────────────────────────────── */
.gdt-cancelled{background:#fff;padding:36px 24px;text-align:center}
.gdt-cancelled-icon{font-size:64px;margin-bottom:16px}
.gdt-cancelled-title{font-size:20px;font-weight:900;color:#ef4444;margin-bottom:8px}
.gdt-cancelled-sub{font-size:14px;color:var(--muted)}

/* ─── Note ────────────────────────────────────────────────────── */
.gdt-note{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 16px;margin:12px 20px;font-size:13px;color:#92400e;line-height:1.5}

/* ─── Bottom actions ──────────────────────────────────────────── */
.gdt-actions{padding:20px 16px 0;display:flex;flex-direction:column;gap:10px}
.gdt-btn{display:block;width:100%;padding:15px;border-radius:14px;font-size:15px;font-weight:800;text-align:center;cursor:pointer;border:none;font-family:inherit;text-decoration:none}
.gdt-btn-primary{background:linear-gradient(135deg,var(--p),var(--pg));color:#fff;box-shadow:0 4px 18px rgba(245,158,11,.35)}
.gdt-btn-secondary{background:var(--card);color:var(--text);border:1.5px solid var(--border)}


/* ─── Courier message ──────────────────────────────────────────── */
.gdt-courier-msg{display:none;margin-top:10px;background:var(--card)}
.gdt-courier-msg.show{display:block}
.gdt-courier-msg-inner{padding:16px 20px;display:flex;align-items:center;gap:14px;border-left:4px solid var(--blue)}
.gdt-courier-msg.arrived .gdt-courier-msg-inner{border-color:var(--done)}
.gdt-courier-msg-icon{font-size:36px;flex-shrink:0}
.gdt-courier-msg-text{font-size:14px;font-weight:800;color:var(--text);line-height:1.4}
.gdt-courier-msg-sub{font-size:12px;color:var(--muted);margin-top:3px}

/* ─── Map ──────────────────────────────────────────────────────── */
.gdt-map-section{display:none;margin-top:10px;background:var(--card);overflow:hidden}
.gdt-map-section.show{display:block}
#gdtMap{height:280px;width:100%}

/* ─── Hero status animations ─────────────────────────────────── */
@keyframes gdt-wait   {0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
@keyframes gdt-accept {0%,100%{transform:scale(1)}50%{transform:scale(1.16)}}
@keyframes gdt-cook   {0%,100%{transform:rotate(-7deg)}33%{transform:rotate(7deg) translateY(-4px)}66%{transform:rotate(-5deg) translateY(-2px)}}
@keyframes gdt-drive  {0%,100%{transform:translateX(-8px) rotate(-2deg)}50%{transform:translateX(8px) rotate(2deg)}}
@keyframes gdt-party  {0%,100%{transform:scale(1) rotate(0)}25%{transform:scale(1.35) rotate(-14deg)}75%{transform:scale(1.2) rotate(11deg)}}
.gdt-anim-pending    {animation:gdt-wait   2.5s ease-in-out infinite;display:inline-block}
.gdt-anim-accepted   {animation:gdt-accept 2s   ease-in-out infinite;display:inline-block}
.gdt-anim-preparing  {animation:gdt-cook   0.75s ease-in-out infinite;display:inline-block}
.gdt-anim-in_transit {animation:gdt-drive  1s   ease-in-out infinite;display:inline-block}
.gdt-anim-delivered  {animation:gdt-party  1.5s ease-in-out infinite;display:inline-block}
.gdt-anim-ready      {animation:gdt-bell   1.4s ease-in-out infinite;display:inline-block;transform-origin:50% 0%}
.gdt-anim-cancelled  {display:inline-block;opacity:.55}
@keyframes gdt-bell  {0%,100%{transform:rotate(0)}20%{transform:rotate(-22deg)}40%{transform:rotate(22deg)}60%{transform:rotate(-14deg)}80%{transform:rotate(14deg)}}

/* ─── Prep / ETA strip ───────────────────────────────────────── */
.gdt-prep-strip{text-align:center;margin-top:14px;font-size:12px;color:var(--muted);line-height:1.8}
.gdt-prep-val{font-size:18px;font-weight:900;font-variant-numeric:tabular-nums;letter-spacing:1px;display:inline-block;min-width:56px;color:var(--text);transition:color .3s}
.gdt-prep-val.overdue{color:#ef4444}
.gdt-prep-val.eta    {color:var(--blue)}
</style>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<div class="gdt">
<div class="gdt-wrap">


<?php /* ── Hero ── */ ?>
<div class="gdt-hero">
  <div class="gdt-hero-icon" id="gdtHeroIcon">
    <span class="gdt-anim-<?= $displayStep ?>" id="gdtHeroAnim"><?= $heroIcons[$displayStep] ?? '⏳' ?></span>
    <div id="gdtDeliveredBadge" class="gdt-delivered-badge"<?= $displayStep !== 'delivered' ? ' style="display:none"' : '' ?>>✅</div>
  </div>
  <div class="gdt-hero-status" id="gdtHeroStatus">
    <?= $heroLabels[$displayStep] ?? e($order['status']) ?>
  </div>
  <div class="gdt-hero-num"><?= e($order['order_number']) ?></div>
  <div class="gdt-hero-time">📅 <?= date('d.m.Y · H:i', strtotime($order['created_at'])) ?></div>
</div>

<?php /* ── Progress stepper ── */ ?>
<?php if(!$isCancelled): ?>
<div class="gdt-steps-card">
  <div class="gdt-stepper">
    <div class="gdt-line-bg"></div>
    <div class="gdt-line-fill" id="gdtLineFill" style="width:calc(80% * <?= $progressPct ?> / 100)"></div>
    <?php foreach($steps as $i => $step):
      $cls = '';
      if($step === 'pickup'){
        // pickup step: always hidden initially; JS shows it when courier has location
      } elseif($curIdx !== false){
        if($i < $curIdx) $cls = 'done';
        elseif($i === $curIdx) $cls = 'current';
      }
      $hidden = $step === 'pickup' ? ' style="display:none"' : '';
    ?>
    <div class="gdt-step <?= $cls ?>"<?= $hidden ?> id="gdtStep_<?= $step ?>">
      <div class="gdt-dot" <?= $step==='pickup' ? 'id="gdtPickupDot"' : '' ?>><?= $cls==='done' ? '✓' : $sIcons[$step] ?></div>
      <div class="gdt-step-lbl" <?= $step==='pickup' ? 'id="gdtPickupLbl"' : '' ?>><?= $sLabels[$step] ?></div>
    </div>
    <?php endforeach ?>
  </div>
  <?php if(!$isDelivered && !$isCancelled && ($order['prep_time_minutes'] ?? 0)): ?>
  <div class="gdt-prep-strip" id="gdtPrepWrap">
    <span id="gdtPrepIcon">⏱</span>
    <span id="gdtPrepLabel">სავარაუდო მომზადება:</span><br>
    <span class="gdt-prep-val" id="gdtPrepVal"><?= (int)$order['prep_time_minutes'] ?> წთ</span>
  </div>
  <?php endif ?>
</div>
<?php else: ?>
<div class="gdt-cancelled">
  <div class="gdt-cancelled-icon">❌</div>
  <div class="gdt-cancelled-title">შეკვეთა გაუქმდა</div>
  <div class="gdt-cancelled-sub">სამწუხაროდ თქვენი შეკვეთა გაუქმდა.<br>კითხვის შემთხვევაში დაგვიკავშირდით.</div>
</div>
<?php endif ?>

<?php /* ── Courier message ── */ ?>
<div class="gdt-courier-msg" id="gdtCourierMsg">
  <div class="gdt-courier-msg-inner">
    <div class="gdt-courier-msg-icon" id="gdtCourierMsgIcon">🛵</div>
    <div>
      <div class="gdt-courier-msg-text" id="gdtCourierMsgText">კურიერი მიდის შეკვეთის ასაღებად</div>
      <div class="gdt-courier-msg-sub"  id="gdtCourierMsgSub">შეკვეთა გამზადებულია</div>
    </div>
  </div>
</div>

<?php /* ── Live map ── */ ?>
<div class="gdt-map-section" id="gdtMapSection">
  <div id="gdtMap"></div>
</div>

<?php /* ── Vendor info ── */ ?>
<?php if($vendor): ?>
<div class="gdt-section" style="margin-top:10px">
  <div class="gdt-vendor">
    <div class="gdt-vendor-logo">
      <?php if(!empty($vendor['logo'])): ?>
      <img src="<?= e($vendor['logo']) ?>" alt="<?= e($vendor['name']) ?>">
      <?php else: ?>
      <?= match($vendor['category']??'restaurant'){'grocery'=>'🛒','pharmacy'=>'💊','cafe'=>'☕','bakery'=>'🥐',default=>'🍽'} ?>
      <?php endif ?>
    </div>
    <div>
      <div class="gdt-vendor-name"><?= e($vendor['name']) ?></div>
      <div class="gdt-vendor-sub">📍 <?= e($order['pickup_address'] ?: ($vendor['address'] ?? '')) ?></div>
    </div>
  </div>
</div>
<?php endif ?>

<?php /* ── Order items ── */ ?>
<?php if(!empty($order['items'])): ?>
<div class="gdt-section" style="margin-top:10px">
  <div class="gdt-sec-head">🛍 შეკვეთა</div>
  <?php foreach($order['items'] as $item):
    $extras = !empty($item['modifiers_json']) ? json_decode($item['modifiers_json'], true) : [];
    $modifiers = $extras['modifiers'] ?? [];
    $combos    = $extras['combos'] ?? [];
    $trSizeMod   = null;
    $trOtherMods = [];
    foreach($modifiers as $mod){
        if(($mod['group_type'] ?? '') === 'size') $trSizeMod = $mod;
        else $trOtherMods[] = $mod;
    }
  ?>
  <div class="gdt-item">
    <div style="flex:1">
      <?php if($trSizeMod): ?>
      <div style="font-size:10px;font-weight:800;background:#dcfce7;color:#166534;border-radius:6px;padding:1px 8px;display:inline-block;margin-bottom:3px;letter-spacing:.3px">📏 <?= e($trSizeMod['name']) ?><?= (float)($trSizeMod['price']??0)>0?' (+'.number_format((float)$trSizeMod['price'],2).$sym.')':'' ?></div>
      <?php endif ?>
      <div class="gdt-item-name"><?= e($item['name']) ?> <span class="gdt-item-qty">× <?= (int)$item['quantity'] ?></span></div>
      <?php foreach($combos as $cb):
        $cbSels = $cb['selections'] ?? [];
        if(!$cbSels) continue;
        $cbType = $cb['type'] ?? 'choice';
      ?>
        <?php if($cbType !== 'included'): ?>
        <div class="gdt-item-mod" style="font-weight:700;margin-top:3px"><?= e($cb['name'] ?? '') ?></div>
        <?php endif ?>
        <?php foreach($cbSels as $s): ?>
        <div class="gdt-item-mod" style="display:flex;align-items:baseline;gap:4px;padding-left:<?= $cbType!=='included'?'10':'0'?>px">
          <span style="color:#d1d5db;font-size:10px;flex-shrink:0">▸</span><?= e($s['name'] ?? '') ?>
        </div>
        <?php endforeach ?>
      <?php endforeach ?>
      <?php foreach($trOtherMods as $mod): ?>
        <div class="gdt-item-mod" style="display:flex;align-items:baseline;gap:4px">
          <span style="color:#d1d5db;font-size:10px;flex-shrink:0">✕</span><?= e($mod['name'] ?? '') ?><?= (float)($mod['price']??0) > 0 ? ' <span style="color:#f59e0b;margin-left:3px">(+'.number_format((float)$mod['price'],2).$sym.')</span>' : '' ?>
        </div>
      <?php endforeach ?>
    </div>
    <div class="gdt-item-price"><?= number_format((float)$item['item_total'], 2) ?><?= $sym ?></div>
  </div>
  <?php endforeach ?>

  <?php if($order['customer_note']): ?>
  <div class="gdt-note">📝 <?= e($order['customer_note']) ?></div>
  <?php endif ?>

  <div class="gdt-totals">
    <?php
      $subtotal = (float)($order['subtotal'] ?? 0);
      $delfee   = (float)($order['delivery_fee'] ?? 0);
      $total    = (float)($order['price'] ?? 0);
    ?>
    <?php if($subtotal > 0): ?>
    <div class="gdt-total-row"><span class="gdt-row-lbl">📦 პროდუქტები</span><span><?= number_format($subtotal,2).$sym ?></span></div>
    <?php endif ?>
    <?php if($delfee > 0): ?>
    <div class="gdt-total-row"><span class="gdt-row-lbl">🚚 მიწოდება</span><span><?= number_format($delfee,2).$sym ?></span></div>
    <?php endif ?>
    <div class="gdt-total-row big"><span>სულ</span><span style="color:var(--p)"><?= number_format($total,2).$sym ?></span></div>
  </div>
</div>
<?php endif ?>

<?php /* ── Delivery details ── */ ?>
<div class="gdt-section" style="margin-top:10px">
  <div class="gdt-sec-head">📋 დეტალები</div>
  <div class="gdt-row">
    <span class="gdt-row-lbl">📍 მიწოდება</span>
    <span class="gdt-row-val"><?= e($order['delivery_address']) ?></span>
  </div>
  <?php if($order['driver'] ?? null): ?>
  <div class="gdt-row">
    <span class="gdt-row-lbl">🏍 კურიერი</span>
    <span class="gdt-row-val">
      <?= e($order['driver']['name'] ?? '') ?>
      <?php if(!empty($order['driver']['phone'])): ?>
      · <a href="tel:<?= e($order['driver']['phone']) ?>" style="color:var(--blue)"><?= e($order['driver']['phone']) ?></a>
      <?php endif ?>
    </span>
  </div>
  <?php endif ?>
  <div class="gdt-row">
    <span class="gdt-row-lbl">💳 გადახდა</span>
    <span class="gdt-row-val">
      <?= $payMethod ?>
      <span class="gdt-pay-badge <?= $payPaid?'gdt-pay-paid':'gdt-pay-unpaid' ?>" style="margin-left:6px">
        <?= $payPaid ? '✓ გადახდილი' : '⏳ გადასახდელი' ?>
      </span>
    </span>
  </div>
</div>

<?php /* ── Bottom buttons ── */ ?>
<div class="gdt-actions">
  <a href="<?= e($base) ?>/<?= e($slug) ?>" class="gdt-btn gdt-btn-primary">+ ახალი შეკვეთა</a>
  <?php if($delivery->setting('phone')): ?>
  <a href="tel:<?= e($delivery->setting('phone')) ?>" class="gdt-btn gdt-btn-secondary">📞 დაგვიკავშირდით</a>
  <?php endif ?>
</div>

</div><!-- /gdt-wrap -->
</div><!-- /gdt -->

<?php if(!$isCancelled): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
var BASE         = <?= json_encode($base) ?>;
var TRACK_TOKEN  = <?= json_encode($order['track_token'] ?? '') ?>;
var INIT_STATUS  = <?= json_encode($order['status']) ?>;
var INIT_VSTATUS = <?= json_encode($order['vendor_status'] ?? 'pending') ?>;
var PICKUP_LAT   = <?= json_encode($order['pickup_lat']   ? (float)$order['pickup_lat']   : null) ?>;
var PICKUP_LNG   = <?= json_encode($order['pickup_lng']   ? (float)$order['pickup_lng']   : null) ?>;
var DELIVERY_LAT = <?= json_encode($order['delivery_lat'] ? (float)$order['delivery_lat'] : null) ?>;
var DELIVERY_LNG = <?= json_encode($order['delivery_lng'] ? (float)$order['delivery_lng'] : null) ?>;
var TRACK_API    = BASE + '/api/delivery/track/' + TRACK_TOKEN + '/status';
var HAS_DRIVER   = <?= json_encode(!empty($order['driver_id'])) ?>;

var currentStatus  = INIT_STATUS;
var currentVStatus = INIT_VSTATUS;
var gdtMap = null, courierMarker = null;

/* ── Countdown ── */
var PREP_MS = <?= (int)($order['prep_time_minutes'] ?? 0) ?> * 60 * 1000;
/* prep_ends_at is written by the server the moment the vendor sets prep time,
   so it is timezone-safe (server clock used for both write and read).          */
var END_MS  = <?= !empty($order['prep_ends_at'])
    ? json_encode((int)(strtotime($order['prep_ends_at']) * 1000))
    : '0' ?>;
if (!END_MS && PREP_MS) {
    /* Fallback for orders created before prep_ends_at column existed */
    END_MS = <?= json_encode((int)(strtotime($order['created_at']) * 1000)) ?> + PREP_MS;
}
var cdIv = null;

/* ── 7-step display steps (must match PHP $steps array) ── */
/* 'pickup' is location-driven and hidden until courier GPS is available */
var STEPS      = ['pending','accepted','preparing','ready','pickup','in_transit','delivered'];
var STEP_ICONS = ['⏳','✅','🍳','🔔','🛵','🚗','🏁'];

/* ── Hero icon/label per display step ── */
var HERO_ICONS  = {pending:'⏳',accepted:'✅',preparing:'🍳',ready:'🔔',in_transit:'🚗',delivered:'🏁',cancelled:'❌'};
var HERO_LABELS = {pending:'მოლოდინში',accepted:'მიღებულია',preparing:'მზადდება',ready:'მზადაა!',in_transit:'გზაშია',delivered:'დასრულებული',cancelled:'გაუქმდა'};

/* ── Display step: combines order_status + vendor_status ── */
function getDisplayStep(os, vs){
  // Courier stages take hard priority
  if(os==='picked_up'||os==='in_transit') return 'in_transit';
  if(os==='delivered') return 'delivered';
  if(os==='cancelled') return 'cancelled';
  // Pre-courier: driven by vendor_status (order_status stays 'pending' the whole time)
  if(vs==='ready')     return 'ready';
  if(vs==='preparing') return 'preparing';
  if(vs==='accepted')  return 'accepted';
  return 'pending';
}

/* ── Haversine distance (meters) ── */
function haversine(lat1,lng1,lat2,lng2){
  var R=6371000,dLat=(lat2-lat1)*Math.PI/180,dLng=(lng2-lng1)*Math.PI/180;
  var a=Math.sin(dLat/2)*Math.sin(dLat/2)+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)*Math.sin(dLng/2);
  return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}

/* ── ETA estimate: distance / 25 km/h ≈ 416 m/min ── */
function etaMinutes(courierLat, courierLng, destLat, destLng){
  if(!courierLat||!courierLng||!destLat||!destLng) return null;
  var dist = haversine(courierLat,courierLng,destLat,destLng);
  return Math.max(1, Math.ceil(dist/416));
}

/* ── Map init ── */
function initMap(){
  if(gdtMap) return;
  var el = document.getElementById('gdtMap');
  if(!el) return;
  var center = PICKUP_LAT ? [PICKUP_LAT,PICKUP_LNG] : [41.6938,44.8015];
  gdtMap = L.map('gdtMap',{zoomControl:true,attributionControl:false}).setView(center,15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(gdtMap);
  if(PICKUP_LAT)
    L.marker([PICKUP_LAT,PICKUP_LNG],{icon:L.divIcon({html:'<div style="font-size:28px">🏪</div>',iconSize:[36,36],iconAnchor:[18,18],className:''})})
     .bindPopup('ობიექტი').addTo(gdtMap);
  if(DELIVERY_LAT)
    L.marker([DELIVERY_LAT,DELIVERY_LNG],{icon:L.divIcon({html:'<div style="font-size:28px">🏠</div>',iconSize:[36,36],iconAnchor:[18,18],className:''})})
     .bindPopup('თქვენი მისამართი').addTo(gdtMap);
}

function updateCourierMarker(lat,lng){
  if(!gdtMap) initMap();
  if(!courierMarker){
    courierMarker = L.marker([lat,lng],{icon:L.divIcon({html:'<div style="font-size:36px;filter:drop-shadow(0 2px 8px rgba(0,0,0,.55))">🛵</div>',iconSize:[44,44],iconAnchor:[22,22],className:''})})
      .bindPopup('კურიერი').addTo(gdtMap);
    gdtMap.setView([lat,lng],15);
  } else {
    courierMarker.setLatLng([lat,lng]);
  }
}

/* ── Prep strip: countdown → delivery ETA ── */
function tickCountdown(){
  var el = document.getElementById('gdtPrepVal');
  if(!el) return;
  var rem = Math.floor((END_MS - Date.now()) / 1000);
  if(rem <= 0){
    el.textContent = 'ოდნავ გვიანდება...';
    el.className = 'gdt-prep-val overdue';
    el.style.fontSize = '13px';
  } else {
    var m = Math.floor(rem/60), s = rem%60;
    el.textContent = m + ':' + (s<10?'0':'') + s;
    el.className   = 'gdt-prep-val' + (rem < 120 ? ' overdue' : '');
    el.style.fontSize = '';
  }
}

function stopCountdown(){
  if(cdIv){ clearInterval(cdIv); cdIv = null; }
}

function updatePrepSection(vs, displayStep, courier){
  var wrap = document.getElementById('gdtPrepWrap');
  if(!wrap) return;

  // Terminal or in-transit: hide strip entirely (ETA shown in courier message)
  if(displayStep === 'in_transit' || displayStep === 'delivered' || displayStep === 'cancelled'){
    wrap.style.display = 'none';
    stopCountdown();
    return;
  }

  wrap.style.display = '';
  var icon = document.getElementById('gdtPrepIcon');
  var lbl  = document.getElementById('gdtPrepLabel');
  var val  = document.getElementById('gdtPrepVal');

  if(displayStep === 'ready'){
    // Order ready — switch to delivery ETA
    stopCountdown();
    var eta = null;
    if(courier && courier.lat && DELIVERY_LAT && DELIVERY_LNG){
      eta = Math.max(1, Math.ceil(haversine(courier.lat, courier.lng, DELIVERY_LAT, DELIVERY_LNG) / 416));
    } else if(PICKUP_LAT && PICKUP_LNG && DELIVERY_LAT && DELIVERY_LNG){
      eta = Math.max(1, Math.ceil(haversine(PICKUP_LAT, PICKUP_LNG, DELIVERY_LAT, DELIVERY_LNG) / 416));
    }
    if(icon) icon.textContent = '🛵';
    if(lbl)  lbl.textContent  = 'სავარაუდო მიტანა:';
    if(val){ val.textContent = eta ? '~' + eta + ' წთ' : '...'; val.className = 'gdt-prep-val eta'; val.style.fontSize = ''; }
  } else {
    // Still preparing — show/run countdown
    if(icon) icon.textContent = '⏱';
    if(lbl)  lbl.textContent  = 'სავარაუდო მომზადება:';
    if(PREP_MS > 0 && !cdIv){ tickCountdown(); cdIv = setInterval(tickCountdown, 1000); }
  }
}

/* ── Stepper update — display step + optional courier for pickup sub-step ── */
function updateSteps(displayStep, courier){
  var pickupEl  = document.getElementById('gdtStep_pickup');
  var pickupDot = document.getElementById('gdtPickupDot');
  var pickupLbl = document.getElementById('gdtPickupLbl');

  // Determine pickup step visibility and sub-state
  var showPickup = false;
  var pickupDone = false;
  var nearPickup = false;

  if(displayStep === 'ready' && courier && courier.lat && PICKUP_LAT){
    showPickup = true;
    nearPickup = haversine(courier.lat, courier.lng, PICKUP_LAT, PICKUP_LNG) < 200;
  } else if(displayStep === 'in_transit' || displayStep === 'delivered'){
    // Keep pickup visible as 'done' once courier has picked up
    pickupDone = HAS_DRIVER;
  }

  var pickupVisible = showPickup || pickupDone;

  // Show/update pickup step element
  if(pickupEl){
    pickupEl.style.display = pickupVisible ? 'flex' : 'none';
    if(pickupVisible){
      if(showPickup){
        pickupEl.className    = 'gdt-step current';
        pickupDot.textContent = nearPickup ? '📦' : '🛵';
        pickupLbl.textContent = nearPickup ? 'აღება' : 'ასაღებად';
      } else {
        pickupEl.className    = 'gdt-step done';
        pickupDot.textContent = '✓';
        pickupLbl.textContent = 'ასაღებად';
      }
    }
  }

  // Build the visible steps list (pickup only counted when shown)
  var vis = STEPS.filter(function(s){ return s !== 'pickup' || pickupVisible; });

  // Effective step for index: when pickup shown and order is ready, pickup is "current"
  var effStep = (showPickup) ? 'pickup' : displayStep;
  var idx = vis.indexOf(effStep);

  // Update all non-pickup steps
  STEPS.forEach(function(s, si){
    if(s === 'pickup') return;  // handled above
    var el = document.getElementById('gdtStep_'+s);
    if(!el) return;
    var vi = vis.indexOf(s);
    el.className = 'gdt-step'+(vi<idx?' done':vi===idx?' current':'');
    var dot = el.querySelector('.gdt-dot');
    if(dot) dot.textContent = vi<idx?'✓':STEP_ICONS[si];
  });

  // Progress line fill (based on visible step count)
  var fill = document.getElementById('gdtLineFill');
  if(fill && idx>=0){
    fill.style.width = 'calc(80% * '+Math.round(idx/Math.max(1,vis.length-1)*100)+'/100)';
  }
}

/* ── Courier message + ETA ── */
function updateCourierMsg(displayStep, status, vStatus, courier, hasDriver){
  var box  = document.getElementById('gdtCourierMsg');
  var icon = document.getElementById('gdtCourierMsgIcon');
  var txt  = document.getElementById('gdtCourierMsgText');
  var sub  = document.getElementById('gdtCourierMsgSub');
  if(!box) return;

  var show = false;

  if(displayStep === 'preparing'){
    // Vendor is cooking — only show courier info if courier already dispatched
    if(courier && courier.lat && PICKUP_LAT){
      icon.textContent = '🛵'; box.className = 'gdt-courier-msg show';
      txt.textContent  = 'კურიერი მიდის შეკვეთის ასაღებად';
      sub.textContent  = 'ობიექტი ამზადებს შეკვეთას';
      show = true;
    }
  } else if(displayStep === 'ready'){
    // Order ready — show courier status
    if(courier && courier.lat && PICKUP_LAT){
      var nearPickup = haversine(courier.lat,courier.lng,PICKUP_LAT,PICKUP_LNG) < 200;
      if(nearPickup){
        icon.textContent = '🛵'; box.className = 'gdt-courier-msg show arrived';
        txt.textContent  = 'კურიერი მივიდა შეკვეთის ასაღებად';
        sub.textContent  = 'კურიერი ობიექტთანაა';
      } else {
        icon.textContent = '🛵'; box.className = 'gdt-courier-msg show';
        txt.textContent  = 'შეკვეთა გამზადებულია · კურიერი მოდის';
        sub.textContent  = 'კურიერი გამოეშურება';
      }
      show = true;
    } else if(!hasDriver){
      // No courier assigned yet — actively searching
      icon.textContent = '🔍'; box.className = 'gdt-courier-msg show';
      txt.textContent  = 'ვეძებთ უახლოეს თავისუფალ კურიერს';
      sub.textContent  = 'ოდნავ მოთმინება…';
      show = true;
    } else {
      // Courier assigned but location not yet available
      icon.textContent = '🛵'; box.className = 'gdt-courier-msg show';
      txt.textContent  = 'შეკვეთა გამზადებულია';
      sub.textContent  = 'კურიერი გამოემართება';
      show = true;
    }
  } else if(displayStep === 'in_transit'){
    var eta = etaMinutes(courier&&courier.lat, courier&&courier.lng, DELIVERY_LAT, DELIVERY_LNG);
    icon.textContent = '🚗'; box.className = 'gdt-courier-msg show';
    txt.textContent  = eta ? 'კურიერი გზაშია · ~' + eta + ' წთ-ში' : 'კურიერი გზაშია თქვენ სახლამდე';
    sub.textContent  = eta ? 'სავარაუდო ჩაბარება: ' + eta + ' წთ-ში (25 კმ/სთ)' : 'ცოტაც და ჩააბარებს!';
    show = true;
  }

  if(!show) box.className = 'gdt-courier-msg';
}

/* ── Main poll ── */
function poll(){
  fetch(TRACK_API).then(function(r){return r.json();}).then(function(d){
    if(!d.ok) return;

    var prevDisplay = getDisplayStep(currentStatus, currentVStatus);
    var newDisplay  = getDisplayStep(d.status, d.vendor_status||'');
    var changed     = (d.status !== currentStatus) || (d.vendor_status !== currentVStatus);

    currentStatus  = d.status;
    currentVStatus = d.vendor_status || '';

    // Live-sync countdown end time if vendor updated prep
    if (d.prep_ends_at_ms && d.prep_ends_at_ms !== END_MS) {
      END_MS = d.prep_ends_at_ms;
      if (cdIv) { clearInterval(cdIv); cdIv = null; }
    }

    // Hero icon + animation
    var hAnim = document.getElementById('gdtHeroAnim');
    var hStat = document.getElementById('gdtHeroStatus');
    if(hAnim){ hAnim.textContent = HERO_ICONS[newDisplay] || '⏳'; hAnim.className = 'gdt-anim-' + newDisplay; }
    if(hStat) hStat.textContent = HERO_LABELS[newDisplay] || d.status;
    var gdtDoneBadge = document.getElementById('gdtDeliveredBadge');
    if(gdtDoneBadge) gdtDoneBadge.style.display = newDisplay === 'delivered' ? 'block' : 'none';

    // Prep strip / countdown / ETA
    updatePrepSection(d.vendor_status||'', newDisplay, d.courier);

    // Stepper (display step + courier GPS for pickup sub-step)
    updateSteps(newDisplay, d.courier);

    // Map: always show for 'ready' and 'in_transit'; for 'preparing' only when courier assigned
    var showMap = newDisplay === 'ready' || newDisplay === 'in_transit' ||
                  (newDisplay === 'preparing' && d.courier && d.courier.lat);
    var mapSec  = document.getElementById('gdtMapSection');
    if(showMap){
      if(mapSec) mapSec.classList.add('show');
      initMap();
      // Courier marker only when location is available
      if(d.courier && d.courier.lat) updateCourierMarker(d.courier.lat, d.courier.lng);
    }

    // Courier message
    if(d.has_driver !== undefined) HAS_DRIVER = !!d.has_driver;
    updateCourierMsg(newDisplay, d.status, d.vendor_status||'', d.courier, HAS_DRIVER);

    // Terminal states: stop polling
    if(d.status==='delivered'||d.status==='cancelled'){
      clearInterval(pollIv);
      if(changed) setTimeout(function(){ location.reload(); }, 600);
    }
  }).catch(function(){});
}

// Init prep strip + courier message immediately on page load
updatePrepSection(INIT_VSTATUS, getDisplayStep(INIT_STATUS, INIT_VSTATUS), null);
updateCourierMsg(getDisplayStep(INIT_STATUS, INIT_VSTATUS), INIT_STATUS, INIT_VSTATUS, null, HAS_DRIVER);

var pollIv = setInterval(poll, 4000);
poll();

})();
</script>
<?php endif ?>
