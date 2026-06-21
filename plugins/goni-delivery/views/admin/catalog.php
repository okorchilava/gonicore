<?php
$pageTitle = 'Catalog · '.e($vendor['name']);
$activeNav = 'delivery-vendors';
$vid = (int)$vendor['id'];
$topbarActions = '<a href="'.e($base).'/manage/delivery/vendors/'.$vid.'" class="btn btn-ghost" style="font-size:12px">← Vendor</a>';
$sym = '₾';

// Group products by category for stats
$catMap = [];
foreach($categories as $c) $catMap[(int)$c['id']] = $c;
$inStock = count(array_filter($products, fn($p)=>(int)$p['in_stock']===1));
$outStock = count($products) - $inStock;
?>

<!-- Info banner -->
<div style="background:linear-gradient(135deg,#fef3c7,#fde68a);border:2px solid #f59e0b;border-radius:16px;padding:20px 24px;margin-bottom:24px;display:flex;align-items:flex-start;gap:16px">
  <div style="font-size:36px;line-height:1">🏪</div>
  <div style="flex:1">
    <div style="font-size:16px;font-weight:900;color:#92400e;margin-bottom:4px">კატალოგი ვენდორს მართავს</div>
    <div style="font-size:13px;color:#78350f;line-height:1.5">
      პროდუქტების, კატეგორიების და ფასდაკლებების დამატება/რედაქტირება ხდება <strong>ვენდორის პორტალიდან</strong>.<br>
      ადმინისტრატორი ხედავს კატალოგს read-only რეჟიმში (ზედამხედველობის მიზნით).
    </div>
  </div>
  <?php if(!empty($vendor['vendor_token'])): ?>
  <a href="<?= e($base)?>/delivery/portal/<?= e($vendor['vendor_token'])?>" target="_blank"
     style="background:#f59e0b;color:#fff;border-radius:10px;padding:10px 18px;font-size:13px;font-weight:800;white-space:nowrap;text-decoration:none;display:inline-flex;align-items:center;gap:6px">
    🏪 Vendor Portal ↗
  </a>
  <?php else: ?>
  <a href="<?= e($base)?>/manage/delivery/vendors/<?= $vid?>"
     style="background:#f59e0b;color:#fff;border-radius:10px;padding:10px 18px;font-size:13px;font-weight:800;white-space:nowrap;text-decoration:none">
    🔑 Token-ის დამატება
  </a>
  <?php endif ?>
</div>

<!-- Stats -->
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px">
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px 20px;flex:1;min-width:120px;text-align:center">
    <div style="font-size:24px;font-weight:900;color:var(--accent)"><?= count($products)?></div>
    <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Products</div>
  </div>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px 20px;flex:1;min-width:120px;text-align:center">
    <div style="font-size:24px;font-weight:900;color:#22c55e"><?= $inStock?></div>
    <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">In Stock</div>
  </div>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px 20px;flex:1;min-width:120px;text-align:center">
    <div style="font-size:24px;font-weight:900;color:#ef4444"><?= $outStock?></div>
    <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Out of Stock</div>
  </div>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px 20px;flex:1;min-width:120px;text-align:center">
    <div style="font-size:24px;font-weight:900"><?= count($categories)?></div>
    <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Categories</div>
  </div>
</div>

<?php if(empty($products) && empty($categories)): ?>
<div style="text-align:center;padding:60px 20px;color:var(--muted)">
  <div style="font-size:48px;margin-bottom:12px">📦</div>
  <div style="font-size:14px;margin-bottom:8px">კატალოგი ცარიელია</div>
  <div style="font-size:12px">ვენდორმა უნდა დაამატოს პროდუქტები პორტალიდან</div>
</div>
<?php else: ?>

<style>
.ro-section{background:var(--surface);border:1px solid var(--border);border-radius:12px;margin-bottom:14px;overflow:hidden}
.ro-head{padding:12px 18px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);background:var(--surface2,#f8fafc)}
.ro-head-name{font-size:13px;font-weight:800}
.ro-row{display:flex;align-items:center;padding:11px 18px;border-bottom:1px solid var(--border2,#f1f5f9);gap:12px}
.ro-row:last-child{border-bottom:none}
.ro-name{font-size:13px;font-weight:700;flex:1}
.ro-desc{font-size:11.5px;color:var(--muted);margin-top:2px}
.ro-price{font-size:14px;font-weight:900;color:var(--accent);flex-shrink:0}
.ro-badge{font-size:11px;font-weight:700;padding:2px 9px;border-radius:999px;flex-shrink:0}
.ro-in{background:#f0fdf4;color:#166534}
.ro-out{background:#fef2f2;color:#b91c1c}
</style>

<?php
$byCategory = [];
foreach($products as $p) {
    $cid = (int)($p['category_id'] ?? 0);
    $byCategory[$cid][] = $p;
}
// Show categorized first, uncategorized last
uksort($byCategory, fn($a,$b) => $a===0 ? 1 : ($b===0 ? -1 : $a-$b));
?>

<?php foreach($byCategory as $cid => $prods): ?>
<div class="ro-section">
  <div class="ro-head">
    <span class="ro-head-name">
      <?= $cid===0 ? '📦 კატეგორიის გარეშე' : '📂 '.e($catMap[$cid]['name']??'?') ?>
    </span>
    <span style="font-size:12px;color:var(--muted)"><?= count($prods)?> product<?= count($prods)!==1?'s':''?></span>
  </div>
  <?php foreach($prods as $p): ?>
  <div class="ro-row">
    <div style="flex:1">
      <div class="ro-name"><?= e($p['name'])?></div>
      <?php if($p['description']): ?>
      <div class="ro-desc"><?= e(mb_substr($p['description'],0,80))?></div>
      <?php endif ?>
    </div>
    <?php if(($p['old_price'] ?? 0) > 0): ?>
    <span style="font-size:12px;color:var(--muted);text-decoration:line-through"><?= number_format((float)$p['old_price'],2).$sym?></span>
    <?php endif ?>
    <span class="ro-price"><?= number_format((float)$p['price'],2).$sym?></span>
    <span class="ro-badge <?= (int)$p['in_stock']===1?'ro-in':'ro-out'?>">
      <?= (int)$p['in_stock']===1?'✓ In Stock':'✗ Out'?>
    </span>
  </div>
  <?php endforeach ?>
</div>
<?php endforeach ?>

<?php endif ?>
