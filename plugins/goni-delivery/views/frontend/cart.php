<?php
$isLoggedIn = !empty($customer);
$minOrder   = $vendor ? (float)$vendor['min_order'] : 0;
$belowMin   = $minOrder > 0 && $summary['subtotal'] < $minOrder;
?>
<!DOCTYPE html>
<html lang="ka">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>🛒 კალათა · <?= e($brand ?? 'Delivery') ?></title>
<style>
:root{--p:#f59e0b;--pd:#d97706;--g:#22c55e;--r:#ef4444;--bg:#f1f5f9;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}

/* Layout */
.cart-wrap{max-width:760px;margin:0 auto;padding:24px 16px 100px}

/* Header */
.cart-topbar{display:flex;align-items:center;gap:14px;margin-bottom:24px}
.cart-back{width:40px;height:40px;border-radius:50%;background:var(--card);border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:18px;text-decoration:none;color:var(--text);flex-shrink:0;transition:all .15s;box-shadow:0 1px 3px rgba(0,0,0,.07)}
.cart-back:hover{background:var(--bg);border-color:var(--p)}
.cart-title{font-size:22px;font-weight:900}
.cart-subtitle{font-size:13px;color:var(--muted);margin-top:2px}

/* Vendor banner */
.vendor-banner{background:var(--card);border-radius:16px;padding:16px 18px;margin-bottom:20px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.vendor-logo{width:52px;height:52px;border-radius:12px;background:linear-gradient(135deg,#fef3c7,#fde68a);display:flex;align-items:center;justify-content:center;font-size:26px;flex-shrink:0}
.vendor-name{font-size:15px;font-weight:800}
.vendor-meta{font-size:12px;color:var(--muted);margin-top:2px}

/* Items card */
.items-card{background:var(--card);border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:16px}
.items-card-head{padding:14px 18px;border-bottom:1px solid var(--border);font-size:12px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;display:flex;justify-content:space-between;align-items:center}
.clear-btn{font-size:12px;color:var(--r);background:none;border:none;cursor:pointer;font-family:inherit;font-weight:700}
.cart-item{display:flex;align-items:center;gap:14px;padding:14px 18px;border-bottom:1px solid #f8fafc;transition:background .1s}
.cart-item:last-child{border-bottom:none}
.ci-info{flex:1;min-width:0}
.ci-name{font-size:14px;font-weight:700;margin-bottom:3px}
.ci-mods{font-size:12px;color:var(--muted);margin-bottom:6px;line-height:1.4}
.ci-price{font-size:13px;font-weight:800;color:var(--p)}
.qty-ctrl{display:flex;align-items:center;gap:8px;flex-shrink:0}
.qty-btn{width:32px;height:32px;border-radius:50%;border:1.5px solid var(--border);background:var(--card);cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;font-family:inherit;transition:all .15s;font-weight:700;color:var(--text)}
.qty-btn:hover{border-color:var(--p);color:var(--p);background:#fffbeb}
.qty-btn.minus:hover{border-color:var(--r);color:var(--r);background:#fef2f2}
.qty-num{font-size:15px;font-weight:900;min-width:24px;text-align:center}
.ci-total{font-size:14px;font-weight:900;min-width:60px;text-align:right;flex-shrink:0}

/* Summary card */
.summary-card{background:var(--card);border-radius:16px;padding:18px 18px;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:16px}
.sum-row{display:flex;justify-content:space-between;font-size:14px;padding:7px 0;color:var(--muted)}
.sum-row.total{font-weight:900;font-size:18px;color:var(--text);padding-top:12px;margin-top:4px;border-top:2px solid var(--border)}
.sum-row .val{font-weight:700;color:var(--text)}
.sum-row.total .val{color:var(--p);font-size:20px}
.free-delivery-bar{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:10px 14px;font-size:13px;color:#166534;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.min-order-warn{background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:10px 14px;font-size:13px;color:#92400e;margin-bottom:16px;display:flex;align-items:center;gap:8px}

/* Auth prompt */
.auth-prompt{background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1.5px solid #fde68a;border-radius:14px;padding:18px;margin-bottom:16px;text-align:center}
.auth-prompt p{font-size:13px;color:#92400e;margin-bottom:12px}

/* Sticky bottom */
.cart-sticky{position:fixed;bottom:0;left:0;right:0;z-index:999;padding:12px 16px 20px;background:linear-gradient(to top,#f1f5f9 60%,transparent);pointer-events:none}
.cart-sticky-inner{max-width:760px;margin:0 auto;pointer-events:all}
.checkout-btn{width:100%;padding:16px 20px;background:var(--p);color:#fff;border:none;border-radius:14px;font-size:16px;font-weight:900;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:space-between;box-shadow:0 4px 20px rgba(245,158,11,.4);transition:all .15s}
.checkout-btn:hover{background:var(--pd);transform:translateY(-1px);box-shadow:0 6px 24px rgba(245,158,11,.5)}
.checkout-btn:disabled{background:#fcd34d;cursor:not-allowed;transform:none;box-shadow:none;opacity:.7}
.checkout-btn .btn-total{font-size:17px;font-weight:900}
.checkout-btn .btn-label{opacity:.85;font-size:14px}

/* Loading skeleton */
@keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}
</style>
</head>
<body>

<div class="cart-wrap">

  <!-- Header -->
  <div class="cart-topbar">
    <a href="<?= e($base).'/'.$slug?>/vendor/<?= $vendor?e($vendor['slug']):''?>" class="cart-back">←</a>
    <div>
      <div class="cart-title">🛒 კალათა</div>
      <div class="cart-subtitle"><?= $summary['count']?> item<?= $summary['count']!==1?'s':''?> · <?= $vendor?e($vendor['name']):'Unknown vendor'?></div>
    </div>
  </div>

  <!-- Vendor banner -->
  <?php if($vendor): ?>
  <div class="vendor-banner">
    <?php if(!empty($vendor['logo'])): ?>
    <img src="<?= e($vendor['logo'])?>" alt="<?= e($vendor['name'])?>" class="vendor-logo" style="object-fit:cover">
    <?php else: ?>
    <div class="vendor-logo"><?= $vendor['category']==='grocery'?'🛒':($vendor['category']==='pharmacy'?'💊':'🍽')?></div>
    <?php endif ?>
    <div>
      <div class="vendor-name"><?= e($vendor['name'])?></div>
      <div class="vendor-meta">
        🛵 <?= number_format((float)$vendor['delivery_fee'],2).$sym?> delivery
        <?php if($vendor['free_delivery_threshold']>0): ?> · Free over <?= number_format((float)$vendor['free_delivery_threshold'],0).$sym?><?php endif ?>
        · ⏱ ~<?= (int)$vendor['prep_time_min']?>min
      </div>
    </div>
  </div>
  <?php endif ?>

  <!-- Min order warning -->
  <?php if($belowMin): ?>
  <div class="min-order-warn">
    ⚠️ Minimum order: <strong><?= number_format($minOrder,2).$sym?></strong>
    · კიდევ <strong><?= number_format($minOrder - $summary['subtotal'],2).$sym?></strong> დაამატე
  </div>
  <?php endif ?>

  <!-- Free delivery progress -->
  <?php if($vendor && $vendor['free_delivery_threshold']>0 && $summary['delivery_fee']>0):
    $pct = min(100, round($summary['subtotal']/$vendor['free_delivery_threshold']*100));
    $left = round((float)$vendor['free_delivery_threshold'] - $summary['subtotal'], 2);
  ?>
  <div style="background:var(--card);border-radius:14px;padding:14px 18px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)">
    <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:700;margin-bottom:8px">
      <span>🎉 <?= number_format($left,2).$sym?> more for free delivery</span>
      <span style="color:var(--g)"><?= number_format((float)$vendor['free_delivery_threshold'],0).$sym?></span>
    </div>
    <div style="height:6px;background:#f1f5f9;border-radius:999px;overflow:hidden">
      <div style="height:6px;background:var(--g);border-radius:999px;width:<?= $pct?>%;transition:width .3s"></div>
    </div>
  </div>
  <?php elseif($vendor && $vendor['free_delivery_threshold']>0 && $summary['delivery_fee']==0): ?>
  <div class="free-delivery-bar">🎉 <strong>Free delivery!</strong> — შენი შეკვეთა აღემატება <?= number_format((float)$vendor['free_delivery_threshold'],0).$sym?>-ს</div>
  <?php endif ?>

  <!-- Items -->
  <div class="items-card">
    <div class="items-card-head">
      <span><?= $summary['count']?> item<?= $summary['count']!==1?'s':''?></span>
      <button class="clear-btn" onclick="clearAll()">🗑 Clear all</button>
    </div>
    <div id="itemsList">
      <?php foreach($cart as $key => $item): ?>
      <div class="cart-item" id="item_<?= htmlspecialchars($key,ENT_QUOTES)?>">
        <?php
          $ciSizeMod   = null;
          $ciOtherMods = [];
          foreach(($item['modifiers'] ?? []) as $mod){
              if(($mod['group_type'] ?? '') === 'size') $ciSizeMod = $mod;
              else $ciOtherMods[] = $mod;
          }
        ?>
        <div class="ci-info">
          <?php if($ciSizeMod): ?>
          <div style="font-size:10px;font-weight:800;background:#dcfce7;color:#166534;border-radius:6px;padding:1px 8px;display:inline-block;margin-bottom:4px;letter-spacing:.3px">📏 <?= e($ciSizeMod['name']) ?><?= (float)($ciSizeMod['price']??0)>0?' (+'.number_format((float)$ciSizeMod['price'],2).$sym.')':'' ?></div>
          <?php endif ?>
          <div class="ci-name"><?= e($item['name'])?></div>
          <?php if(!empty($item['combos'])): ?>
          <div class="ci-mods">
            <?php foreach($item['combos'] as $cb):
              $cbType = $cb['type'] ?? 'choice';
              $cbSels = $cb['selections'] ?? [];
              if(!$cbSels) continue;
            ?>
              <?php if($cbType !== 'included'): ?>
              <span style="display:block;font-weight:700;color:#374151;margin-top:2px"><?= e($cb['name'] ?? '') ?></span>
              <?php endif ?>
              <?php foreach($cbSels as $s): ?>
              <span style="display:flex;align-items:baseline;gap:3px;padding-left:<?= $cbType!=='included'?'8':'0'?>px;line-height:1.6">
                <span style="color:#d1d5db;font-size:10px;flex-shrink:0">▸</span><?= e($s['name'] ?? '') ?>
              </span>
              <?php endforeach ?>
            <?php endforeach ?>
          </div>
          <?php endif ?>
          <?php if(!empty($ciOtherMods)): ?>
          <div class="ci-mods">
            <?php foreach($ciOtherMods as $mod): ?>
            <span style="display:flex;align-items:baseline;gap:3px;line-height:1.6">
              <span style="color:#d1d5db;font-size:10px;flex-shrink:0">✕</span><?= e($mod['name'] ?? '') ?><?= (float)($mod['price']??0) > 0 ? ' <span style="color:#f59e0b">(+'.number_format((float)$mod['price'],2).$sym.')</span>' : '' ?>
            </span>
            <?php endforeach ?>
          </div>
          <?php endif ?>
          <div class="ci-price"><?= number_format((float)$item['unit_price'],2).$sym?> each</div>
        </div>
        <div class="qty-ctrl">
          <button class="qty-btn minus" onclick="updateQty('<?= htmlspecialchars($key,ENT_QUOTES)?>', <?= (int)$item['quantity']?> - 1)">−</button>
          <span class="qty-num" id="qty_<?= htmlspecialchars($key,ENT_QUOTES)?>"><?= (int)$item['quantity']?></span>
          <button class="qty-btn" onclick="updateQty('<?= htmlspecialchars($key,ENT_QUOTES)?>', <?= (int)$item['quantity']?> + 1)">＋</button>
        </div>
        <div class="ci-total" id="total_<?= htmlspecialchars($key,ENT_QUOTES)?>"><?= number_format((float)$item['item_total'],2).$sym?></div>
      </div>
      <?php endforeach ?>
    </div>
  </div>

  <!-- Summary -->
  <div class="summary-card">
    <div class="sum-row"><span>Subtotal</span><span class="val" id="sumSubtotal"><?= number_format($summary['subtotal'],2).$sym?></span></div>
    <div class="sum-row"><span>Delivery fee</span><span class="val" id="sumDelivery"><?= number_format($summary['delivery_fee'],2).$sym?></span></div>
    <div class="sum-row" id="sumKmRow" style="display:none">
      <span>📏 მანძილის საკომისიო</span>
      <span class="val" id="sumKmFee">0.00<?= $sym?></span>
    </div>
    <div class="sum-row" id="sumBelowMinRow" <?= ($summary['below_min_fee']??0)<=0?'style="display:none"':''?>>
      <span>⚡ მინიმალური შეკვეთის საკომისიო</span>
      <span class="val" id="sumBelowMin"><?= number_format($summary['below_min_fee']??0,2).$sym?></span>
    </div>
    <div class="sum-row" id="sumWeatherRow" <?= ($summary['weather_fee']??0)<=0?'style="display:none"':''?>>
      <span>☔ ამინდის საკომისიო</span>
      <span class="val" id="sumWeather"><?= number_format($summary['weather_fee']??0,2).$sym?></span>
    </div>
    <div class="sum-row total"><span>Total</span><span class="val" id="sumTotal"><?= number_format($summary['total'],2).$sym?></span></div>
  </div>

  <!-- Auth prompt if not logged in -->
  <?php if(!$isLoggedIn): ?>
  <div class="auth-prompt">
    <p>შესასვლელად/დასარეგისტრირებლად OTP კოდი გამოგეგზავნება</p>
    <a href="<?= e($base).'/'.$slug?>/auth" class="checkout-btn" style="text-decoration:none;display:flex;justify-content:center;font-size:15px">
      🔐 შესვლა / რეგისტრაცია
    </a>
  </div>
  <?php endif ?>

  <div style="height:100px"></div><!-- space for sticky button -->
</div>

<!-- Sticky checkout button -->
<div class="cart-sticky">
  <div class="cart-sticky-inner">
    <?php if($isLoggedIn): ?>
    <button class="checkout-btn" id="checkoutBtn"
            onclick="goCheckout()"
            <?= $belowMin ? 'disabled' : ''?>>
      <span class="btn-label">📍 Checkout</span>
      <span class="btn-total" id="stickyTotal"><?= number_format($summary['total'],2).$sym?></span>
    </button>
    <?php else: ?>
    <a href="<?= e($base).'/'.$slug?>/auth" class="checkout-btn" style="text-decoration:none">
      <span class="btn-label">🔐 შევიდე და შევუკვეთო</span>
      <span class="btn-total"><?= number_format($summary['total'],2).$sym?></span>
    </a>
    <?php endif ?>
  </div>
</div>

<script>
var BASE = <?= json_encode($base) ?>;
var SLUG = <?= json_encode($slug) ?>;
var SYM  = <?= json_encode($sym) ?>;
var MIN_ORDER = <?= (float)$minOrder ?>;

function updateQty(key, newQty) {
    fetch(BASE+'/api/delivery/cart/update', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({key: key, qty: newQty})
    })
    .then(function(r){ return r.json(); })
    .then(function(d) {
        if (!d.ok) return;
        var c = d.cart;

        if (newQty <= 0) {
            var row = document.getElementById('item_'+key);
            if (row) { row.style.opacity='0'; row.style.transform='translateX(20px)'; row.style.transition='all .25s'; setTimeout(function(){ row.remove(); checkEmpty(c); }, 250); }
        } else {
            var qel = document.getElementById('qty_'+key);
            var tel = document.getElementById('total_'+key);
            if (qel) qel.textContent = newQty;
            // recalc item total locally
            var items = c.items || [];
            // find matching item
            items.forEach(function(item, idx) {
                if (String(idx) === String(key) || item.product_id + '_' === key.substr(0, key.indexOf('_')+1)) {
                    if (tel) tel.textContent = parseFloat(item.item_total).toFixed(2)+SYM;
                }
            });
        }

        updateSummary(c);
    });
}

function updateSummary(c) {
    var subtotal   = parseFloat(c.subtotal      || 0);
    var delivery   = parseFloat(c.delivery_fee  || 0);
    var belowMin   = parseFloat(c.below_min_fee || 0);
    var weather    = parseFloat(c.weather_fee   || 0);
    var kmFee      = parseFloat(c.km_fee        || 0);
    var total      = parseFloat(c.total         || 0);

    var el = document.getElementById('sumSubtotal'); if(el) el.textContent = subtotal.toFixed(2)+SYM;
    var el = document.getElementById('sumDelivery'); if(el) el.textContent = delivery.toFixed(2)+SYM;
    var el = document.getElementById('sumTotal');    if(el) el.textContent = total.toFixed(2)+SYM;
    var el = document.getElementById('stickyTotal'); if(el) el.textContent = total.toFixed(2)+SYM;

    var bmRow = document.getElementById('sumBelowMinRow');
    var bmEl  = document.getElementById('sumBelowMin');
    if(bmRow){ bmRow.style.display = belowMin > 0 ? '' : 'none'; }
    if(bmEl)  bmEl.textContent = belowMin.toFixed(2)+SYM;

    var wRow = document.getElementById('sumWeatherRow');
    var wEl  = document.getElementById('sumWeather');
    if(wRow){ wRow.style.display = weather > 0 ? '' : 'none'; }
    if(wEl)  wEl.textContent = weather.toFixed(2)+SYM;

    var kmRow = document.getElementById('sumKmRow');
    var kmEl  = document.getElementById('sumKmFee');
    if(kmRow){ kmRow.style.display = kmFee > 0 ? '' : 'none'; }
    if(kmEl)  kmEl.textContent = kmFee.toFixed(2)+SYM;

    // Min order check
    var btn = document.getElementById('checkoutBtn');
    if (btn) btn.disabled = (MIN_ORDER > 0 && subtotal < MIN_ORDER);
}

function checkEmpty(c) {
    if (!c.items || !c.items.length) {
        window.location.href = BASE+'/'+SLUG;
    }
}

function clearAll() {
    if (!confirm('კალათა გაიწმინდება. დარწმუნებული ხარ?')) return;
    fetch(BASE+'/api/delivery/cart/clear', {method:'POST'})
        .then(function(){ window.location.href = BASE+'/'+SLUG; });
}

function goCheckout() {
    window.location.href = BASE+'/'+SLUG+'/checkout';
}
</script>
</body>
</html>
