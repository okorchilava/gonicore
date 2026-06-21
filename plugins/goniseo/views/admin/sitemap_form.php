<?php
$pageTitle     = $isEdit ? 'GoniSEO — URL რედაქტირება' : 'GoniSEO — URL-ის დამატება';
$activeNav     = 'goniseo-sitemap';
$topbarActions = '<a href="' . e($base) . '/manage/goniseo/sitemap" class="btn btn-ghost" style="font-size:13px">← Sitemap</a>';

$r = $row ?? [];
?>
<div style="max-width:560px">
<div class="card">
    <div class="card-header"><h3><?= $isEdit ? 'URL-ის რედაქტირება' : 'Sitemap-ში URL-ის დამატება' ?></h3></div>
    <div class="card-body">
    <form method="POST" action="<?= e($base) ?>/manage/goniseo/sitemap/save">
        <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>">
        <?php endif ?>

        <div class="form-group">
            <label class="form-label" for="gsUrl">URL <span style="color:#ef4444">*</span></label>
            <input type="text" id="gsUrl" name="url" class="form-input" required autofocus
                   value="<?= e((string)($r['url'] ?? '')) ?>"
                   placeholder="https://example.com/page ან /page">
            <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                სრული URL ან root-ის ფარდობითი path (/about)
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="form-group">
                <label class="form-label" for="gsPri">Priority</label>
                <select id="gsPri" name="priority" class="form-input">
                    <?php foreach (['1.0','0.9','0.8','0.7','0.6','0.5','0.4','0.3','0.2','0.1'] as $p): ?>
                    <option value="<?= $p ?>" <?= (string)($r['priority'] ?? '0.5') == $p ? 'selected' : '' ?>>
                        <?= $p ?>
                    </option>
                    <?php endforeach ?>
                </select>
                <div style="font-size:11px;color:var(--muted);margin-top:3px">1.0 = მაღალი, 0.5 = ნორმალური</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="gsCf">Change Frequency</label>
                <select id="gsCf" name="changefreq" class="form-input">
                    <?php foreach (['always','hourly','daily','weekly','monthly','yearly','never'] as $cf): ?>
                    <option value="<?= $cf ?>" <?= ($r['changefreq'] ?? 'weekly') === $cf ? 'selected' : '' ?>>
                        <?= $cf ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="gsLm">Last Modified (lastmod)</label>
            <input type="date" id="gsLm" name="lastmod" class="form-input"
                   value="<?= e((string)($r['lastmod'] ?? '')) ?>"
                   style="max-width:180px">
            <div style="font-size:11.5px;color:var(--muted);margin-top:3px">Optional. ცარიელი = lastmod არ ჩაიწერება.</div>
        </div>

        <div style="display:flex;gap:12px;margin-top:4px">
            <button type="submit" class="btn btn-primary">
                <?= $isEdit ? '💾 განახლება' : '+ დამატება' ?>
            </button>
            <a href="<?= e($base) ?>/manage/goniseo/sitemap" class="btn btn-ghost">გაუქმება</a>
            <?php if ($isEdit): ?>
            <form method="POST" action="<?= e($base) ?>/manage/goniseo/sitemap/delete"
                  style="margin-left:auto"
                  onsubmit="return confirm('URL წაიშლება?')">
                <input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>">
                <button type="submit" class="btn btn-ghost" style="color:#ef4444">🗑 წაშლა</button>
            </form>
            <?php endif ?>
        </div>
    </form>
    </div>
</div>
</div>
