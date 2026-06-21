<?php
$sym   = $delivery->setting('currency_symbol','₾');
$slug  = $delivery->setting('page_slug','delivery');
$brand = $delivery->setting('brand_name','GoniDelivery');

/**
 * Returns true when the vendor is open and closes within 30 minutes.
 */
function gdIsClosingSoon(array $v): bool {
    if (empty($v['close_time'])) return false;
    $now   = time();
    $close = strtotime(date('Y-m-d') . ' ' . $v['close_time']);
    if ($close === false) return false;
    // Handle past-midnight edge: if close is already behind today, try tomorrow
    if ($close < $now) $close = strtotime('+1 day', $close);
    $minsLeft = ($close - $now) / 60;
    return $minsLeft > 0 && $minsLeft <= 30;
}
?>
<style>
.gd-wrap{max-width:900px;margin:0 auto;padding:28px 16px 48px}
.gd-hero{background:linear-gradient(135deg,#f59e0b,#ef4444);border-radius:20px;padding:32px 28px;color:#fff;margin-bottom:28px;position:relative;overflow:hidden}
.gd-hero h1{font-size:28px;font-weight:900;margin-bottom:6px}
.gd-hero p{font-size:14px;opacity:.85}
.gd-hero-icon{position:absolute;right:24px;bottom:16px;font-size:72px;opacity:.2}
.gd-section{font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.6px;margin-bottom:14px}
.gd-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:32px}
.gd-card{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.07),0 4px 12px rgba(0,0,0,.05);cursor:pointer;text-decoration:none!important;color:inherit;display:block}
.gd-card:hover,.gd-card:focus,.gd-card:visited{text-decoration:none!important;color:inherit}
.gd-card-img{height:140px;background:linear-gradient(135deg,#fef3c7,#fde68a);display:flex;align-items:center;justify-content:center;font-size:52px;position:relative}
.gd-card-body{padding:14px 16px}
.gd-card-name{font-size:15px;font-weight:800;margin-bottom:4px}
.gd-card-sub{font-size:12px;color:#64748b;margin-bottom:8px}
.gd-card-meta{display:flex;align-items:center;gap:10px;font-size:12px;flex-wrap:wrap}
.gd-badge-open{background:#f0fdf4;color:#166534;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700}
.gd-badge-closed{background:#f1f5f9;color:#64748b;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700}
.gd-badge-closing{background:#fef2f2;color:#dc2626;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;animation:gdClosingPulse 1.1s ease-in-out infinite}
@keyframes gdClosingPulse{0%,100%{opacity:1;background:#fef2f2}50%{opacity:.55;background:#fee2e2}}
.gd-topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
</style>

<div class="gd-wrap">
  <div class="gd-topbar">
    <?php if(!empty($customer)): ?>
    <span style="font-size:13px;color:#64748b">👤 <?= e($customer['name']?:$customer['phone'])?> · <a href="<?= e($base).'/'.$slug?>/logout" style="color:#ef4444;font-weight:700;font-size:12px">გასვლა</a></span>
    <?php else: ?>
    <a href="<?= e($base).'/'.$slug?>/auth" style="font-size:13px;color:#f59e0b;font-weight:700">შესვლა / რეგისტრაცია</a>
    <?php endif ?>
    <span style="font-size:12px;color:#64748b"><?= count($vendors)?> vendor<?= count($vendors)!==1?'s':''?></span>
  </div>

  <div class="gd-hero">
    <h1>🛵 <?= e($brand)?></h1>
    <p>შეუკვეთე, ჩვენ მოგიტანთ</p>
    <div class="gd-hero-icon">🍕</div>
  </div>

  <?php if($error): ?><div style="background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:12px 16px;color:#b91c1c;font-size:13px;margin-bottom:16px">⚠️ <?= e($error)?></div><?php endif ?>

  <?php
  $featured = array_values(array_filter($vendors, fn($v)=>(bool)$v['is_featured']&&$v['status']!=='inactive'));
  $regular  = array_values(array_filter($vendors, fn($v)=>!$v['is_featured']&&$v['status']!=='inactive'));
  ?>

  <?php if(!empty($featured)): ?>
  <div class="gd-section">⭐ Featured</div>
  <div class="gd-grid">
  <?php foreach($featured as $v): $open=$delivery->isVendorOpen($v); $closingSoon=$open&&gdIsClosingSoon($v); ?>
    <a href="<?= e($base).'/'.$slug?>/vendor/<?= e($v['slug'])?>" class="gd-card">
      <div class="gd-card-img" style="<?= $v['cover_image']?'background:url('.e($v['cover_image']).') center/cover;font-size:0':''?>">
        <?= !$v['cover_image'] ? ($v['category']==='grocery'?'🛒':($v['category']==='pharmacy'?'💊':'🍽')) : '' ?>
        <span style="position:absolute;top:8px;left:8px;background:#f59e0b;color:#fff;font-size:10px;font-weight:800;padding:2px 8px;border-radius:999px">⭐ Featured</span>
        <?php if($closingSoon): ?>
        <span style="position:absolute;top:8px;right:8px" class="gd-badge-closing">🔴 მალე იკეტება</span>
        <?php endif ?>
      </div>
      <div class="gd-card-body">
        <div class="gd-card-name"><?= e($v['name'])?></div>
        <div class="gd-card-sub"><?= e($v['cuisine_tags']?:$v['category'])?></div>
        <div class="gd-card-meta">
          <span class="<?= $open?'gd-badge-open':'gd-badge-closed'?>"><?= $open?'✓ Open':'Closed'?></span>
          <?php if($closingSoon): ?><span class="gd-badge-closing">🔴 მალე იკეტება</span><?php endif ?>
          <?php if($v['rating']>0): ?><span style="color:#f59e0b;font-weight:700">★<?= number_format((float)$v['rating'],1)?></span><?php endif ?>
          <span style="color:#64748b">🛵 <?= number_format((float)$v['delivery_fee'],2).$sym?></span>
          <span style="color:#64748b">⏱~<?= (int)$v['prep_time_min']?>min</span>
        </div>
      </div>
    </a>
  <?php endforeach ?>
  </div>
  <?php endif ?>

  <div class="gd-section">🍽 All Vendors</div>
  <?php if(empty($regular)&&empty($featured)): ?>
  <div style="text-align:center;padding:60px 20px;color:#64748b"><div style="font-size:48px;margin-bottom:12px">🏪</div><div>No vendors available yet.</div></div>
  <?php else: ?>
  <div class="gd-grid">
  <?php foreach($regular as $v): $open=$delivery->isVendorOpen($v); $closingSoon=$open&&gdIsClosingSoon($v); ?>
    <a href="<?= e($base).'/'.$slug?>/vendor/<?= e($v['slug'])?>" class="gd-card">
      <div class="gd-card-img" style="<?= $v['cover_image']?'background:url('.e($v['cover_image']).') center/cover;font-size:0':''?>">
        <?= !$v['cover_image'] ? ($v['category']==='grocery'?'🛒':($v['category']==='pharmacy'?'💊':'🍽')) : '' ?>
        <?php if($closingSoon): ?>
        <span style="position:absolute;top:8px;right:8px" class="gd-badge-closing">🔴 მალე იკეტება</span>
        <?php endif ?>
      </div>
      <div class="gd-card-body">
        <div class="gd-card-name"><?= e($v['name'])?></div>
        <div class="gd-card-sub"><?= e($v['cuisine_tags']?:$v['category'])?></div>
        <div class="gd-card-meta">
          <span class="<?= $open?'gd-badge-open':'gd-badge-closed'?>"><?= $open?'✓ Open':'Closed'?></span>
          <?php if($closingSoon): ?><span class="gd-badge-closing">🔴 მალე იკეტება</span><?php endif ?>
          <?php if($v['rating']>0): ?><span style="color:#f59e0b;font-weight:700">★<?= number_format((float)$v['rating'],1)?></span><?php endif ?>
          <span style="color:#64748b">🛵 <?= number_format((float)$v['delivery_fee'],2).$sym?></span>
          <span style="color:#64748b">⏱~<?= (int)$v['prep_time_min']?>min</span>
        </div>
        <?php if($v['min_order']>0): ?><div style="font-size:11px;color:#94a3b8;margin-top:6px">Min: <?= number_format((float)$v['min_order'],2).$sym?></div><?php endif ?>
      </div>
    </a>
  <?php endforeach ?>
  </div>
  <?php endif ?>
</div>
