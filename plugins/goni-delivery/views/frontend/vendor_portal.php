<!DOCTYPE html>
<html lang="ka">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($vendor['name']) ?> · Portal</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#f1f5f9;--surface:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;
      --amber:#f59e0b;--green:#22c55e;--blue:#3b82f6;--red:#ef4444;--purple:#8b5cf6}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
a,a:hover{text-decoration:none}
.topbar{background:linear-gradient(135deg,#f59e0b,#ef4444);color:#fff;padding:14px 20px;
        display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.topbar-name{font-size:17px;font-weight:900}
.topbar-right{display:flex;align-items:center;gap:10px}
.live-dot{width:8px;height:8px;border-radius:50%;background:#4ade80;animation:lb 1.5s infinite;display:inline-block}
@keyframes lb{0%,100%{opacity:1}50%{opacity:.3}}
.topbar-live{font-size:12px;opacity:.85;display:flex;align-items:center;gap:5px}
.admin-link{background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);
            border-radius:8px;padding:6px 12px;font-size:12px;font-weight:700;display:flex;align-items:center;gap:5px}

/* ── Layout ── */
.main{max-width:960px;margin:0 auto;padding:20px 16px}

/* ── Stats ── */
.stats{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:20px}
.stat{background:var(--surface);border-radius:12px;padding:12px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.stat-val{font-size:22px;font-weight:900;margin-bottom:2px}
.stat-lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px}
@media(max-width:600px){.stats{grid-template-columns:1fr 1fr}}

/* ── Orders grid ── */
.orders-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px}

/* ── Solo mode ── */
.solo-back{display:none;align-items:center;gap:8px;background:var(--surface);border:1.5px solid var(--border);
           border-radius:10px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;
           color:var(--text);margin-bottom:14px;width:fit-content}
.solo-back.visible{display:flex}
.orders-grid.solo-mode .order-card:not(.solo){display:none}
.orders-grid.solo-mode .order-card.solo{grid-column:1/-1}

/* ── Order card ── */
.order-card{background:var(--surface);border-radius:16px;overflow:hidden;
            box-shadow:0 1px 3px rgba(0,0,0,.06);border:2px solid var(--border)}
.order-card.new{border-color:var(--amber);animation:np 1.2s ease-in-out infinite}
.order-card.new .order-head{animation:nh 1.2s ease-in-out infinite}
@keyframes np{
  0%,100%{box-shadow:0 0 0 0 rgba(245,158,11,.55),0 1px 3px rgba(0,0,0,.06);border-color:rgba(245,158,11,.7)}
  50%{box-shadow:0 0 0 12px rgba(245,158,11,0),0 1px 3px rgba(0,0,0,.06);border-color:rgba(245,158,11,1)}
}
@keyframes nh{0%,100%{background:#fff}50%{background:#fffbeb}}
.order-head{padding:12px 16px;display:flex;align-items:center;justify-content:space-between;
            border-bottom:1px solid var(--border);cursor:pointer;user-select:none}
.order-num{font-size:12px;font-family:monospace;color:var(--muted)}
.order-meta{font-size:11px;color:var(--muted);margin-top:2px}
.order-card.expanded .order-num{font-size:14px}
.order-card.expanded .order-meta{font-size:13px;margin-top:3px}
.pill{font-size:11px;font-weight:700;padding:2px 10px;border-radius:999px}
.card-chev{font-size:20px;color:var(--muted);line-height:1;flex-shrink:0;transition:transform .2s}
.order-card.expanded .card-chev{transform:rotate(90deg)}
.order-body{padding:16px 20px}
.order-item-row{display:flex;justify-content:space-between;align-items:flex-start;font-size:16px;padding:10px 0;border-bottom:1px solid #f1f5f9;gap:12px}
.order-item-row:last-child{border-bottom:none}
.order-item-row>span:first-child{flex:1;min-width:0}
.order-item-row>span:last-child{font-size:17px;font-weight:800;flex-shrink:0;padding-left:8px}
.order-total{font-size:20px;font-weight:900;color:var(--amber);margin-top:12px;padding-top:10px;border-top:2px solid var(--border);display:flex;justify-content:space-between}
.order-note{font-size:15px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:10px 14px;margin-top:10px;color:#92400e;line-height:1.45}
.order-branch{font-size:13px;font-weight:700;color:var(--blue);background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:5px 12px;margin-top:10px;display:inline-flex;align-items:center;gap:4px}
.order-actions{padding:14px 18px;border-top:1px solid var(--border);display:flex;gap:10px}
.act-btn{flex:1;padding:14px;border:none;border-radius:12px;font-size:16px;font-weight:800;cursor:pointer;font-family:inherit;transition:opacity .15s}
.act-btn:hover{opacity:.85}
.act-btn:disabled{opacity:.5;cursor:default}
.act-accept{background:var(--green);color:#fff}
.act-prepare{background:var(--blue);color:#fff}
.act-ready{background:var(--amber);color:#fff}
.act-done{background:var(--red);color:#fff}
.prep-row{display:flex;align-items:center;gap:8px;margin-top:10px;font-size:14px;color:var(--muted)}
.prep-row input{width:64px;border:1.5px solid var(--border);border-radius:8px;padding:6px 10px;font-size:15px;font-family:inherit;outline:none}
.empty{text-align:center;padding:60px 20px;color:var(--muted);font-size:14px}
.spinner{display:inline-block;width:20px;height:20px;border:3px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── Branch selector ── */
.branch-select{max-width:440px;margin:44px auto;padding:0 20px;text-align:center}
.branch-select-title{font-size:22px;font-weight:900;margin-bottom:6px}
.branch-select-sub{font-size:13px;color:var(--muted);margin-bottom:28px}
.branch-btn{display:flex;align-items:center;gap:14px;background:#fff;border:2px solid var(--border);border-radius:16px;padding:18px 20px;margin-bottom:12px;text-decoration:none;color:var(--text);transition:border-color .15s,box-shadow .15s;text-align:left}
.branch-btn:hover{border-color:var(--amber);box-shadow:0 0 0 4px rgba(245,158,11,.15)}
.branch-btn-icon{font-size:30px;flex-shrink:0}
.branch-btn-name{font-size:15px;font-weight:800}
.branch-btn-addr{font-size:12px;color:var(--muted);margin-top:2px}

/* ── PIN digits (shared by branch screen and modal) ── */
.pin-inputs{display:flex;justify-content:center;gap:10px;margin-bottom:16px}
.pin-digit{width:58px;height:70px;font-size:30px;font-weight:900;text-align:center;
           border:2.5px solid var(--border);border-radius:14px;outline:none;
           font-family:monospace;caret-color:transparent;background:#f8fafc;
           transition:border-color .15s,background .15s}
.pin-digit:focus{border-color:var(--amber);background:#fffbeb}
@keyframes shk{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-6px)}40%,80%{transform:translateX(6px)}}
.pin-err{color:var(--red);font-size:13px;font-weight:700;min-height:20px;margin-bottom:12px;text-align:center}

/* ── Branch PIN screen ── */
.bpin-screen{max-width:400px;margin:44px auto;padding:0 20px;text-align:center}
.bpin-title{font-size:20px;font-weight:900;margin-bottom:4px}
.bpin-sub{font-size:13px;color:var(--muted);margin-bottom:4px}
.bpin-name{font-size:16px;font-weight:900;color:var(--blue);margin-bottom:24px}
.bpin-submit{width:100%;padding:14px;background:var(--amber);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:800;cursor:pointer;font-family:inherit;margin-bottom:10px}
.bpin-back{font-size:13px;color:var(--muted);text-decoration:underline;background:none;border:none;font-family:inherit;cursor:pointer;margin-top:4px;display:inline-block}

/* ── Handoff PIN modal overlay ── */
.pin-overlay{position:fixed;inset:0;z-index:8000;background:rgba(0,0,0,.6);
             backdrop-filter:blur(4px);display:flex;align-items:flex-end;justify-content:center}
.pin-overlay.hidden{display:none}
.pin-sheet{width:100%;max-width:440px;background:#fff;border-radius:24px 24px 0 0;
           padding:28px 24px 40px;box-shadow:0 -8px 40px rgba(0,0,0,.25);
           animation:slideUp .3s cubic-bezier(.22,1,.36,1)}
@keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
.pin-sheet-title{font-size:18px;font-weight:900;text-align:center;margin-bottom:4px}
.pin-sheet-sub{font-size:13px;color:var(--muted);text-align:center;margin-bottom:22px}
.pin-submit{width:100%;padding:14px;background:var(--red);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:800;cursor:pointer;font-family:inherit;margin-bottom:8px}
.pin-cancel{width:100%;padding:11px;background:#f1f5f9;color:var(--muted);border:1px solid var(--border);border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}

/* ── Toast ── */
.toast{position:fixed;bottom:24px;right:24px;z-index:9999;background:#0f172a;color:#fff;padding:12px 20px;border-radius:12px;font-size:13px;font-weight:700;box-shadow:0 4px 20px rgba(0,0,0,.25);opacity:0;transform:translateY(10px);transition:all .25s;pointer-events:none}
.toast.show{opacity:1;transform:translateY(0)}
.toast.error{background:var(--red)}
</style>
</head>
<body>

<?php
$statusColors      = ['pending'=>'#f59e0b','accepted'=>'#3b82f6','preparing'=>'#8b5cf6','ready'=>'#22c55e'];
$statusLabels      = ['pending'=>'⏳ New','accepted'=>'✓ Accepted','preparing'=>'🍳 Preparing','ready'=>'✅ Ready'];
$branchMap         = !empty($branches) ? array_column($branches, null, 'id') : [];
$pending           = array_filter($orders, fn($o)=>$o['vendor_status']==='pending');
$accepted          = array_filter($orders, fn($o)=>$o['vendor_status']==='accepted');
$preparing         = array_filter($orders, fn($o)=>$o['vendor_status']==='preparing');
$ready             = array_filter($orders, fn($o)=>$o['vendor_status']==='ready');
$sym               = $sym ?? '₾';
$handoffPins       = $handoffPins ?? [];
$needsBranchSelect = $needsBranchSelect ?? false;
$needsBranchPin    = $needsBranchPin    ?? false;
$branchToken       = $branchToken       ?? '';
$branchTokens      = $branchTokens      ?? [];
?>

<div class="topbar">
  <div>
    <?php if(!empty($vendor['menu_size'])): ?>
    <div style="font-size:10px;font-weight:700;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.35);border-radius:6px;padding:1px 8px;display:inline-block;margin-bottom:4px;letter-spacing:.3px">📋 <?= e($vendor['menu_size']) ?></div>
    <?php endif ?>
    <div class="topbar-name">🏪 <?= e($vendor['name']) ?></div>
    <?php if(!empty($selectedBranch)): ?>
    <div style="font-size:11px;opacity:.75;margin-top:2px">📍 <?= e($selectedBranch['name']) ?></div>
    <?php endif ?>
  </div>
  <div class="topbar-right">
    <div class="topbar-live"><span class="live-dot"></span><span>Live · <?= date('H:i') ?></span></div>
    <?php if(count($branches) > 1): ?>
    <a href="<?= e($base)?>/delivery/portal/<?= e($token)?>?switch=1" class="admin-link" title="ობიექტის შეცვლა">🔄</a>
    <?php endif ?>
    <a href="<?= e($base)?>/delivery/portal/<?= e($token)?>/admin" class="admin-link">⚙ ადმინი</a>
  </div>
</div>

<div class="main">

<?php if($needsBranchPin && !empty($selectedBranch)): ?>
<!-- ── Branch PIN entry screen ─────────────────────────────────────────── -->
<div class="bpin-screen">
  <div style="font-size:52px;margin-bottom:14px">🔐</div>
  <div class="bpin-title">PIN კოდი</div>
  <div class="bpin-sub">ობიექტი:</div>
  <div class="bpin-name">🏪 <?= e($selectedBranch['name']) ?></div>
  <div class="pin-inputs" id="bpinWrap">
    <input class="pin-digit" maxlength="1" id="bpin0" inputmode="numeric" pattern="[0-9]" autocomplete="off">
    <input class="pin-digit" maxlength="1" id="bpin1" inputmode="numeric" pattern="[0-9]" autocomplete="off">
    <input class="pin-digit" maxlength="1" id="bpin2" inputmode="numeric" pattern="[0-9]" autocomplete="off">
    <input class="pin-digit" maxlength="1" id="bpin3" inputmode="numeric" pattern="[0-9]" autocomplete="off">
  </div>
  <div class="pin-err" id="bpinError"></div>
  <button class="bpin-submit" id="bpinSubmitBtn" onclick="submitBranchPin()">✓ შესვლა</button>
  <a href="<?= e($base)?>/delivery/portal/<?= e($token)?>" class="bpin-back">← ობიექტის სია</a>
</div>

<?php elseif($needsBranchSelect): ?>
<!-- ── Branch selector ─────────────────────────────────────────────────── -->
<div class="branch-select">
  <div class="branch-select-title">🏪 <?= e($vendor['name']) ?></div>
  <div class="branch-select-sub">აირჩიეთ მიმდინარე ობიექტი</div>
  <?php foreach($branches as $b): ?>
  <a href="?t=<?= urlencode($branchTokens[(int)$b['id']] ?? '') ?>" class="branch-btn">
    <div class="branch-btn-icon">🏪</div>
    <div>
      <div class="branch-btn-name"><?= e($b['name']) ?></div>
      <?php if(!empty($b['address'])): ?><div class="branch-btn-addr">📍 <?= e($b['address']) ?></div><?php endif ?>
    </div>
  </a>
  <?php endforeach ?>
</div>

<?php else: ?>
<!-- ── Stats + orders ──────────────────────────────────────────────────── -->
<div class="stats">
  <div class="stat"><div class="stat-val" style="color:var(--amber)"><?= count($pending)?></div><div class="stat-lbl">New</div></div>
  <div class="stat"><div class="stat-val" style="color:var(--blue)"><?= count($accepted)?></div><div class="stat-lbl">Accepted</div></div>
  <div class="stat"><div class="stat-val" style="color:var(--purple)"><?= count($preparing)?></div><div class="stat-lbl">Preparing</div></div>
  <div class="stat"><div class="stat-val" style="color:var(--green)"><?= count($ready)?></div><div class="stat-lbl">Ready</div></div>
  <div class="stat"><div class="stat-val" style="color:var(--red)" id="completedCount"><?= $completedToday ?></div><div class="stat-lbl">დასრულებული</div></div>
</div>

<!-- Back button (visible only in solo mode) -->
<button class="solo-back" id="soloBackBtn" onclick="exitSolo()">← სია</button>

<div class="orders-grid">
<?php if(empty($orders)): ?>
  <div class="empty">📭 ახლა აქტიური შეკვეთა არ არის</div>
<?php else: ?>
<?php foreach($orders as $o):
  $sc = $statusColors[$o['vendor_status']] ?? '#64748b';
  $sl = $statusLabels[$o['vendor_status']] ?? $o['vendor_status'];
  $isNew      = $o['vendor_status']==='pending';
  $autoExpand = true;
  $itemCount  = count($o['items'] ?? []);
  $subtotal   = (float)($o['subtotal'] ?? $o['price'] ?? 0);
  $oid        = (int)$o['id'];
?>
  <div class="order-card <?= $isNew?'new':''?> <?= $autoExpand?'expanded':''?>" id="oc_<?=$oid?>">
    <div class="order-head" onclick="soloCard(<?=$oid?>)">
      <div>
        <span class="order-num"><?= e($o['order_number'])?></span>
        <div class="order-meta"><?= $itemCount ?> ნივთი · <?= number_format($subtotal,2).$sym ?></div>
      </div>
      <div style="display:flex;align-items:center;gap:6px">
        <span class="pill" style="background:<?=$sc?>22;color:<?=$sc?>"><?= $sl?></span>
        <span class="card-chev" id="chev_<?=$oid?>" style="transform:rotate(90deg)">›</span>
      </div>
    </div>
    <div class="order-body" id="body_<?=$oid?>">
      <?php foreach($o['items']??[] as $item):
        $extras    = !empty($item['modifiers_json']) ? json_decode($item['modifiers_json'], true) : [];
        $comboInfo = $extras['combos'] ?? [];
        $modInfo   = $extras['modifiers'] ?? [];
        $vpSizeMod   = null;
        $vpOtherMods = [];
        foreach($modInfo as $mod){
            if(($mod['group_type'] ?? '') === 'size') $vpSizeMod = $mod;
            else $vpOtherMods[] = $mod;
        }
      ?>
      <div class="order-item-row">
        <span>
          <?php if($vpSizeMod): ?>
          <span style="font-size:11px;font-weight:800;background:#dcfce7;color:#166534;border-radius:5px;padding:2px 8px;display:inline-block;margin-bottom:3px;letter-spacing:.3px">📏 <?= e($vpSizeMod['name']) ?></span><br>
          <?php endif ?>
          <b style="font-size:17px"><?= e($item['name'])?></b> <span style="font-size:15px;color:var(--muted);font-weight:600">×<?= (int)$item['quantity']?></span>
          <?php foreach($comboInfo as $cb):
            $cbType = $cb['type'] ?? 'choice';
            $sels   = $cb['selections'] ?? [];
            if(!$sels) continue;
          ?>
          <?php if($cbType === 'included'): ?>
            <?php foreach($sels as $s): ?>
            <span style="font-size:14px;color:#374151;display:flex;align-items:baseline;gap:5px;padding:2px 0 2px 12px;line-height:1.5">
              <span style="color:#9ca3af;flex-shrink:0">▸</span><?= e($s['name'] ?? '') ?>
            </span>
            <?php endforeach ?>
          <?php else: ?>
            <span style="font-size:12px;color:var(--muted);display:block;padding-left:12px;font-weight:700;margin-top:3px"><?= e($cb['name'] ?? '') ?></span>
            <?php foreach($sels as $s): ?>
            <span style="font-size:14px;color:#374151;display:flex;align-items:baseline;gap:5px;padding:2px 0 2px 22px;line-height:1.5">
              <span style="color:#9ca3af;flex-shrink:0">▸</span><?= e($s['name'] ?? '') ?>
            </span>
            <?php endforeach ?>
          <?php endif ?>
          <?php endforeach ?>
          <?php foreach($vpOtherMods as $mod): ?>
            <span style="font-size:14px;color:#6b7280;display:flex;align-items:baseline;gap:5px;padding:2px 0 2px 12px;line-height:1.5">
              <span style="color:#d1d5db;flex-shrink:0">✕</span><?= e($mod['name'] ?? '') ?>
            </span>
          <?php endforeach ?>
        </span>
        <span><?= number_format((float)$item['item_total'],2).$sym?></span>
      </div>
      <?php endforeach ?>
      <?php $subtotal = (float)($o['subtotal'] ?? $o['price'] ?? 0); ?>
      <div class="order-total"><span>სულ</span><span><?= number_format($subtotal,2).$sym?></span></div>
      <?php if(!empty($o['branch_id']) && isset($branchMap[(int)$o['branch_id']])): ?>
      <div class="order-branch">🏪 <?= e($branchMap[(int)$o['branch_id']]['name'])?></div>
      <?php elseif(count($branchMap) === 0 && !empty($o['pickup_address'])): ?>
      <div class="order-branch">🏪 <?= e($vendor['name'])?></div>
      <?php endif ?>
      <?php if($o['customer_note']): ?>
      <div class="order-note">📝 <?= e($o['customer_note'])?></div>
      <?php endif ?>
      <?php if($o['vendor_status']==='pending'): ?>
      <div class="prep-row">⏱ მზადება: <input type="number" id="prep_<?=$oid?>" value="<?=(int)($o['prep_time_minutes']??20)?>" min="5" max="120"> წთ</div>
      <?php endif ?>
    </div>
    <div class="order-actions">
      <?php if($o['vendor_status']==='pending'): ?>
        <button class="act-btn act-accept" onclick="orderStatus(<?=$oid?>,'accepted',this)">✓ მიღება</button>
      <?php elseif($o['vendor_status']==='accepted'): ?>
        <button class="act-btn act-prepare" onclick="orderStatus(<?=$oid?>,'preparing',this)">🍳 მომზადება</button>
      <?php elseif($o['vendor_status']==='preparing'): ?>
        <button class="act-btn act-ready" onclick="orderStatus(<?=$oid?>,'ready',this)">✅ მზადაა</button>
      <?php elseif($o['vendor_status']==='ready'):
          $courierPickedUp = in_array($o['status'] ?? '', ['picked_up','in_transit','delivered'], true);
        ?>
        <?php if($courierPickedUp): ?>
        <button class="act-btn act-done" onclick="showPinModal(<?=$oid?>)">🏁 დასრულება</button>
        <?php else: ?>
        <button class="act-btn act-done" disabled title="კურიერი ჯერ ვერ ამოიღო შეკვეთა">⏳ კურიერი მოდის</button>
        <?php endif ?>
      <?php endif ?>
    </div>
  </div>
<?php endforeach ?>
<?php endif ?>
</div><!-- .orders-grid -->
<?php endif ?>
</div><!-- .main -->

<!-- ── Handoff PIN modal ───────────────────────────────────────────────────── -->
<div class="pin-overlay hidden" id="pinOverlay">
  <div class="pin-sheet">
    <div class="pin-sheet-title">🔐 ჩაბარების PIN</div>
    <div class="pin-sheet-sub">შეიყვანეთ კურიერის 4-ნიშნა კოდი</div>
    <div class="pin-inputs">
      <input class="pin-digit" maxlength="1" id="pd0" inputmode="numeric" pattern="[0-9]" autocomplete="off">
      <input class="pin-digit" maxlength="1" id="pd1" inputmode="numeric" pattern="[0-9]" autocomplete="off">
      <input class="pin-digit" maxlength="1" id="pd2" inputmode="numeric" pattern="[0-9]" autocomplete="off">
      <input class="pin-digit" maxlength="1" id="pd3" inputmode="numeric" pattern="[0-9]" autocomplete="off">
    </div>
    <div class="pin-err" id="pinError"></div>
    <button class="pin-submit" id="pinSubmitBtn" onclick="submitHandoffPin()">✓ დასრულება</button>
    <button class="pin-cancel" onclick="closePinModal()">გაუქმება</button>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
var BASE         = <?= json_encode($base) ?>;
var TOKEN        = <?= json_encode($token) ?>;
var ORDER_API    = BASE + '/api/delivery/vendor/' + TOKEN;
var BRANCH_TOKEN = <?= json_encode($branchToken) ?>;

/* ── Solo card mode ──────────────────────────────────────────────────────── */
var _soloId = null;

function soloCard(orderId) {
    // Toggle: tap same card again → back to list
    if (_soloId === orderId) { exitSolo(); return; }
    var grid = document.querySelector('.orders-grid');
    if (!grid) return;
    grid.querySelectorAll('.order-card.solo').forEach(function(c){ c.classList.remove('solo'); });
    var card = document.getElementById('oc_'+orderId);
    if (!card) return;
    card.classList.add('solo','expanded');
    _soloId = orderId;
    grid.classList.add('solo-mode');
    var back = document.getElementById('soloBackBtn');
    if (back) back.classList.add('visible');
    card.scrollIntoView({behavior:'smooth', block:'start'});
}

function exitSolo() {
    var grid = document.querySelector('.orders-grid');
    if (!grid) return;
    grid.classList.remove('solo-mode');
    grid.querySelectorAll('.order-card.solo').forEach(function(c){ c.classList.remove('solo'); });
    var back = document.getElementById('soloBackBtn');
    if (back) back.classList.remove('visible');
    _soloId = null;
}

/* ── Toast ───────────────────────────────────────────────────────────────── */
function toast(msg, isErr) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast' + (isErr ? ' error' : '') + ' show';
    setTimeout(function(){ t.className = 'toast' + (isErr ? ' error' : ''); }, 3000);
}

/* ── Generic status change (accept / preparing / ready) ─────────────────── */
function orderStatus(orderId, status, btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>';
    var prepInput = document.getElementById('prep_'+orderId);
    var prep = prepInput ? parseInt(prepInput.value)||20 : null;
    fetch(ORDER_API+'/order/'+orderId+'/status', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({vendor_status: status, prep_time: prep})
    }).then(function(r){ return r.json(); }).then(function(d){
        if (d.ok) {
            toast('✓ სტატუსი განახლდა');
            exitSolo();
            setTimeout(function(){ location.reload(); }, 600);
        } else {
            toast('შეცდომა', true);
            btn.disabled = false;
            btn.innerHTML = btn.getAttribute('data-label') || 'Error';
        }
    }).catch(function(){
        toast('შეცდომა', true);
        btn.disabled = false;
    });
}

/* ── Handoff PIN modal ───────────────────────────────────────────────────── */
var _pinOrderId = null;

function showPinModal(orderId) {
    _pinOrderId = orderId;
    ['pd0','pd1','pd2','pd3'].forEach(function(id){
        var el = document.getElementById(id);
        if (el) { el.value = ''; el.style.animation = ''; el.style.borderColor = ''; }
    });
    document.getElementById('pinError').textContent = '';
    var sub = document.getElementById('pinSubmitBtn');
    if (sub) { sub.disabled = false; sub.innerHTML = '✓ დასრულება'; }
    document.getElementById('pinOverlay').classList.remove('hidden');
    setTimeout(function(){ var f = document.getElementById('pd0'); if (f) f.focus(); }, 80);
}

function closePinModal() {
    document.getElementById('pinOverlay').classList.add('hidden');
    _pinOrderId = null;
}

function submitHandoffPin() {
    if (!_pinOrderId) return;
    var pin = ['pd0','pd1','pd2','pd3'].map(function(id){
        var el = document.getElementById(id); return el ? el.value : '';
    }).join('');
    if (pin.length < 4) {
        document.getElementById('pinError').textContent = 'შეიყვანეთ 4 ციფრი';
        return;
    }
    var btn = document.getElementById('pinSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>';
    fetch(ORDER_API+'/order/'+_pinOrderId+'/status', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({vendor_status: 'completed', handoff_pin: pin})
    }).then(function(r){ return r.json(); }).then(function(d){
        btn.disabled = false;
        btn.innerHTML = '✓ დასრულება';
        if (d.ok) {
            var oid = _pinOrderId;
            closePinModal();
            toast('✅ შეკვეთა დასრულდა');
            // Counter +1
            var cc = document.getElementById('completedCount');
            if (cc) cc.textContent = (parseInt(cc.textContent)||0) + 1;
            // Animate card out
            var card = document.getElementById('oc_'+oid);
            if (card) {
                var pill = card.querySelector('.pill');
                if (pill) { pill.textContent='✅ დასრულებული'; pill.style.background='#dcfce722'; pill.style.color='#166534'; }
                card.style.transition = 'opacity .35s ease, transform .35s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(.96)';
                setTimeout(function(){ card.remove(); }, 380);
            }
            exitSolo();
            setTimeout(function(){ location.reload(); }, 1200);
        } else if (d.error === 'invalid_pin') {
            document.getElementById('pinError').textContent = '❌ ' + (d.message || 'კოდი არასწორია');
            ['pd0','pd1','pd2','pd3'].forEach(function(id){
                var el = document.getElementById(id);
                if (el) {
                    el.value = '';
                    el.style.borderColor = 'var(--red)';
                    el.style.animation = 'shk .4s ease';
                    setTimeout(function(){ el.style.animation = ''; el.style.borderColor = ''; }, 500);
                }
            });
            setTimeout(function(){ var f=document.getElementById('pd0'); if(f) f.focus(); }, 100);
        } else {
            document.getElementById('pinError').textContent = '❌ შეცდომა, კვლავ სცადეთ';
        }
    }).catch(function(){
        btn.disabled = false; btn.innerHTML = '✓ დასრულება';
        document.getElementById('pinError').textContent = '❌ კავშირის შეცდომა';
    });
}

/* ── Generic PIN auto-advance helper ────────────────────────────────────── */
function bindPins(prefix, count, onComplete) {
    for (var i = 0; i < count; i++) {
        (function(idx){
            var el = document.getElementById(prefix + idx);
            if (!el) return;
            el.addEventListener('input', function(){
                var v = this.value.replace(/\D/g,'');
                this.value = v.slice(-1);
                if (v && idx < count - 1) {
                    var nxt = document.getElementById(prefix + (idx+1));
                    if (nxt) nxt.focus();
                }
                if (idx === count - 1 && v && onComplete) onComplete();
            });
            el.addEventListener('keydown', function(e){
                if (e.key === 'Backspace' && !this.value && idx > 0) {
                    var prv = document.getElementById(prefix + (idx-1));
                    if (prv) { prv.value = ''; prv.focus(); }
                }
                if (e.key === 'Enter' && onComplete) onComplete();
            });
        })(i);
    }
}

// Handoff PIN modal
bindPins('pd', 4, function(){ setTimeout(submitHandoffPin, 80); });

// Branch PIN screen — AJAX (PIN never enters the URL)
function submitBranchPin() {
    var pin = ['bpin0','bpin1','bpin2','bpin3'].map(function(id){
        var el = document.getElementById(id); return el ? el.value : '';
    }).join('');
    var errEl = document.getElementById('bpinError');
    if (pin.length < 4) { if(errEl) errEl.textContent='შეიყვანეთ 4 ციფრი'; return; }
    var btn = document.getElementById('bpinSubmitBtn');
    if (btn) { btn.disabled=true; btn.innerHTML='<span class="spinner"></span>'; }
    fetch(BASE+'/api/delivery/vendor/'+TOKEN+'/branch-auth', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({t: BRANCH_TOKEN, pin: pin})
    }).then(function(r){ return r.json(); }).then(function(d){
        if (btn) { btn.disabled=false; btn.innerHTML='✓ შესვლა'; }
        if (d.ok) {
            window.location.href = '?t=' + encodeURIComponent(BRANCH_TOKEN);
        } else if (d.error === 'invalid_pin') {
            if(errEl) errEl.textContent = '❌ '+(d.message||'PIN კოდი არასწორია');
            ['bpin0','bpin1','bpin2','bpin3'].forEach(function(id){
                var el=document.getElementById(id);
                if(el){ el.value=''; el.style.borderColor='var(--red)'; setTimeout(function(){ el.style.borderColor=''; },600); }
            });
            setTimeout(function(){ var f=document.getElementById('bpin0'); if(f) f.focus(); },100);
        } else {
            if(errEl) errEl.textContent='❌ შეცდომა';
        }
    }).catch(function(){
        if(btn){ btn.disabled=false; btn.innerHTML='✓ შესვლა'; }
        if(errEl) errEl.textContent='❌ კავშირის შეცდომა';
    });
}
(function(){
    var f = document.getElementById('bpin0');
    if (f) setTimeout(function(){ f.focus(); }, 150);
    bindPins('bpin', 4, function(){ setTimeout(submitBranchPin, 80); });
})();

// Auto-reload every 30s — pause while PIN modal is open
setInterval(function(){ if (!_pinOrderId) location.reload(); }, 30000);
</script>
</body>
</html>
