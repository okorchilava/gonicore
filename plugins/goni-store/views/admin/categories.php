<?php
$pageTitle = 'Categories — GoniStore';
$activeNav = 'store';
$topbarActions = '';
?>
<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
    <?php foreach ([''.'dashboard'=>'Dashboard','products'=>'Products','categories'=>'Categories','orders'=>'Orders','coupons'=>'Coupons','settings'=>'Settings'] as $path=>$label): ?>
    <a href="<?= e($base) ?>/manage/store/<?= $path ?>" style="padding:7px 14px;background:<?= $label==='Categories'?'var(--accent)':'#fff' ?>;color:<?= $label==='Categories'?'#fff':'var(--muted)' ?>;border:1px solid <?= $label==='Categories'?'var(--accent)':'var(--border)' ?>;border-radius:7px;font-size:13px;font-weight:600;text-decoration:none"><?= $label ?></a>
    <?php endforeach ?>
</div>

<?php if ($success ?? null): ?><div class="alert alert-success" style="margin-bottom:16px"><?= e($success) ?></div><?php endif ?>
<?php if ($error ?? null): ?><div class="alert alert-danger" style="margin-bottom:16px"><?= e($error) ?></div><?php endif ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">
    <!-- Category list -->
    <div class="card" style="padding:0">
        <table style="width:100%;border-collapse:collapse">
            <thead><tr style="border-bottom:1px solid var(--border);font-size:12px;color:var(--muted);text-transform:uppercase">
                <th style="padding:12px 16px;text-align:left">Name</th>
                <th style="padding:12px 16px;text-align:left">Slug</th>
                <th style="padding:12px 16px;text-align:right">Actions</th>
            </tr></thead>
            <tbody>
            <?php if (empty($cats)): ?>
            <tr><td colspan="3" style="padding:32px;text-align:center;color:var(--muted)">No categories yet.</td></tr>
            <?php else: foreach ($cats as $c): ?>
            <tr style="border-bottom:1px solid var(--border)">
                <td style="padding:10px 16px">
                    <div style="display:flex;align-items:center;gap:10px">
                        <?php if ($c['image']): ?><img src="<?= e($c['image']) ?>" style="width:36px;height:36px;border-radius:6px;object-fit:cover"><?php endif ?>
                        <strong><?= e($c['name']) ?></strong>
                    </div>
                </td>
                <td style="padding:10px 16px;color:var(--muted);font-size:13px;font-family:monospace"><?= e($c['slug']) ?></td>
                <td style="padding:10px 16px;text-align:right">
                    <button onclick="editCat(<?= htmlspecialchars(json_encode($c),ENT_QUOTES) ?>)" class="btn btn-ghost" style="font-size:12px;padding:5px 10px">Edit</button>
                    <form method="POST" action="<?= e($base) ?>/manage/store/categories/<?= (int)$c['id'] ?>/delete" style="display:inline" onsubmit="return confirm('Delete?')">
                        <button class="btn btn-danger" style="font-size:12px;padding:5px 10px">✕</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; endif ?>
            </tbody>
        </table>
    </div>

    <!-- Add/Edit form -->
    <div class="card">
        <div class="card-header"><h3 id="catFormTitle">Add Category</h3></div>
        <div class="card-body" style="padding:16px">
            <form method="POST" id="catForm" action="<?= e($base) ?>/manage/store/categories/create">
                <input type="hidden" name="_cat_id" id="catId">
                <div class="form-group" style="margin-bottom:12px">
                    <label class="form-label">Name *</label>
                    <input type="text" name="name" id="catName" class="form-input" required>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" id="catSlug" class="form-input" placeholder="auto-generated">
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="catDesc" class="form-textarea" rows="3"></textarea>
                </div>
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Image URL</label>
                    <input type="text" name="image" id="catImage" class="form-input" placeholder="https://...">
                </div>
                <div style="display:flex;gap:8px">
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">Save</button>
                    <button type="button" onclick="resetCatForm()" class="btn btn-ghost">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCat(c) {
    document.getElementById('catFormTitle').textContent = 'Edit Category';
    document.getElementById('catForm').action = '<?= e($base) ?>/manage/store/categories/'+c.id+'/update';
    document.getElementById('catId').value    = c.id;
    document.getElementById('catName').value  = c.name;
    document.getElementById('catSlug').value  = c.slug;
    document.getElementById('catDesc').value  = c.description;
    document.getElementById('catImage').value = c.image;
    document.getElementById('catName').focus();
}
function resetCatForm(){
    document.getElementById('catFormTitle').textContent = 'Add Category';
    document.getElementById('catForm').action = '<?= e($base) ?>/manage/store/categories/create';
    document.getElementById('catForm').reset();
}
</script>
