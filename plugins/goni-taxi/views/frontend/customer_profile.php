<?php
$slug  = $taxi->setting('page_slug','taxi');
$brand = $taxi->setting('brand_name','GoniTaxi');
$sym   = $taxi->setting('currency_symbol','₾');
?>
<style>
.prof-wrap{min-height:calc(100vh - 80px);background:#f1f5f9;padding:32px 16px}
.prof-box{max-width:520px;margin:0 auto}
.prof-head{background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:20px;padding:28px 24px;color:#fff;margin-bottom:20px;display:flex;align-items:center;gap:16px}
.prof-av{width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0}
.prof-name{font-size:20px;font-weight:900}
.prof-phone{font-size:13px;opacity:.75;margin-top:3px}
.prof-card{background:#fff;border-radius:16px;padding:22px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.prof-card-title{font-size:12px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:16px}
.prof-field{margin-bottom:14px}
.prof-label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px;display:block}
.prof-input{width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:11px 14px;font-size:14px;font-family:inherit;outline:none;transition:border-color .15s;background:#fff;box-sizing:border-box}
.prof-input:focus{border-color:#4f46e5;box-shadow:0 0 0 3px rgba(79,70,229,.1)}
.prof-addr-row{position:relative}
.prof-addr-ac{position:absolute;z-index:999;background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;top:calc(100% + 4px);left:0;width:100%;box-shadow:0 8px 24px rgba(0,0,0,.1);max-height:180px;overflow-y:auto;display:none}
.prof-addr-item{padding:10px 14px;font-size:13px;cursor:pointer;border-bottom:1px solid #f1f5f9}
.prof-addr-item:hover{background:#f8fafc}
.prof-save{width:100%;padding:13px;background:#4f46e5;color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:800;cursor:pointer;font-family:inherit;transition:all .15s}
.prof-save:hover{background:#4338ca}
.prof-success{background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:10px;padding:12px 14px;color:#166534;font-size:13px;margin-bottom:16px}
.prof-back{display:flex;align-items:center;gap:6px;color:#4f46e5;font-size:13px;font-weight:700;text-decoration:none;margin-bottom:16px}
.prof-back:hover{color:#4338ca}
</style>

<div class="prof-wrap">
  <div class="prof-box">
    <a href="<?= e($base).'/'.$slug ?>" class="prof-back">← უკან / Book Ride</a>

    <?php if($saved): ?>
    <div class="prof-success">✅ პროფილი შენახულია</div>
    <?php endif ?>

    <div class="prof-head">
      <div class="prof-av">👤</div>
      <div>
        <div class="prof-name"><?= e($customer['name'] ?: 'მომხმარებელი') ?></div>
        <div class="prof-phone">📱 <?= e($customer['phone']) ?></div>
      </div>
    </div>

    <form method="POST" action="<?= e($base) ?>/taxi/profile/update">

      <!-- Basic info -->
      <div class="prof-card">
        <div class="prof-card-title">👤 პირადი ინფო</div>
        <div class="prof-field">
          <label class="prof-label">სახელი</label>
          <input type="text" name="name" class="prof-input" value="<?= e($customer['name'] ?? '') ?>" placeholder="შენი სახელი">
        </div>
        <div class="prof-field">
          <label class="prof-label">Email (სურვილისამებრ)</label>
          <input type="email" name="email" class="prof-input" value="<?= e($customer['email'] ?? '') ?>" placeholder="email@example.com">
        </div>
      </div>

      <!-- Saved addresses -->
      <div class="prof-card">
        <div class="prof-card-title">📍 შენახული მისამართები</div>

        <!-- Home -->
        <div class="prof-field">
          <label class="prof-label">🏠 სახლი</label>
          <div class="prof-addr-row">
            <input type="text" name="home_address" id="homeAddr" class="prof-input"
                   value="<?= e($customer['home_address'] ?? '') ?>"
                   placeholder="სახლის მისამართი" autocomplete="off"
                   oninput="acSearch(this,'homeAc','homeLat','homeLng')">
            <input type="hidden" name="home_lat" id="homeLat" value="<?= e($customer['home_lat'] ?? '') ?>">
            <input type="hidden" name="home_lng" id="homeLng" value="<?= e($customer['home_lng'] ?? '') ?>">
            <div id="homeAc" class="prof-addr-ac"></div>
          </div>
        </div>

        <!-- Work -->
        <div class="prof-field">
          <label class="prof-label">🏢 სამსახური</label>
          <div class="prof-addr-row">
            <input type="text" name="work_address" id="workAddr" class="prof-input"
                   value="<?= e($customer['work_address'] ?? '') ?>"
                   placeholder="სამსახურის მისამართი" autocomplete="off"
                   oninput="acSearch(this,'workAc','workLat','workLng')">
            <input type="hidden" name="work_lat" id="workLat" value="<?= e($customer['work_lat'] ?? '') ?>">
            <input type="hidden" name="work_lng" id="workLng" value="<?= e($customer['work_lng'] ?? '') ?>">
            <div id="workAc" class="prof-addr-ac"></div>
          </div>
        </div>

        <p style="font-size:12px;color:#94a3b8;margin:8px 0 0">შენახული მისამართები ჩანს სწრაფი წვდომისთვის დაჯავშნისას</p>
      </div>

      <button type="submit" class="prof-save">💾 შენახვა</button>
    </form>
  </div>
</div>

<script>
var acTimers = {};
function acSearch(input, acId, latId, lngId) {
    var q = input.value.trim();
    var ac = document.getElementById(acId);
    clearTimeout(acTimers[acId]);
    if (q.length < 3) { ac.style.display='none'; return; }

    acTimers[acId] = setTimeout(function(){
        fetch('https://nominatim.openstreetmap.org/search?format=json&q='+encodeURIComponent(q)+'&limit=5&countrycodes=ge', {
            headers: {'Accept-Language':'ka,en'}
        }).then(function(r){return r.json();}).then(function(results){
            ac.innerHTML = '';
            if (!results.length) { ac.style.display='none'; return; }
            results.forEach(function(item){
                var div = document.createElement('div');
                div.className = 'prof-addr-item';
                div.textContent = item.display_name;
                div.onclick = function(){
                    input.value = item.display_name;
                    document.getElementById(latId).value = item.lat;
                    document.getElementById(lngId).value = item.lon;
                    ac.style.display='none';
                };
                ac.appendChild(div);
            });
            ac.style.display = 'block';
        }).catch(function(){ ac.style.display='none'; });
    }, 350);
}
document.addEventListener('click', function(e){
    document.querySelectorAll('.prof-addr-ac').forEach(function(ac){
        if (!ac.contains(e.target)) ac.style.display='none';
    });
});
</script>
