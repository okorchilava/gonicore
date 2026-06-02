<?php
$isEdit    = !empty($product);
$pageTitle = $isEdit ? 'Edit: '.e($product['name']) : 'New Product';
$activeNav = 'store';
$formAction = $isEdit
    ? e($base).'/manage/store/products/'.(int)$product['id'].'/edit'
    : e($base).'/manage/store/products/new';
ob_start(); ?>
<a href="<?= e($base) ?>/manage/store/products" class="topbar-btn ghost">← Products</a>
<?php $topbarActions = ob_get_clean(); ?>

<style>
.gs-product-wrap{display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;max-width:1200px}
@media(max-width:900px){.gs-product-wrap{grid-template-columns:1fr}}
.gs-tabs{display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:20px}
.gs-tab{padding:9px 16px;font-size:13px;font-weight:600;color:var(--muted);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px}
.gs-tab.active{color:var(--accent);border-color:var(--accent)}
.gs-tab-panel{display:none}.gs-tab-panel.active{display:block}
.gs-attr-row{display:flex;gap:8px;margin-bottom:8px;align-items:center}
</style>

<form method="POST" action="<?= $formAction ?>" id="gsProductForm">
<div class="gs-product-wrap">
    <!-- Main -->
    <div>
        <!-- Name -->
        <div style="margin-bottom:16px">
            <input type="text" name="name" class="form-input" value="<?= e($product['name'] ?? '') ?>"
                   placeholder="Product name" style="font-size:20px;font-weight:700;padding:14px 16px;border-radius:10px"
                   oninput="autoSlug(this.value)" required>
        </div>

        <!-- Slug -->
        <div style="margin-bottom:16px;display:flex;align-items:center;gap:8px">
            <span style="font-size:12px;color:var(--muted)">Slug:</span>
            <input type="text" name="slug" id="gsSlug" class="form-input"
                   value="<?= e($product['slug'] ?? '') ?>"
                   style="font-size:13px;padding:6px 10px;font-family:monospace;flex:1">
        </div>

        <!-- Tabs -->
        <div class="gs-tabs">
            <div class="gs-tab active" onclick="showTab('general',this)">General</div>
            <div class="gs-tab" onclick="showTab('description',this)">Description</div>
            <div class="gs-tab" onclick="showTab('images',this)">Images</div>
            <div class="gs-tab" onclick="showTab('attributes',this)">Attributes</div>
            <div class="gs-tab" onclick="showTab('seo',this)">SEO</div>
            <?php if ($isEdit && ($product['type'] ?? '') === 'variable'): ?>
            <div class="gs-tab" onclick="showTab('variations',this)">Variations</div>
            <?php endif ?>
        </div>

        <!-- General -->
        <div class="gs-tab-panel active" id="gstab-general">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                <div class="form-group">
                    <label class="form-label">Regular Price</label>
                    <input type="text" name="price" class="form-input" value="<?= e($product['price'] ?? '0') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Sale Price</label>
                    <input type="text" name="sale_price" class="form-input" value="<?= e($product['sale_price'] ?? '') ?>" placeholder="Leave empty = no sale">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:12px">
                <div class="form-group">
                    <label class="form-label">SKU</label>
                    <input type="text" name="sku" class="form-input" value="<?= e($product['sku'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Weight (kg)</label>
                    <input type="text" name="weight" class="form-input" value="<?= e($product['weight'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <?php foreach (['simple'=>'Simple','variable'=>'Variable','virtual'=>'Virtual','downloadable'=>'Downloadable'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= ($product['type']??'simple')===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
            <div style="margin-bottom:12px">
                <label class="form-label" style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="manage_stock" id="manageStockChk" value="1"
                           <?= !empty($product['manage_stock'])?'checked':'' ?> onchange="toggleStock(this.checked)">
                    Manage Stock
                </label>
            </div>
            <div id="stockWrap" style="<?= empty($product['manage_stock'])?'display:none':'' ?>">
                <div class="form-group" style="max-width:160px">
                    <label class="form-label">Stock Quantity</label>
                    <input type="number" name="stock" class="form-input" value="<?= (int)($product['stock'] ?? 0) ?>">
                </div>
            </div>
            <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:12px">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13.5px">
                    <input type="checkbox" name="featured" value="1" <?= !empty($product['featured'])?'checked':'' ?>> Featured
                </label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13.5px">
                    <input type="checkbox" name="virtual" value="1" <?= !empty($product['virtual'])?'checked':'' ?>> Virtual
                </label>
            </div>
        </div>

        <!-- Description -->
        <div class="gs-tab-panel" id="gstab-description">
            <div class="form-group" style="margin-bottom:12px">
                <label class="form-label">Short Description</label>
                <textarea name="short_description" class="form-textarea" style="min-height:80px"><?= e($product['short_description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Full Description</label>
                <?php
                $editorName  = 'description';
                $editorId    = 'gsProductDesc';
                $editorValue = $product['description'] ?? '';
                $editorHeight = '380px';
                $editorPath = dirname(__DIR__,3).'/themes/default/views/manage/_editor.php';
                if (is_file($editorPath)) include $editorPath;
                else: ?>
                <textarea name="description" class="form-textarea" style="min-height:200px"><?= e($editorValue) ?></textarea>
                <?php endif ?>
            </div>
        </div>

        <!-- Images -->
        <div class="gs-tab-panel" id="gstab-images">
            <div class="form-group" style="margin-bottom:16px">
                <label class="form-label">Main Images (one URL per line)</label>
                <textarea name="images" class="form-textarea" rows="5" placeholder="https://example.com/image.jpg" style="font-family:monospace;font-size:12px"><?= e(implode("\n", $product['images'] ?? [])) ?></textarea>
                <div style="font-size:12px;color:var(--muted);margin-top:4px">First image is the product thumbnail.</div>
            </div>
            <?php
            $imgs = $product['images'] ?? [];
            if (!empty($imgs)): ?>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px">
                <?php foreach ($imgs as $img): ?>
                <img src="<?= e($img) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:6px;border:1px solid var(--border)">
                <?php endforeach ?>
            </div>
            <?php endif ?>
        </div>

        <!-- Attributes -->
        <div class="gs-tab-panel" id="gstab-attributes">
            <div id="attrs-wrap">
                <?php foreach ($product['attributes'] ?? [] as $k => $v): ?>
                <div class="gs-attr-row">
                    <input type="text" name="attributes[][name]"  class="form-input" value="<?= e($k) ?>" placeholder="e.g. Color" style="flex:1">
                    <input type="text" name="attributes[][value]" class="form-input" value="<?= e($v) ?>" placeholder="e.g. Red, Blue" style="flex:2">
                    <button type="button" onclick="this.closest('.gs-attr-row').remove()" style="background:none;border:none;cursor:pointer;font-size:18px;color:var(--muted)">✕</button>
                </div>
                <?php endforeach ?>
            </div>
            <button type="button" onclick="addAttr()" class="btn btn-ghost" style="margin-top:8px">+ Add Attribute</button>
        </div>

        <!-- SEO -->
        <div class="gs-tab-panel" id="gstab-seo">
            <div class="form-group" style="margin-bottom:12px">
                <label class="form-label">Meta Title</label>
                <input type="text" name="meta_title" class="form-input" value="<?= e($product['meta_title'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Meta Description</label>
                <textarea name="meta_description" class="form-textarea" rows="3"><?= e($product['meta_description'] ?? '') ?></textarea>
            </div>
        </div>

        <?php if ($isEdit && ($product['type'] ?? '') === 'variable'): ?>
        <!-- Variations -->
        <div class="gs-tab-panel" id="gstab-variations">
            <p style="font-size:13px;color:var(--muted);margin-bottom:16px">Variations are managed after saving the product.</p>
            <div id="variationsWrap">
                <?php foreach ($variations ?? [] as $v): ?>
                <div class="card" style="margin-bottom:12px;padding:14px">
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:8px;align-items:end">
                        <div><label class="form-label" style="font-size:11px">SKU</label><input type="text" name="var_sku[]" class="form-input" value="<?= e($v['sku']) ?>" style="font-size:13px"></div>
                        <div><label class="form-label" style="font-size:11px">Price</label><input type="text" name="var_price[]" class="form-input" value="<?= e($v['price']) ?>" style="font-size:13px"></div>
                        <div><label class="form-label" style="font-size:11px">Sale Price</label><input type="text" name="var_sale_price[]" class="form-input" value="<?= e($v['sale_price'] ?? '') ?>" style="font-size:13px"></div>
                        <div><label class="form-label" style="font-size:11px">Stock</label><input type="number" name="var_stock[]" class="form-input" value="<?= e($v['stock'] ?? '') ?>" style="font-size:13px"></div>
                        <div><input type="hidden" name="var_id[]" value="<?= (int)$v['id'] ?>"></div>
                    </div>
                    <div style="margin-top:8px">
                        <label class="form-label" style="font-size:11px">Attributes (JSON)</label>
                        <input type="text" name="var_attrs[]" class="form-input" value="<?= e(json_encode($v['attributes'])) ?>" style="font-size:12px;font-family:monospace">
                    </div>
                </div>
                <?php endforeach ?>
            </div>
        </div>
        <?php endif ?>
    </div>

    <!-- Sidebar -->
    <div style="display:flex;flex-direction:column;gap:14px">
        <div class="card">
            <div class="card-header"><h3>Publish</h3></div>
            <div class="card-body" style="padding:16px">
                <div style="margin-bottom:14px">
                    <label class="form-label">Status</label>
                    <?php foreach (['draft'=>['Draft','#f59e0b'],'published'=>['Published','#10b981'],'archived'=>['Archived','#94a3b8']] as $v=>[$l,$c]): ?>
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;cursor:pointer;font-size:13.5px">
                        <input type="radio" name="status" value="<?= $v ?>" <?= ($product['status']??'draft')===$v?'checked':'' ?> style="accent-color:<?= $c ?>">
                        <span style="display:flex;align-items:center;gap:5px">
                            <span style="width:8px;height:8px;border-radius:50%;background:<?= $c ?>;display:inline-block"></span>
                            <?= $l ?>
                        </span>
                    </label>
                    <?php endforeach ?>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                    <?= $isEdit ? 'Save Changes' : 'Create Product' ?>
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Category</h3></div>
            <div class="card-body" style="padding:16px">
                <select name="category_id" class="form-select">
                    <option value="">— No category —</option>
                    <?php foreach ($cats as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ((int)($product['category_id']??0)===(int)$c['id'])?'selected':'' ?>>
                        <?= e($c['name']) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
    </div>
</div>
</form>

<script>
var gsSlugEdited = <?= ($isEdit && !empty($product['slug'])) ? 'true' : 'false' ?>;
function slugify(s){return s.toLowerCase().replace(/[^\w\s-]/g,'').replace(/[\s_-]+/g,'-').replace(/^-+|-+$/g,'');}
function autoSlug(t){if(gsSlugEdited)return; document.getElementById('gsSlug').value=slugify(t);}
document.getElementById('gsSlug').addEventListener('input',function(){gsSlugEdited=true;});

function showTab(name,el){
    document.querySelectorAll('.gs-tab').forEach(function(t){t.classList.remove('active');});
    document.querySelectorAll('.gs-tab-panel').forEach(function(p){p.classList.remove('active');});
    el.classList.add('active');
    document.getElementById('gstab-'+name)?.classList.add('active');
}

function toggleStock(checked){
    document.getElementById('stockWrap').style.display = checked ? '' : 'none';
}

function addAttr(){
    var wrap = document.getElementById('attrs-wrap');
    var row  = document.createElement('div');
    row.className = 'gs-attr-row';
    row.innerHTML = '<input type="text" name="attributes[][name]" class="form-input" placeholder="e.g. Color" style="flex:1">'
        +'<input type="text" name="attributes[][value]" class="form-input" placeholder="e.g. Red, Blue" style="flex:2">'
        +'<button type="button" onclick="this.closest(\'.gs-attr-row\').remove()" style="background:none;border:none;cursor:pointer;font-size:18px;color:var(--muted)">✕</button>';
    wrap.appendChild(row);
}
</script>
