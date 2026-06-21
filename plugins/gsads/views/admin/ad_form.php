<?php
$pageTitle     = $isEdit ? 'GsAds — Ad რედაქტირება' : 'GsAds — ახალი Ad';
$activeNav     = 'gsads-ads';

$ad     = $ad ?? [];
$v      = static fn(string $k, string $d = '') => e((string)($ad[$k] ?? $d));
$curType   = (string)($ad['type'] ?? 'image');
$curZoneId = (int)($ad['zone_id'] ?? $defZoneId ?? 0);

// Back link preserves zone filter
$backUrl = e($base) . '/manage/gsads/ads' . ($curZoneId ? '?zone_id='.$curZoneId : '');

$topbarActions = '<a href="' . $backUrl . '" class="btn btn-ghost" style="font-size:13px">← Ads</a>';
?>
<style>
/* Type selector */
.gsa-type-cards{display:flex;gap:10px;margin-bottom:4px}
.gsa-type-card{flex:1;border:2px solid var(--border);border-radius:10px;padding:12px 14px;cursor:pointer;transition:border-color .15s;display:flex;align-items:center;gap:10px}
.gsa-type-card:has(input:checked){border-color:#7c3aed;background:#7c3aed0d}
.gsa-type-card input{accent-color:#7c3aed;flex-shrink:0}
.gsa-type-info{font-size:12.5px;color:var(--muted);margin-top:2px}
/* Toggle */
.gsa-toggle{position:relative;display:inline-flex;align-items:center;cursor:pointer}
.gsa-toggle input{opacity:0;width:0;height:0;position:absolute}
.gsa-slider{display:inline-block;width:48px;height:26px;background:var(--border);border-radius:26px;transition:.25s;position:relative}
.gsa-slider:before{position:absolute;content:'';height:20px;width:20px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.25s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
.gsa-toggle input:checked~.gsa-slider{background:#7c3aed}
.gsa-toggle input:checked~.gsa-slider:before{transform:translateX(22px)}
/* Field groups per type */
.gsa-fields{display:none}
.gsa-fields.visible{display:block}
</style>

<div style="max-width:640px">
<div class="card">
    <div class="card-header">
        <h3><?= $isEdit ? 'Ad-ის რედაქტირება' : 'ახალი Ad' ?></h3>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= e($base) ?>/manage/gsads/ads/save">
            <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)($ad['id'] ?? 0) ?>">
            <?php endif ?>

            <!-- Zone -->
            <div class="form-group">
                <label class="form-label" for="gsaZone">Ad Zone <span style="color:#ef4444">*</span></label>
                <select id="gsaZone" name="zone_id" class="form-input" required>
                    <option value="">— Zone-ის არჩევა —</option>
                    <?php foreach ($zones as $z): ?>
                    <option value="<?= (int)$z['id'] ?>"
                            <?= (int)$z['id'] === $curZoneId ? 'selected' : '' ?>>
                        <?= e((string)$z['name']) ?>
                        <?php if ($z['width'] && $z['height']): ?>
                        (<?= (int)$z['width'] ?>×<?= (int)$z['height'] ?>)
                        <?php endif ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>

            <!-- Name -->
            <div class="form-group">
                <label class="form-label" for="gsaName">Ad სახელი <span style="color:#ef4444">*</span></label>
                <input type="text" id="gsaName" name="name" class="form-input"
                       value="<?= $v('name') ?>" required autofocus
                       placeholder="Summer Banner, Google AdSense, Header Text Ad...">
                <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                    შიდა სახელი — ვიზიტორებს არ ეჩვენება
                </div>
            </div>

            <!-- Type selector -->
            <div class="form-group">
                <label class="form-label">Ad ტიპი</label>
                <div class="gsa-type-cards">
                    <label class="gsa-type-card">
                        <input type="radio" name="type" value="image"
                               <?= $curType === 'image' ? 'checked' : '' ?>
                               onchange="gsaTypeChange('image')">
                        <div>
                            <div style="font-weight:700">🖼 Image</div>
                            <div class="gsa-type-info">სურათი + ბმული</div>
                        </div>
                    </label>
                    <label class="gsa-type-card">
                        <input type="radio" name="type" value="text"
                               <?= $curType === 'text' ? 'checked' : '' ?>
                               onchange="gsaTypeChange('text')">
                        <div>
                            <div style="font-weight:700">📝 Text</div>
                            <div class="gsa-type-info">სათაური + ტექსტი + ბმული</div>
                        </div>
                    </label>
                    <label class="gsa-type-card">
                        <input type="radio" name="type" value="html"
                               <?= $curType === 'html' ? 'checked' : '' ?>
                               onchange="gsaTypeChange('html')">
                        <div>
                            <div style="font-weight:700">📄 HTML</div>
                            <div class="gsa-type-info">AdSense, Banner კოდი</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- ── Image fields ── -->
            <div id="gsa-fields-image" class="gsa-fields <?= $curType === 'image' ? 'visible' : '' ?>">
                <div class="form-group">
                    <label class="form-label" for="gsaImgUrl">სურათის URL <span style="color:#ef4444">*</span></label>
                    <input type="text" id="gsaImgUrl" name="image_url" class="form-input"
                           value="<?= $v('image_url') ?>"
                           placeholder="https://example.com/banner.jpg">
                </div>
                <div class="form-group">
                    <label class="form-label" for="gsaImgLink">ბმული (link_url) <span style="color:#ef4444">*</span></label>
                    <input type="text" id="gsaImgLink" name="link_url" class="form-input"
                           value="<?= $v('link_url') ?>"
                           placeholder="https://advertiser.com/">
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13.5px">
                        <input type="checkbox" name="opens_blank" value="1"
                               <?= ($ad['opens_blank'] ?? '1') !== '0' ? 'checked' : '' ?>
                               style="accent-color:#7c3aed">
                        ახალ ჩანართში გახსნა (<code style="font-size:11px">target="_blank"</code>)
                    </label>
                </div>
            </div>

            <!-- ── Text fields ── -->
            <div id="gsa-fields-text" class="gsa-fields <?= $curType === 'text' ? 'visible' : '' ?>">
                <div class="form-group">
                    <label class="form-label" for="gsaTitle">სათაური <span style="color:#ef4444">*</span></label>
                    <input type="text" id="gsaTitle" name="ad_title" class="form-input"
                           value="<?= $v('ad_title') ?>"
                           placeholder="სარეკლამო სათაური...">
                </div>
                <div class="form-group">
                    <label class="form-label" for="gsaBody">ტექსტი (სურვილისამებრ)</label>
                    <textarea id="gsaBody" name="ad_body" class="form-input" rows="2"
                              placeholder="მოკლე სარეკლამო ტექსტი..."><?= $v('ad_body') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="gsaTxtLink">ბმული (link_url) <span style="color:#ef4444">*</span></label>
                    <input type="text" id="gsaTxtLink" name="link_url" class="form-input"
                           value="<?= $v('link_url') ?>"
                           placeholder="https://advertiser.com/">
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13.5px">
                        <input type="checkbox" name="opens_blank" value="1"
                               <?= ($ad['opens_blank'] ?? '1') !== '0' ? 'checked' : '' ?>
                               style="accent-color:#7c3aed">
                        ახალ ჩანართში გახსნა
                    </label>
                </div>
            </div>

            <!-- ── HTML fields ── -->
            <div id="gsa-fields-html" class="gsa-fields <?= $curType === 'html' ? 'visible' : '' ?>">
                <div class="form-group">
                    <label class="form-label" for="gsaHtmlCode">HTML / Script კოდი <span style="color:#ef4444">*</span></label>
                    <textarea id="gsaHtmlCode" name="html_code" class="form-input"
                              rows="8" spellcheck="false"
                              style="font-family:monospace;font-size:12.5px"
                              placeholder="<!-- Google AdSense, banner script, iframe... -->"><?= $v('html_code') ?></textarea>
                    <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                        ⚠ HTML ტიპი პირდაპირ ჩასვამს კოდს გვერდში — click tracking ამ ტიპს არ ეხება.
                    </div>
                </div>
            </div>

            <hr style="border:none;border-top:1px solid var(--border);margin:8px 0 20px">

            <!-- Schedule + Weight -->
            <div style="display:grid;grid-template-columns:1fr 1fr 80px;gap:12px">
                <div class="form-group">
                    <label class="form-label">დაწყება (სურვილისამებრ)</label>
                    <input type="date" name="starts_at" class="form-input"
                           value="<?= $v('starts_at') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">დასრულება (სურვილისამებრ)</label>
                    <input type="date" name="ends_at" class="form-input"
                           value="<?= $v('ends_at') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Weight</label>
                    <input type="number" name="weight" class="form-input"
                           value="<?= $v('weight', '10') ?>" min="1" max="255">
                </div>
            </div>
            <div style="font-size:11.5px;color:var(--muted);margin-top:-10px;margin-bottom:16px">
                Weight: 1–255. მაღალი = ამ Ad-ის ჩვენების მეტი შანსი rotation-ის დროს.
            </div>

            <!-- Active -->
            <div style="display:flex;align-items:center;justify-content:space-between;background:var(--bg-2,rgba(0,0,0,.03));border:1px solid var(--border);border-radius:10px;padding:14px 16px;margin-bottom:20px">
                <div>
                    <div style="font-weight:700;font-size:14px">Ad აქტიურია</div>
                    <div style="font-size:12px;color:var(--muted);margin-top:2px">გათიშვა Ad-ს დამალავს rotation-იდან</div>
                </div>
                <label class="gsa-toggle">
                    <input type="hidden"   name="active" id="gsaActHidden" value="<?= $v('active', '1') ?>">
                    <input type="checkbox" id="gsaActCb"
                           <?= ($ad['active'] ?? '1') !== '0' ? 'checked' : '' ?>
                           onchange="document.getElementById('gsaActHidden').value=this.checked?'1':'0'">
                    <span class="gsa-slider"></span>
                </label>
            </div>

            <div style="display:flex;gap:12px">
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? '💾 განახლება' : '+ Ad-ის შექმნა' ?>
                </button>
                <a href="<?= $backUrl ?>" class="btn btn-ghost">გაუქმება</a>
                <?php if ($isEdit): ?>
                <form method="POST" action="<?= e($base) ?>/manage/gsads/ads/delete"
                      style="margin-left:auto"
                      onsubmit="return confirm('Ad წაიშლება. გაგრძელება?')">
                    <input type="hidden" name="id" value="<?= (int)($ad['id'] ?? 0) ?>">
                    <button type="submit" class="btn btn-ghost" style="color:#ef4444">🗑 წაშლა</button>
                </form>
                <?php endif ?>
            </div>
        </form>
    </div>
</div>
</div>

<script>
function gsaTypeChange(type) {
    document.querySelectorAll('.gsa-fields').forEach(function(el) {
        el.classList.remove('visible');
    });
    var el = document.getElementById('gsa-fields-' + type);
    if (el) el.classList.add('visible');
}
</script>
