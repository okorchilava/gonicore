<?php
/**
 * GoniStore — Single Product page
 * Variables: $product, $variations, $related, $settings, $base
 */
$imgs    = json_decode((string)($product['images'] ?? ''), true) ?: [];
$thumb   = $imgs[0] ?? '';
$attrs   = json_decode((string)($product['attributes'] ?? ''), true) ?: [];
$onSale  = !empty($product['sale_price']);
$price   = $onSale ? (float)$product['sale_price'] : (float)$product['price'];
$symbol  = $settings['currency_symbol'] ?? '$';
$inStock = (int)($product['stock_qty'] ?? 0);
$manage  = !empty($product['manage_stock']);
?>
<style>
.gs-product-page{max-width:1200px;margin:0 auto;padding:40px 24px}
.gs-breadcrumb{font-size:13px;color:#64748b;margin-bottom:24px}
.gs-breadcrumb a{color:#10B27C;text-decoration:none}
.gs-breadcrumb a:hover{text-decoration:underline}
.gs-product-layout{display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:start}
.gs-gallery{position:sticky;top:24px}
.gs-gallery-main{border-radius:12px;overflow:hidden;background:#f8fafc;border:1px solid #e2e8f0;aspect-ratio:1;display:flex;align-items:center;justify-content:center}
.gs-gallery-main img{width:100%;height:100%;object-fit:cover;cursor:zoom-in;transition:transform .3s}
.gs-gallery-main img:hover{transform:scale(1.03)}
.gs-gallery-main-placeholder{font-size:80px;color:#cbd5e1}
.gs-gallery-thumbs{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap}
.gs-gallery-thumbs img{width:64px;height:64px;object-fit:cover;border-radius:8px;border:2px solid transparent;cursor:pointer;transition:border-color .15s}
.gs-gallery-thumbs img.active,.gs-gallery-thumbs img:hover{border-color:#10B27C}
.gs-product-info h1{font-size:26px;font-weight:800;color:#0f172a;margin-bottom:12px;line-height:1.25}
.gs-price-block{display:flex;align-items:baseline;gap:10px;margin-bottom:20px}
.gs-price-now{font-size:28px;font-weight:800;color:#10B27C}
.gs-price-old{font-size:17px;text-decoration:line-through;color:#94a3b8}
.gs-sale-badge{background:#ef4444;color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:20px;vertical-align:middle}
.gs-stock{font-size:13px;font-weight:600;margin-bottom:16px}
.gs-stock.in{color:#16a34a}.gs-stock.out{color:#dc2626}
.gs-sku{font-size:13px;color:#64748b;margin-bottom:16px}
.gs-desc{font-size:15px;line-height:1.7;color:#374151;margin-bottom:24px;border-top:1px solid #e2e8f0;padding-top:16px}
.gs-variations{margin-bottom:20px}
.gs-variations label{display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px}
.gs-variations select{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;color:#0f172a;background:#fff;cursor:pointer}
.gs-qty-row{display:flex;gap:10px;margin-bottom:20px;align-items:center}
.gs-qty-row label{font-size:13px;font-weight:700;color:#374151;white-space:nowrap}
.gs-qty-input{width:72px;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;text-align:center}
.gs-add-btn{flex:1;padding:12px 20px;background:#10B27C;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;transition:background .15s}
.gs-add-btn:hover{background:#0e9c6c}
.gs-add-btn:disabled{background:#94a3b8;cursor:not-allowed}
.gs-meta{font-size:13px;color:#64748b;margin-top:16px;border-top:1px solid #e2e8f0;padding-top:16px;display:flex;flex-direction:column;gap:6px}
.gs-meta span{font-weight:600;color:#374151}
.gs-related{margin-top:64px}
.gs-related h2{font-size:20px;font-weight:800;margin-bottom:20px;color:#0f172a}
.gs-related-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px}
.gs-related-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;transition:box-shadow .2s}
.gs-related-card:hover{box-shadow:0 6px 24px rgba(0,0,0,.09)}
.gs-related-thumb{height:160px;background:#f8fafc;overflow:hidden;display:flex;align-items:center;justify-content:center}
.gs-related-thumb img{width:100%;height:100%;object-fit:cover}
.gs-related-thumb-ph{font-size:36px;color:#cbd5e1}
.gs-related-body{padding:12px}
.gs-related-name{font-size:14px;font-weight:700;margin-bottom:4px}
.gs-related-name a{color:#0f172a;text-decoration:none}
.gs-related-name a:hover{color:#10B27C}
.gs-related-price{font-size:15px;font-weight:800;color:#10B27C}
@media(max-width:768px){.gs-product-layout{grid-template-columns:1fr}.gs-gallery{position:static}}
</style>

<div class="gs-product-page">
    <!-- Breadcrumb -->
    <div class="gs-breadcrumb">
        <a href="<?= e($base) ?>/shop">Shop</a> &rsaquo;
        <?php if (!empty($product['category_name'])): ?>
        <a href="<?= e($base) ?>/shop?category=<?= e($product['category_slug'] ?? '') ?>"><?= e($product['category_name']) ?></a> &rsaquo;
        <?php endif ?>
        <?= e($product['name']) ?>
    </div>

    <div class="gs-product-layout">
        <!-- Gallery -->
        <div class="gs-gallery">
            <div class="gs-gallery-main" id="gs-main-img">
                <?php if ($thumb): ?>
                <img src="<?= e($thumb) ?>" alt="<?= e($product['name']) ?>" id="gs-hero-img">
                <?php else: ?>
                <div class="gs-gallery-main-placeholder">📦</div>
                <?php endif ?>
            </div>
            <?php if (count($imgs) > 1): ?>
            <div class="gs-gallery-thumbs">
                <?php foreach ($imgs as $i => $img): ?>
                <img src="<?= e($img) ?>" alt="" class="<?= $i===0?'active':'' ?>"
                     onclick="document.getElementById('gs-hero-img').src=this.src;document.querySelectorAll('.gs-gallery-thumbs img').forEach(t=>t.classList.remove('active'));this.classList.add('active')">
                <?php endforeach ?>
            </div>
            <?php endif ?>
        </div>

        <!-- Info -->
        <div class="gs-product-info">
            <h1><?= e($product['name']) ?></h1>

            <div class="gs-price-block">
                <?php if ($onSale): ?>
                <span class="gs-price-old"><?= $symbol ?><?= number_format((float)$product['price'],2) ?></span>
                <?php endif ?>
                <span class="gs-price-now"><?= $symbol ?><?= number_format($price,2) ?></span>
                <?php if ($onSale): ?>
                <span class="gs-sale-badge">SALE</span>
                <?php endif ?>
            </div>

            <?php if ($manage): ?>
            <div class="gs-stock <?= $inStock>0?'in':'out' ?>">
                <?= $inStock>0 ? '✔ In Stock ('.$inStock.' available)' : '✘ Out of Stock' ?>
            </div>
            <?php endif ?>

            <?php if (!empty($product['short_description'])): ?>
            <div class="gs-desc"><?= $product['short_description'] ?></div>
            <?php elseif (!empty($product['description'])): ?>
            <div class="gs-desc"><?= excerpt(strip_tags($product['description']), 300) ?></div>
            <?php endif ?>

            <form method="POST" action="<?= e($base) ?>/cart/add">
                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

                <?php if (!empty($variations)): ?>
                <div class="gs-variations">
                    <label for="gs-variation">Select Option</label>
                    <select name="variation_id" id="gs-variation" required>
                        <option value="">— Choose —</option>
                        <?php foreach ($variations as $v):
                            $vatts = json_decode((string)($v['attributes']??''), true) ?: [];
                            $label = implode(' / ', array_map(fn($k,$val)=>$k.': '.$val, array_keys($vatts), $vatts));
                            if (!$label) $label = 'Option #'.$v['id'];
                        ?>
                        <option value="<?= (int)$v['id'] ?>" <?= (isset($v['stock_qty'])&&(int)$v['stock_qty']===0)?'disabled':'' ?>>
                            <?= e($label) ?> — <?= $symbol ?><?= number_format((float)($v['price']??$price),2) ?>
                            <?= (isset($v['stock_qty'])&&(int)$v['stock_qty']===0)?' (Out of stock)':'' ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <?php endif ?>

                <div class="gs-qty-row">
                    <label for="gs-qty">Qty</label>
                    <input type="number" name="quantity" id="gs-qty" class="gs-qty-input" value="1" min="1" <?= ($manage&&$inStock>0)?'max="'.$inStock.'"':'' ?>>
                    <button type="submit" class="gs-add-btn" <?= ($manage&&$inStock<=0)?'disabled':'' ?>>
                        🛒 Add to Cart
                    </button>
                </div>
            </form>

            <div class="gs-meta">
                <?php if (!empty($product['sku'])): ?>
                <div>SKU: <span><?= e($product['sku']) ?></span></div>
                <?php endif ?>
                <?php if (!empty($product['category_name'])): ?>
                <div>Category: <span><a href="<?= e($base) ?>/shop?category=<?= e($product['category_slug']??'') ?>" style="color:#10B27C;text-decoration:none"><?= e($product['category_name']) ?></a></span></div>
                <?php endif ?>
                <?php if (!empty($product['weight'])): ?>
                <div>Weight: <span><?= e($product['weight']) ?></span></div>
                <?php endif ?>
            </div>
        </div>
    </div>

    <?php if (!empty($product['description'])): ?>
    <div style="margin-top:48px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:32px">
        <h2 style="font-size:18px;font-weight:800;margin-bottom:16px;color:#0f172a">Product Description</h2>
        <div style="font-size:15px;line-height:1.8;color:#374151"><?= $product['description'] ?></div>
    </div>
    <?php endif ?>

    <?php if (!empty($related)): ?>
    <div class="gs-related">
        <h2>Related Products</h2>
        <div class="gs-related-grid">
            <?php foreach ($related as $r):
                $rImgs  = json_decode((string)($r['images']??''), true) ?: [];
                $rThumb = $rImgs[0] ?? '';
                $rPrice = !empty($r['sale_price']) ? $r['sale_price'] : $r['price'];
            ?>
            <div class="gs-related-card">
                <div class="gs-related-thumb">
                    <?php if ($rThumb): ?>
                    <img src="<?= e($rThumb) ?>" alt="<?= e($r['name']) ?>" loading="lazy">
                    <?php else: ?>
                    <div class="gs-related-thumb-ph">📦</div>
                    <?php endif ?>
                </div>
                <div class="gs-related-body">
                    <div class="gs-related-name"><a href="<?= e($base) ?>/shop/<?= e($r['slug']) ?>"><?= e($r['name']) ?></a></div>
                    <div class="gs-related-price"><?= $symbol ?><?= number_format((float)$rPrice,2) ?></div>
                </div>
            </div>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>
</div>
