<?php
$pageTitle     = $isEdit ? 'GsAds — Zone რედაქტირება' : 'GsAds — ახალი Zone';
$activeNav     = 'gsads-zones';
$topbarActions = '<a href="' . e($base) . '/manage/gsads/zones" class="btn btn-ghost" style="font-size:13px">← Zones</a>';

$z = $zone ?? [];
$v = static fn(string $k, string $d = '') => e((string)($z[$k] ?? $d));
?>
<style>
.gsa-size-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.gsa-toggle{position:relative;display:inline-flex;align-items:center;cursor:pointer}
.gsa-toggle input{opacity:0;width:0;height:0;position:absolute}
.gsa-slider{display:inline-block;width:48px;height:26px;background:var(--border);border-radius:26px;transition:.25s;position:relative}
.gsa-slider:before{position:absolute;content:'';height:20px;width:20px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.25s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
.gsa-toggle input:checked~.gsa-slider{background:#7c3aed}
.gsa-toggle input:checked~.gsa-slider:before{transform:translateX(22px)}
</style>

<div style="max-width:580px">
<div class="card">
    <div class="card-header">
        <h3><?= $isEdit ? 'Zone-ის რედაქტირება' : 'ახალი Ad Zone' ?></h3>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= e($base) ?>/manage/gsads/zones/save">
            <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)($z['id'] ?? 0) ?>">
            <?php endif ?>

            <!-- Name -->
            <div class="form-group">
                <label class="form-label" for="gsaName">Zone სახელი <span style="color:#ef4444">*</span></label>
                <input type="text" id="gsaName" name="name" class="form-input"
                       value="<?= $v('name') ?>" required autofocus
                       placeholder="Header Banner, Sidebar Top, Footer Strip..."
                       oninput="gsaAutoSlug()">
            </div>

            <!-- Slug -->
            <div class="form-group">
                <label class="form-label" for="gsaSlug">Slug <span style="color:#ef4444">*</span></label>
                <div style="position:relative">
                    <input type="text" id="gsaSlug" name="slug" class="form-input"
                           value="<?= $v('slug') ?>" required
                           placeholder="header-banner"
                           pattern="[a-z0-9\-]+"
                           oninput="gsaSlugManual=true"
                           style="padding-right:120px">
                    <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:11.5px;color:var(--muted);pointer-events:none">
                        <?= e("<?= gsads('...') ?>") ?>
                    </span>
                </div>
                <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                    მხოლოდ პატარა ლათინური ასოები, ციფრები, დეფისი. ავტომატურად ივსება.
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="form-label" for="gsaDesc">აღწერა (სურვილისამებრ)</label>
                <input type="text" id="gsaDesc" name="description" class="form-input"
                       value="<?= $v('description') ?>"
                       placeholder="მოკლე შენიშვნა ამ zone-ის შესახებ">
            </div>

            <!-- Size hint -->
            <div class="form-group">
                <label class="form-label">ზომის მინიშნება (px, სურვილისამებრ)</label>
                <div class="gsa-size-grid">
                    <div>
                        <input type="number" name="width" class="form-input"
                               value="<?= $v('width') ?>" min="0" max="9999"
                               placeholder="სიგანე (e.g. 728)">
                    </div>
                    <div>
                        <input type="number" name="height" class="form-input"
                               value="<?= $v('height') ?>" min="0" max="9999"
                               placeholder="სიმაღლე (e.g. 90)">
                    </div>
                </div>
                <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                    მხოლოდ ინფორმაციული (Ads-ის ფორმაზე ნაჩვენები). Zone HTML-ს ზომა არ შეაქვს.
                </div>
            </div>

            <!-- Active -->
            <div style="display:flex;align-items:center;justify-content:space-between;background:var(--bg-2,rgba(0,0,0,.03));border:1px solid var(--border);border-radius:10px;padding:14px 16px;margin-bottom:20px">
                <div>
                    <div style="font-weight:700;font-size:14px">Zone აქტიურია</div>
                    <div style="font-size:12px;color:var(--muted);margin-top:2px">გათიშვა ამ zone-ს ყველა Ad-სგან დამალავს</div>
                </div>
                <label class="gsa-toggle">
                    <input type="hidden"   name="active" id="gsaActHidden" value="<?= $v('active', '1') ?>">
                    <input type="checkbox" id="gsaActCb"
                           <?= ($z['active'] ?? '1') !== '0' ? 'checked' : '' ?>
                           onchange="document.getElementById('gsaActHidden').value=this.checked?'1':'0'">
                    <span class="gsa-slider"></span>
                </label>
            </div>

            <div style="display:flex;gap:12px">
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? '💾 განახლება' : '+ Zone-ის შექმნა' ?>
                </button>
                <a href="<?= e($base) ?>/manage/gsads/zones" class="btn btn-ghost">გაუქმება</a>
                <?php if ($isEdit): ?>
                <form method="POST" action="<?= e($base) ?>/manage/gsads/zones/delete"
                      style="margin-left:auto"
                      onsubmit="return confirm('Zone წაიშლება ყველა Ad-თან ერთად. გაგრძელება?')">
                    <input type="hidden" name="id" value="<?= (int)($z['id'] ?? 0) ?>">
                    <button type="submit" class="btn btn-ghost" style="color:#ef4444">🗑 წაშლა</button>
                </form>
                <?php endif ?>
            </div>
        </form>
    </div>
</div>
</div>

<script>
var gsaSlugManual = <?= $isEdit ? 'true' : 'false' ?>;
function gsaAutoSlug() {
    if (gsaSlugManual) return;
    var name = document.getElementById('gsaName').value;
    document.getElementById('gsaSlug').value = name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
}
document.getElementById('gsaSlug').addEventListener('input', function() {
    gsaSlugManual = true;
    // Sanitize on blur
    this.value = this.value.toLowerCase().replace(/[^a-z0-9\-]+/g, '-');
});
</script>
