<?php
/**
 * GoniStore — Shop page
 * Variables: $products, $total, $pages, $page, $cats, $cat, $settings, $base
 */
?>
<style>
.gs-shop{max-width:1200px;margin:0 auto;padding:40px 24px}
.gs-shop-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.gs-shop-header h1{font-size:28px;font-weight:800}
.gs-cat-filter{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px}
.gs-cat-filter a{padding:6px 14px;border-radius:20px;font-size:13px;font-weight:600;text-decoration:none;border:1.5px solid #e2e8f0;color:#64748b;transition:all .15s}
.gs-cat-filter a:hover,.gs-cat-filter a.active{background:#10B27C;color:#fff;border-color:#10B27C}
.gs-product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:24px}
.gs-product-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;transition:box-shadow .2s,transform .2s}
.gs-product-card:hover{box-shadow:0 8px 32px rgba(0,0,0,.1);transform:translateY(-2px)}
.gs-product-thumb{height:220px;overflow:hidden;position:relative;background:#f8fafc}
.gs-product-thumb img{width:100%;height:100%;object-fit:cover;transition:transform .3s}
.gs-product-card:hover .gs-product-thumb img{transform:scale(1.04)}
.gs-product-thumb-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:48px;color:#cbd5e1}
.gs-product-badge{position:absolute;top:10px;left:10px;background:#ef4444;color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:20px}
.gs-product-body{padding:16px}
.gs-product-name{font-size:15px;font-weight:700;margin-bottom:6px;color:#0f172a}
.gs-product-name a{color:inherit;text-decoration:none}
.gs-product-name a:hover{color:#10B27C}
.gs-product-price{font-size:17px;font-weight:800;color:#10B27C;margin-bottom:12px}
.gs-product-price .original{font-size:13px;text-decoration:line-through;color:#94a3b8;font-weight:400;margin-right:6px}
.gs-add-btn{width:100%;padding:9px;background:#10B27C;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;transition:background .15s}
.gs-add-btn:hover{background:#0e9c6c}
.gs-pagination{display:flex;gap:6px;justify-content:center;margin-top:40px}
.gs-pagination a{padding:8px 14px;border-radius:7px;border:1px solid #e2e8f0;font-size:13px;text-decoration:none;color:#0f172a;transition:all .15s}
.gs-pagination a:hover,.gs-pagination a.active{background:#10B27C;color:#fff;border-color:#10B27C}
@media(max-width:640px){.gs-product-grid{grid-template-columns:repeat(2,1fr);gap:12px}.gs-product-thumb{height:160px}}
</style>

<div class="gs-shop">
    <div class="gs-shop-header">
        <h1>Shop</h1>
        <span style="font-size:14px;color:#64748b"><?= number_format($total) ?> products</span>
    </div>

    <!-- Category filter -->
    <?php if (!empty($cats)): ?>
    <div class="gs-cat-filter">
        <a href="?<?= http_build_query(array_filter(['page'=>1])) ?>" class="<?= empty($cat)?'active':'' ?>">All</a>
        <?php foreach ($cats as $c): ?>
        <a href="?category=<?= e($c['slug']) ?>" class="<?= $cat===$c['slug']?'active':'' ?>"><?= e($c['name']) ?></a>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <?php if (empty($products)): ?>
    <div style="text-align:center;padding:80px 20px;color:#64748b">
        <div style="font-size:48px;margin-bottom:16px">📦</div>
        <p>No products found.</p>
    </div>
    <?php else: ?>
    <div class="gs-product-grid">
        <?php foreach ($products as $p):
            $imgs    = json_decode((string)$p['images'],true) ?: [];
            $thumb   = $imgs[0] ?? '';
            $onSale  = $store->isOnSale($p);
            $price   = $store->effectivePrice($p);
            $discPct = $store->discountPercent($p);
        ?>
        <div class="gs-product-card">
            <div class="gs-product-thumb">
                <?php if ($thumb): ?>
                <img src="<?= e($thumb) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                <?php else: ?>
                <div class="gs-product-thumb-placeholder">📦</div>
                <?php endif ?>
                <?php if ($onSale): ?>
                <span class="gs-product-badge">-<?= $discPct ?>%</span>
                <?php endif ?>
                <?php if (!empty($p['featured'])): ?>
                <span class="gs-product-badge" style="left:auto;right:10px;background:#f59e0b">Featured</span>
                <?php endif ?>
            </div>
            <div class="gs-product-body">
                <div class="gs-product-name"><a href="<?= e($base) ?>/shop/<?= e($p['slug']) ?>"><?= e($p['name']) ?></a></div>
                <div class="gs-product-price">
                    <?php if ($onSale): ?>
                    <span class="original"><?= $store->formatPrice((float)$p['price']) ?></span>
                    <?php endif ?>
                    <?= $store->formatPrice($price) ?>
                </div>
                <form method="POST" action="<?= e($base) ?>/cart/add">
                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                    <input type="hidden" name="redirect" value="<?= e($base) ?>/shop">
                    <button type="submit" class="gs-add-btn">Add to Cart 🛒</button>
                </form>
            </div>
        </div>
        <?php endforeach ?>
    </div>

    <?php if ($pages > 1): ?>
    <div class="gs-pagination">
        <?php for ($i=1;$i<=$pages;$i++): ?>
        <a href="?page=<?= $i ?><?= $cat?'&category='.urlencode($cat):'' ?>" class="<?= $i===$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor ?>
    </div>
    <?php endif ?>
    <?php endif ?>
</div>
