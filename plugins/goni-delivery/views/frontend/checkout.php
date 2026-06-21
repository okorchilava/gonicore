<?php
$vendor   = $summary['vendor_id'] ? $delivery->getVendor((int)$summary['vendor_id']) : null;
$vLat     = $summary['vendor_lat'] ?? ($vendor['lat'] ?? null);
$vLng     = $summary['vendor_lng'] ?? ($vendor['lng'] ?? null);
?>
<style>
.co-wrap{max-width:580px;margin:0 auto;padding:28px 16px 48px}
.co-head{display:flex;align-items:center;gap:12px;margin-bottom:24px}
.co-back{color:#64748b;font-size:13px;text-decoration:none;font-weight:600}
.co-title{font-size:22px;font-weight:900}
.co-card{background:#fff;border-radius:16px;padding:22px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.co-card-title{font-size:12px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:16px}
.co-field{margin-bottom:14px}
.co-label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px;display:block}
.co-input{width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:11px 14px;font-size:14px;font-family:inherit;outline:none;transition:border-color .15s;background:#fff;box-sizing:border-box}
.co-input:focus{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.1)}
.co-item-row{display:flex;justify-content:space-between;font-size:13px;padding:8px 0;border-bottom:1px solid #f1f5f9}
.co-item-row:last-child{border-bottom:none}
.co-sum-row{display:flex;justify-content:space-between;font-size:13px;padding:6px 0}
.co-sum-row.total{font-weight:900;font-size:16px;padding-top:10px;border-top:1px solid #e2e8f0;margin-top:4px}
.co-pay{display:flex;gap:8px}
.co-pay-opt{flex:1;display:flex;align-items:center;gap:10px;padding:12px;border:1.5px solid #e2e8f0;border-radius:10px;cursor:pointer;background:#fff;transition:all .15s}
.co-pay-opt input{display:none}
.co-pay-opt.selected{border-color:#f59e0b;background:#fffbeb}
.co-btn{width:100%;padding:14px;background:#f59e0b;color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:800;cursor:pointer;font-family:inherit;transition:all .15s;margin-top:4px}
.co-btn:hover{background:#d97706}
.co-ac{position:absolute;z-index:999;background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;top:calc(100% + 4px);left:0;width:100%;box-shadow:0 8px 24px rgba(0,0,0,.1);max-height:180px;overflow-y:auto;display:none}
.co-ac-item{padding:10px 14px;font-size:13px;cursor:pointer;border-bottom:1px solid #f1f5f9}
.co-ac-item:hover{background:#fafafa}
</style>

<div class="co-wrap">
  <div class="co-head">
    <a href="<?= e($base).'/'.$slug?>/cart" class="co-back">← კალათა</a>
    <div class="co-title">Checkout</div>
  </div>

  <form method="POST" action="<?= e($base).'/'.$slug?>/checkout" id="coForm">
    <!-- Order summary -->
    <div class="co-card">
      <div class="co-card-title">📋 Order Summary<?php if($vendor): ?> · <?= e($vendor['name'])?><?php endif ?></div>
      <?php foreach($cart as $item):
        $coSizeMod   = null;
        $coOtherMods = [];
        foreach(($item['modifiers'] ?? []) as $mod){
            if(($mod['group_type'] ?? '') === 'size') $coSizeMod = $mod;
            else $coOtherMods[] = $mod;
        }
      ?>
      <div class="co-item-row" style="align-items:flex-start">
        <div style="flex:1;min-width:0">
          <?php if($coSizeMod): ?>
          <div style="font-size:10px;font-weight:800;background:#dcfce7;color:#166534;border-radius:6px;padding:1px 8px;display:inline-block;margin-bottom:3px;letter-spacing:.3px">📏 <?= e($coSizeMod['name']) ?><?= (float)($coSizeMod['price']??0)>0?' (+'.number_format((float)$coSizeMod['price'],2).$sym.')':'' ?></div>
          <?php endif ?>
          <div style="font-weight:700"><?= e($item['name'])?> <span style="font-weight:500;color:#64748b">×<?= (int)$item['quantity']?></span></div>
          <?php if(!empty($item['combos'])): ?>
            <?php foreach($item['combos'] as $cb):
              $cbType = $cb['type'] ?? 'choice';
              $cbSels = $cb['selections'] ?? [];
              if(!$cbSels) continue;
            ?>
              <?php if($cbType !== 'included'): ?>
              <div style="font-size:11px;font-weight:700;color:#374151;margin-top:2px"><?= e($cb['name'] ?? '') ?></div>
              <?php endif ?>
              <?php foreach($cbSels as $s): ?>
              <div style="font-size:11px;color:#6b7280;display:flex;align-items:baseline;gap:3px;padding-left:<?= $cbType!=='included'?'8':'0'?>px;line-height:1.6">
                <span style="color:#d1d5db;font-size:9px;flex-shrink:0">▸</span><?= e($s['name'] ?? '') ?>
              </div>
              <?php endforeach ?>
            <?php endforeach ?>
          <?php endif ?>
          <?php if(!empty($coOtherMods)): ?>
            <?php foreach($coOtherMods as $mod): ?>
            <div style="font-size:11px;color:#6b7280;display:flex;align-items:baseline;gap:3px;line-height:1.6">
              <span style="color:#d1d5db;font-size:9px;flex-shrink:0">✕</span><?= e($mod['name'] ?? '') ?>
            </div>
            <?php endforeach ?>
          <?php endif ?>
        </div>
        <span style="font-weight:700;white-space:nowrap;padding-left:8px"><?= number_format((float)$item['item_total'],2).$sym?></span>
      </div>
      <?php endforeach ?>
      <div style="margin-top:12px;padding-top:12px;border-top:1px solid #e2e8f0">
        <div class="co-sum-row"><span>Subtotal</span><span><?= number_format($summary['subtotal'],2).$sym?></span></div>
        <div class="co-sum-row"><span>Delivery Fee</span><span id="coDelivery"><?= number_format($summary['delivery_fee'],2).$sym?></span></div>
        <div class="co-sum-row" id="coKmRow" style="display:none"><span>📏 მანძილის საკომისიო</span><span id="coKmFee">0.00<?= $sym?></span></div>
        <?php if(($summary['below_min_fee']??0)>0): ?>
        <div class="co-sum-row"><span>⚡ მინიმალური შეკვეთის საკომისიო</span><span><?= number_format($summary['below_min_fee'],2).$sym?></span></div>
        <?php endif ?>
        <?php if(($summary['weather_fee']??0)>0): ?>
        <div class="co-sum-row"><span>☔ ამინდის საკომისიო</span><span><?= number_format($summary['weather_fee'],2).$sym?></span></div>
        <?php endif ?>
        <div class="co-sum-row total"><span>Total</span><span id="coTotal"><?= number_format($summary['total'],2).$sym?></span></div>
      </div>
    </div>

    <!-- Delivery address -->
    <div class="co-card">
      <div class="co-card-title">📍 მიტანის მისამართი</div>
      <?php if(!empty($customer['home_address'])): ?>
      <div style="display:flex;gap:8px;margin-bottom:12px">
        <button type="button" onclick="fillAddress('<?= e(addslashes($customer['home_address']))?>')" class="btn btn-ghost" style="font-size:12px">🏠 სახლი</button>
      </div>
      <?php endif ?>
      <div class="co-field" style="position:relative">
        <label class="co-label">მისამართი *</label>
        <input type="text" name="delivery_address" id="deliveryAddr" class="co-input" placeholder="ქუჩა, სახლი, ბინა..." required autocomplete="off" oninput="acAddr()">
        <input type="hidden" name="delivery_lat" id="deliveryLat">
        <input type="hidden" name="delivery_lng" id="deliveryLng">
        <div id="addrAc" class="co-ac"></div>
      </div>
      <div class="co-field">
        <label class="co-label">შენიშვნა კურიერისთვის</label>
        <textarea name="customer_note" class="co-input" rows="2" placeholder="კარის კოდი, სართული..."></textarea>
      </div>
    </div>

    <!-- Payment -->
    <div class="co-card">
      <div class="co-card-title">💳 გადახდა</div>
      <div class="co-pay">
        <label class="co-pay-opt selected" id="payCash" onclick="selectPay(this)">
          <input type="radio" name="payment_method" value="cash" checked>
          <span style="font-size:20px">💵</span>
          <div><div style="font-size:13px;font-weight:700">Cash</div><div style="font-size:11px;color:#64748b">On delivery</div></div>
        </label>
      </div>
    </div>

    <button type="submit" class="co-btn" id="coBtn">
      <span id="coBtnTxt">🛵 Place Order · <span id="coBtnTotal"><?= number_format($summary['total'],2).$sym?></span></span>
    </button>
  </form>
</div>

<script>
var BASE          = <?= json_encode($base) ?>;
var SYM           = <?= json_encode($sym) ?>;
var VENDOR_LAT    = <?= json_encode($vLat) ?>;
var VENDOR_LNG    = <?= json_encode($vLng) ?>;
var PER_KM_RATE   = <?= (float)($perKmRate ?? 0) ?>;
var PER_KM_THRESH = <?= (float)($perKmThreshold ?? 5) ?>;
var BASE_DELIVERY = <?= (float)$summary['delivery_fee'] ?>;
var BASE_TOTAL    = <?= (float)$summary['total'] ?>;
var acTimer = null;

function fillAddress(addr){ document.getElementById('deliveryAddr').value = addr; }
function selectPay(lbl){ document.querySelectorAll('.co-pay-opt').forEach(function(l){l.classList.remove('selected');}); lbl.classList.add('selected'); }

function haversineKm(lat1,lng1,lat2,lng2){
    var R=6371, dLat=(lat2-lat1)*Math.PI/180, dLng=(lng2-lng1)*Math.PI/180;
    var a=Math.sin(dLat/2)*Math.sin(dLat/2)+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)*Math.sin(dLng/2);
    return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}

function updateDistanceFee(dLat, dLng){
    var kmFee=0;
    if(PER_KM_RATE>0 && VENDOR_LAT && VENDOR_LNG && dLat && dLng){
        var dist=haversineKm(parseFloat(VENDOR_LAT),parseFloat(VENDOR_LNG),parseFloat(dLat),parseFloat(dLng));
        if(dist>PER_KM_THRESH) kmFee=Math.round((dist-PER_KM_THRESH)*PER_KM_RATE*100)/100;
    }
    var kmRow=document.getElementById('coKmRow');
    var kmEl=document.getElementById('coKmFee');
    var totEl=document.getElementById('coTotal');
    var btnEl=document.getElementById('coBtnTotal');
    var delivEl=document.getElementById('coDelivery');
    var newDelivery=BASE_DELIVERY+kmFee;
    var newTotal=BASE_TOTAL+kmFee;
    if(kmRow) kmRow.style.display=kmFee>0?'':'none';
    if(kmEl)  kmEl.textContent=kmFee.toFixed(2)+SYM;
    if(delivEl) delivEl.textContent=newDelivery.toFixed(2)+SYM;
    if(totEl)  totEl.textContent=newTotal.toFixed(2)+SYM;
    if(btnEl)  btnEl.textContent=newTotal.toFixed(2)+SYM;
}

function acAddr(){
    var q = document.getElementById('deliveryAddr').value.trim();
    var ac = document.getElementById('addrAc');
    clearTimeout(acTimer);
    if(q.length < 3){ ac.style.display='none'; return; }
    acTimer = setTimeout(function(){
        fetch('https://nominatim.openstreetmap.org/search?format=json&q='+encodeURIComponent(q)+'&limit=5',{headers:{'Accept-Language':'ka,en'}})
            .then(function(r){return r.json();}).then(function(results){
                ac.innerHTML=''; if(!results.length){ac.style.display='none';return;}
                results.forEach(function(item){
                    var d=document.createElement('div'); d.className='co-ac-item';
                    d.textContent=item.display_name;
                    d.onclick=function(){
                        document.getElementById('deliveryAddr').value=item.display_name;
                        document.getElementById('deliveryLat').value=item.lat;
                        document.getElementById('deliveryLng').value=item.lon;
                        ac.style.display='none';
                        updateDistanceFee(item.lat, item.lon);
                    };
                    ac.appendChild(d);
                });
                ac.style.display='block';
            }).catch(function(){ac.style.display='none';});
    }, 350);
}
document.addEventListener('click', function(e){
    var ac = document.getElementById('addrAc');
    if(!ac.contains(e.target)&&e.target.id!=='deliveryAddr') ac.style.display='none';
});

document.getElementById('coForm').addEventListener('submit', function(){
    document.getElementById('coBtn').disabled=true;
    document.getElementById('coBtnTxt').textContent='Processing...';
});
</script>
