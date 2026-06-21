<?php
$pageTitle = $isEdit ? 'GoniSEO — Meta რედაქტირება' : 'GoniSEO — ახალი Meta';
$activeNav = 'goniseo-meta';
$topbarActions = '<a href="' . e($base) . '/manage/goniseo/meta" class="btn btn-ghost" style="font-size:13px">← Meta სია</a>';

$m = $row ?? [];
$initPath = $isEdit ? (string)($m['url_path'] ?? '') : $prefillPath;
?>
<style>
.gseo-tab-bar{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:20px}
.gseo-tab{padding:10px 20px;font-size:13.5px;font-weight:600;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;color:var(--muted);transition:color .15s,border-color .15s;background:none;border-top:none;border-left:none;border-right:none;font-family:inherit}
.gseo-tab.active{color:#7c3aed;border-bottom-color:#7c3aed}
.gseo-tab-panel{display:none}
.gseo-tab-panel.active{display:block}
.gseo-char-count{font-size:11.5px;color:var(--muted);margin-top:4px}
.gseo-char-count.warn{color:#f59e0b;font-weight:600}
.gseo-char-count.over{color:#ef4444;font-weight:700}
</style>

<div style="max-width:740px">
<div class="card">
    <div class="card-header">
        <h3><?= $isEdit ? 'Meta რედაქტირება' : 'ახალი Meta შეყვანა' ?></h3>
    </div>
    <div class="card-body">
    <form method="POST" action="<?= e($base) ?>/manage/goniseo/meta/save">
        <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int)($m['id'] ?? 0) ?>">
        <?php endif ?>

        <!-- URL Path (always visible) -->
        <div class="form-group">
            <label class="form-label" for="gsPath">
                URL Path <span style="color:#ef4444">*</span>
            </label>
            <input type="text" id="gsPath" name="url_path" class="form-input" required
                   value="<?= e($initPath) ?>"
                   placeholder="/about"
                   pattern="\/.*">
            <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                app root-ის ფარდობითი path. მაგ: <code>/</code>, <code>/about</code>, <code>/blog/my-post</code>
            </div>
        </div>

        <!-- Tab nav -->
        <div class="gseo-tab-bar" id="gsTabBar">
            <button type="button" class="gseo-tab active" onclick="gsTab('basic', this)">📝 ძირითადი</button>
            <button type="button" class="gseo-tab" onclick="gsTab('og', this)">📱 Open Graph</button>
            <button type="button" class="gseo-tab" onclick="gsTab('advanced', this)">⚙ Advanced</button>
        </div>

        <!-- Tab: Basic -->
        <div class="gseo-tab-panel active" id="gsPanel-basic">
            <div class="form-group">
                <label class="form-label" for="gsTitle">SEO Title</label>
                <input type="text" id="gsTitle" name="title" class="form-input"
                       value="<?= e((string)($m['title'] ?? '')) ?>"
                       maxlength="200"
                       placeholder="გვერდის SEO სათაური"
                       oninput="gsCharCount(this, 'gsTitleCount', 60)">
                <div id="gsTitleCount" class="gseo-char-count">0 / 60 სიმბოლო (რეკ.)</div>
                <div style="font-size:11.5px;color:var(--muted);margin-top:2px">
                    ცარიელი = title_format-ი გამოიყენება ნაწარმოები სახელით
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="gsDesc">Description</label>
                <textarea id="gsDesc" name="description" class="form-input" rows="3"
                          maxlength="500"
                          placeholder="გვერდის მოკლე აღწერა (max 160 სიმბოლო)"
                          oninput="gsCharCount(this, 'gsDescCount', 160)"><?= e((string)($m['description'] ?? '')) ?></textarea>
                <div id="gsDescCount" class="gseo-char-count">0 / 160 სიმბოლო (რეკ.)</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="gsKeys">Keywords</label>
                <input type="text" id="gsKeys" name="keywords" class="form-input"
                       value="<?= e((string)($m['keywords'] ?? '')) ?>"
                       placeholder="keyword1, keyword2, keyword3"
                       maxlength="300">
                <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                    მძიმით გამოყოფილი. Google keywords-ს ნაკლებ ყურადღებას აქცევს — Optional.
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="gsRobots">Robots</label>
                <select id="gsRobots" name="robots" class="form-input" style="max-width:220px">
                    <option value="">default (settings-ის მიხედვით)</option>
                    <?php foreach ($robotsOptions as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= ($m['robots'] ?? '') === $opt ? 'selected' : '' ?>>
                        <?= e($opt) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>

        <!-- Tab: Open Graph -->
        <div class="gseo-tab-panel" id="gsPanel-og">
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 16px;margin-bottom:18px;font-size:13px;color:#1e40af">
                ℹ ცარიელი = Basic ველები გამოიყენება. OG ველები სოც. ქსელების share-ისთვის.
            </div>

            <div class="form-group">
                <label class="form-label" for="gsOgTitle">OG Title</label>
                <input type="text" id="gsOgTitle" name="og_title" class="form-input"
                       value="<?= e((string)($m['og_title'] ?? '')) ?>"
                       maxlength="200"
                       placeholder="Facebook/Twitter-ის სათაური (ცარიელი = SEO Title)">
            </div>

            <div class="form-group">
                <label class="form-label" for="gsOgDesc">OG Description</label>
                <textarea id="gsOgDesc" name="og_description" class="form-input" rows="3"
                          maxlength="500"
                          placeholder="Facebook/Twitter-ის აღწერა (ცარიელი = Description)"><?= e((string)($m['og_description'] ?? '')) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="gsOgImg">OG Image URL</label>
                <input type="url" id="gsOgImg" name="og_image" class="form-input"
                       value="<?= e((string)($m['og_image'] ?? '')) ?>"
                       placeholder="https://example.com/images/share.jpg">
                <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                    რეკ. ზომა: 1200×630px. ცარიელი = Default OG Image (Settings-იდან).
                </div>
            </div>

            <?php if (!empty($m['og_image'])): ?>
            <div style="margin-top:8px">
                <div style="font-size:12px;color:var(--muted);margin-bottom:6px">Preview:</div>
                <img src="<?= e((string)$m['og_image']) ?>"
                     style="max-width:280px;border-radius:8px;border:1px solid var(--border)"
                     onerror="this.style.display='none'" alt="OG preview">
            </div>
            <?php endif ?>
        </div>

        <!-- Tab: Advanced -->
        <div class="gseo-tab-panel" id="gsPanel-advanced">
            <div class="form-group">
                <label class="form-label" for="gsCanonical">Canonical URL</label>
                <input type="url" id="gsCanonical" name="canonical" class="form-input"
                       value="<?= e((string)($m['canonical'] ?? '')) ?>"
                       placeholder="https://example.com/about">
                <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                    ცარიელი = მიმდინარე URL ავტომატურად. გამოიყენე duplicate content-ის პრობლემისთვის.
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="gsJsonLd">
                    JSON-LD Schema
                    <a href="https://schema.org" target="_blank" style="font-size:11.5px;font-weight:400;margin-left:6px">schema.org →</a>
                </label>
                <textarea id="gsJsonLd" name="json_ld" class="form-input"
                          rows="10"
                          style="font-family:monospace;font-size:12.5px"
                          placeholder='{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "გვერდის სახელი"
}'><?= e((string)($m['json_ld'] ?? '')) ?></textarea>
                <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                    სტრუქტურირებული მონაცემი. იქნება inserted as-is.
                    <a href="https://validator.schema.org" target="_blank">ვალიდაცია →</a>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div style="display:flex;gap:12px;margin-top:8px;padding-top:4px;border-top:1px solid var(--border)">
            <button type="submit" class="btn btn-primary">
                <?= $isEdit ? '💾 განახლება' : '+ Meta შენახვა' ?>
            </button>
            <a href="<?= e($base) ?>/manage/goniseo/meta" class="btn btn-ghost">გაუქმება</a>
            <?php if ($isEdit): ?>
            <form method="POST" action="<?= e($base) ?>/manage/goniseo/meta/delete"
                  style="margin-left:auto"
                  onsubmit="return confirm('Meta წაიშლება?')">
                <input type="hidden" name="id" value="<?= (int)($m['id'] ?? 0) ?>">
                <button type="submit" class="btn btn-ghost" style="color:#ef4444">🗑 წაშლა</button>
            </form>
            <?php endif ?>
        </div>
    </form>
    </div>
</div>
</div>

<script>
// ── Tabs ──
function gsTab(name, btn) {
    document.querySelectorAll('.gseo-tab').forEach(function(b) { b.classList.remove('active'); });
    document.querySelectorAll('.gseo-tab-panel').forEach(function(p) { p.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById('gsPanel-' + name).classList.add('active');
}

// ── Character counters ──
function gsCharCount(el, counterId, recommended) {
    var len = el.value.length;
    var out = document.getElementById(counterId);
    if (!out) return;
    out.textContent = len + ' / ' + recommended + ' სიმბოლო (რეკ.)';
    out.className = 'gseo-char-count' + (len > recommended * 1.3 ? ' over' : len > recommended ? ' warn' : '');
}

// Init counters
gsCharCount(document.getElementById('gsTitle'), 'gsTitleCount', 60);
gsCharCount(document.getElementById('gsDesc'),  'gsDescCount',  160);
</script>
