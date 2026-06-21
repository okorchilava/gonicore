<?php
$eventsSlug = $tickets->setting('events_page_slug', 'events');

// ── Fallback category data (used when DB is empty or slug not found) ──────────
$catFallbacks = [
    'concert'    => ['c1'=>'#3b0764','c2'=>'#7c3aed','accent'=>'#a78bfa','icon'=>'🎵','label'=>'კონცერტი'],
    'festival'   => ['c1'=>'#7c2d12','c2'=>'#ea580c','accent'=>'#fb923c','icon'=>'🎪','label'=>'ფესტივალი'],
    'theater'    => ['c1'=>'#1e1b4b','c2'=>'#4338ca','accent'=>'#818cf8','icon'=>'🎭','label'=>'თეატრი'],
    'cinema'     => ['c1'=>'#1c0a0a','c2'=>'#991b1b','accent'=>'#f87171','icon'=>'🎬','label'=>'კინო'],
    'sports'     => ['c1'=>'#052e16','c2'=>'#15803d','accent'=>'#4ade80','icon'=>'⚽','label'=>'სპორტი'],
    'art'        => ['c1'=>'#042f2e','c2'=>'#0f766e','accent'=>'#2dd4bf','icon'=>'🎨','label'=>'ხელოვნება'],
    'food'       => ['c1'=>'#451a03','c2'=>'#b45309','accent'=>'#fbbf24','icon'=>'🍽','label'=>'კულინარია'],
    'conference' => ['c1'=>'#0c1445','c2'=>'#1d4ed8','accent'=>'#60a5fa','icon'=>'💼','label'=>'კონფერენცია'],
    'education'  => ['c1'=>'#0c2340','c2'=>'#0369a1','accent'=>'#38bdf8','icon'=>'🎓','label'=>'განათლება'],
    'train'      => ['c1'=>'#0f172a','c2'=>'#334155','accent'=>'#94a3b8','icon'=>'🚂','label'=>'მატარებელი'],
    'bus'        => ['c1'=>'#1c1400','c2'=>'#854d0e','accent'=>'#fde047','icon'=>'🚌','label'=>'ავტობუსი'],
    'nature'     => ['c1'=>'#052e16','c2'=>'#166534','accent'=>'#86efac','icon'=>'🌿','label'=>'ბუნება'],
    'other'      => ['c1'=>'#0a0812','c2'=>'#4c1d95','accent'=>'#a78bfa','icon'=>'🎟','label'=>'სხვა'],
];

/** Get display data for a category slug, merging DB row with fallbacks */
$getCat = function(string $slug) use ($catMap, $catFallbacks): array {
    $db = $catMap[$slug] ?? null;
    $fb = $catFallbacks[$slug] ?? $catFallbacks['other'];
    if ($db) {
        return [
            'icon'   => $db['icon']     ?: $fb['icon'],
            'label'  => $db['label']    ?: $fb['label'],
            'accent' => $db['accent']   ?: $fb['accent'],
            'c1'     => $db['grad_from']?: $fb['c1'],
            'c2'     => $db['grad_to']  ?: $fb['c2'],
        ];
    }
    return $fb;
};
?>
<style>
/* ── Page ─────────────────────────────────────────────────────────────── */
.gt-page{background:#0a0812;position:relative}
.gt-page a{text-decoration:none}

.gt-glow{position:absolute;border-radius:50%;filter:blur(160px);pointer-events:none;z-index:0}
.gt-glow-a{width:800px;height:800px;background:rgba(124,58,237,.28);top:-320px;left:50%;transform:translateX(-60%)}
.gt-glow-b{width:600px;height:600px;background:rgba(236,72,153,.16);top:-240px;left:50%;transform:translateX(-5%)}

/* ── Heading ──────────────────────────────────────────────────────────── */
.gt-heading{position:relative;z-index:1;text-align:center;padding:80px 24px 64px}
.gt-hero-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.25);color:#c4b5fd;border-radius:20px;padding:5px 16px;font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;margin-bottom:24px}
.gt-heading h1{font-size:clamp(36px,5.5vw,72px);font-weight:900;color:#fff;line-height:1.08;letter-spacing:-2.5px;margin-bottom:20px}
.gt-heading h1 em{font-style:normal;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.gt-heading-sub{color:#94a3b8;font-size:15px}
.gt-hero-count{display:inline-block;background:rgba(99,102,241,.12);color:#818cf8;border-radius:10px;padding:2px 10px;font-size:12.5px;font-weight:700;margin-left:6px}

.gt-cat-row{display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin-top:28px}
.gt-cat-btn{display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#94a3b8;border-radius:30px;padding:11px 22px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap}
.gt-cat-btn:hover{background:rgba(255,255,255,.09);color:#e2e8f0;border-color:rgba(255,255,255,.18)}
.gt-cat-btn.active{background:color-mix(in srgb,var(--cc,#a78bfa) 15%,transparent);border-color:color-mix(in srgb,var(--cc,#a78bfa) 50%,transparent);color:var(--cc,#a78bfa)}
.gt-cat-btn[data-cat="all"].active{background:rgba(167,139,250,.12);border-color:rgba(167,139,250,.35);color:#a78bfa}

/* ── List ─────────────────────────────────────────────────────────────── */
.gt-list{position:relative;z-index:1;max-width:1280px;margin:0 auto;padding:0 24px 80px}
.gt-section-label{display:flex;align-items:center;gap:14px;padding:0 0 20px}
.gt-section-label span{font-size:11px;font-weight:800;color:#a78bfa;text-transform:uppercase;letter-spacing:1.5px;white-space:nowrap}
.gt-section-label::before,.gt-section-label::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.06)}

/* ── Row ──────────────────────────────────────────────────────────────── */
.gt-row{display:grid;grid-template-columns:1fr 1fr;min-height:360px;border-radius:24px 0 24px 0;overflow:hidden;margin-bottom:12px}
.gt-row:nth-child(even) .gt-row-img{order:2}
.gt-row:nth-child(even) .gt-row-body{order:1}

.gt-row-img{position:relative;overflow:hidden}
.gt-row-img img{width:100%;height:100%;object-fit:cover;display:block}
.gt-row-num{position:absolute;top:20px;left:20px;width:36px;height:36px;background:rgba(10,8,18,.6);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#e2e8f0}
.gt-row-soldout-banner{position:absolute;inset:0;background:rgba(10,8,18,.6);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:800;color:#fca5a5;letter-spacing:2px;text-transform:uppercase}

.gt-row-body{background:var(--cat-glow,rgba(255,255,255,.03));border:1px solid rgba(255,255,255,.06);padding:44px 48px;display:flex;flex-direction:column;justify-content:center}
.gt-row-top{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:18px}
.gt-row-tag{font-size:10.5px;font-weight:800;color:var(--cat-accent,#a78bfa);text-transform:uppercase;letter-spacing:1.2px;white-space:nowrap}
.gt-row-organizer{font-size:11px;font-weight:600;color:#3f3f5a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gt-row-organizer a{color:#3f3f5a}
.gt-row-organizer a:hover{color:#a78bfa}
.gt-row-meta{display:flex;align-items:center;gap:6px;margin-top:10px;font-size:12px;color:#3f3f5a;font-weight:500}
.gt-row-meta-dot{color:#2a2a40}
.gt-row-title{font-size:clamp(22px,2.2vw,34px);font-weight:900;color:#f1f5f9;line-height:1.18;letter-spacing:-.5px;margin-bottom:18px;text-decoration:none;display:block}
.gt-row-title:hover{color:#c4b5fd}
.gt-row-cta{display:flex;align-items:flex-end;gap:20px;margin-top:auto}
.gt-row-price-from{font-size:10px;text-transform:uppercase;letter-spacing:.6px;color:#a78bfa;font-weight:700;margin-bottom:2px}
.gt-row-price{font-size:32px;font-weight:900;color:var(--cat-accent,#a78bfa);line-height:1;letter-spacing:-1px}
.gt-row-free{font-size:18px;font-weight:800;color:#34d399;line-height:1}
.gt-row-soldout-lbl{font-size:14px;font-weight:700;color:#fca5a5;line-height:1}
.gt-row-btn{display:inline-flex;align-items:center;gap:8px;background:var(--cat-accent,#7c3aed);color:#0a0812 !important;padding:13px 26px;border-radius:30px;font-size:14px;font-weight:700;text-decoration:none !important;white-space:nowrap;margin-left:auto}
.gt-row-btn:hover{opacity:.88}
.gt-row-btn-dis{display:inline-flex;align-items:center;background:rgba(255,255,255,.06);color:#94a3b8;padding:13px 26px;border-radius:30px;font-size:14px;font-weight:700;cursor:not-allowed;white-space:nowrap;margin-left:auto}

/* ── Featured slider ──────────────────────────────────────────────────── */
.gt-featured{position:relative;z-index:1;padding:0 0 48px}
.gt-featured-track-wrap{overflow:hidden;border-radius:0}
.gt-featured-track{display:flex;gap:0;transition:transform .45s cubic-bezier(.4,0,.2,1)}
.gt-feat-slide{flex:0 0 50%;position:relative;border-radius:0;overflow:hidden;min-height:400px;cursor:pointer}
.gt-feat-slide img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .4s}
.gt-feat-slide:hover img{transform:scale(1.04)}
.gt-feat-slide-bg{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:80px}
.gt-feat-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(5,3,14,.92) 0%,rgba(5,3,14,.3) 55%,transparent 100%);pointer-events:none}
.gt-feat-info{position:absolute;bottom:0;left:0;right:0;padding:28px 28px 24px}
.gt-feat-tag{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:1.2px;color:var(--cat-accent,#a78bfa);margin-bottom:8px}
.gt-feat-title{font-size:clamp(17px,1.8vw,24px);font-weight:900;color:#fff;line-height:1.2;letter-spacing:-.3px;margin-bottom:10px;text-decoration:none;display:block}
.gt-feat-title:hover{color:#c4b5fd}
.gt-feat-meta{font-size:11.5px;color:rgba(255,255,255,.55);display:flex;align-items:center;gap:8px}
.gt-feat-badge{position:absolute;top:18px;left:18px;display:inline-flex;align-items:center;gap:5px;background:rgba(167,139,250,.18);border:1px solid rgba(167,139,250,.35);backdrop-filter:blur(8px);color:#c4b5fd;border-radius:20px;padding:4px 12px;font-size:10px;font-weight:800;letter-spacing:.6px;text-transform:uppercase}
.gt-feat-btn-wrap{display:flex;justify-content:center;gap:10px;margin-top:18px}
.gt-feat-dot{width:8px;height:8px;border-radius:50%;border:2px solid rgba(255,255,255,.25);background:transparent;cursor:pointer;transition:background .2s,border-color .2s;padding:0}
.gt-feat-dot.active{background:#a78bfa;border-color:#a78bfa}
.gt-feat-arr{position:absolute;top:50%;transform:translateY(-50%);z-index:10;width:42px;height:42px;border-radius:50%;background:rgba(10,8,18,.65);border:1px solid rgba(255,255,255,.12);backdrop-filter:blur(10px);color:#fff;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s,opacity .15s;opacity:.7}
.gt-feat-arr:hover{background:rgba(124,58,237,.5);opacity:1}
.gt-feat-arr--prev{left:20px}
.gt-feat-arr--next{right:20px}

/* light mode */
html.gt-light .gt-feat-overlay{background:linear-gradient(to top,rgba(235,233,248,.97) 0%,rgba(235,233,248,.4) 55%,transparent 100%)}
html.gt-light .gt-feat-title{color:#1e1b4b}
html.gt-light .gt-feat-title:hover{color:#7c3aed}
html.gt-light .gt-feat-meta{color:rgba(30,27,75,.5)}

.gt-empty{position:relative;z-index:1;text-align:center;padding:80px 24px;color:#94a3b8;max-width:1280px;margin:0 auto}

@media(max-width:720px){
    .gt-row{grid-template-columns:1fr;min-height:unset}
    .gt-row:nth-child(even) .gt-row-img,.gt-row:nth-child(even) .gt-row-body{order:unset}
    .gt-row-img img,.gt-row-img a{min-height:220px}
    .gt-row-body{padding:28px 24px 32px}
    .gt-row-title{font-size:22px}
    .gt-heading{padding:56px 20px 44px}
}
@media(max-width:640px){
    .gt-feat-slide{flex:0 0 100%}
    .gt-feat-arr--prev{left:12px}
    .gt-feat-arr--next{right:12px}
    .gt-feat-slide{min-height:280px}
}
</style>

<?php $activeSubnav = 'events'; include __DIR__ . '/_subnav.php'; ?>
<div class="gt-page">

    <div class="gt-glow gt-glow-a"></div>
    <div class="gt-glow gt-glow-b"></div>

    <div class="gt-heading">
        <h1>Find Your <em>Next<br>Experience</em></h1>

        <?php if (!empty($events)): ?>
        <?php
            $activeSlugs = [];
            foreach ($events as $ev) {
                $k = $ev['category'] ?? 'other';
                if (!isset($activeSlugs[$k])) $activeSlugs[$k] = $getCat($k);
            }
        ?>
        <div class="gt-cat-row">
            <a class="gt-cat-btn" data-cat="all" href="#all">🎟 ყველა</a>
            <?php foreach ($activeSlugs as $slug => $c): ?>
            <a class="gt-cat-btn" data-cat="<?= e($slug) ?>" style="--cc:<?= e($c['accent']) ?>" href="#<?= e($slug) ?>">
                <?= e($c['icon']) ?> <?= e($c['label']) ?>
            </a>
            <?php endforeach ?>
        </div>
        <?php endif ?>
    </div>

    <?php if (!empty($featured)): ?>
    <div class="gt-featured" id="gtFeatured">
        <div style="position:relative">
            <button class="gt-feat-arr gt-feat-arr--prev" id="gtFeatPrev" aria-label="Previous">‹</button>
            <div class="gt-featured-track-wrap">
                <div class="gt-featured-track" id="gtFeatTrack">
                    <?php foreach ($featured as $fev):
                        $fCatSlug = $fev['category'] ?? 'other';
                        $fCat     = $getCat($fCatSlug);
                        $fUrl     = e($base) . '/' . $eventsSlug . '/' . e($fev['slug']);
                        $fDate    = e($tickets->formatDate($fev['event_date']));
                        $fOrgId   = (int)($fev['organizer_id'] ?? 0);
                        $fOrg     = ($fOrgId && isset($orgMap[$fOrgId])) ? $orgMap[$fOrgId] : null;
                    ?>
                    <div class="gt-feat-slide" style="--cat-accent:<?= e($fCat['accent']) ?>">
                        <?php if (!empty($fev['image'])): ?>
                        <img src="<?= e($fev['image']) ?>" alt="<?= e($fev['title']) ?>" loading="lazy">
                        <?php else: ?>
                        <div class="gt-feat-slide-bg" style="background:linear-gradient(135deg,<?= e($fCat['c1']) ?>,<?= e($fCat['c2']) ?>)"><?= e($fCat['icon']) ?></div>
                        <?php endif ?>
                        <div class="gt-feat-overlay"></div>
                        <div class="gt-feat-badge">⭐ Featured</div>
                        <div class="gt-feat-info">
                            <div class="gt-feat-tag"><?= e($fCat['icon']) ?> <?= e($fCat['label']) ?></div>
                            <a href="<?= $fUrl ?>" class="gt-feat-title"><?= e($fev['title']) ?></a>
                            <div class="gt-feat-meta">
                                <span>📅 <?= $fDate ?></span>
                                <?php if ($fOrg): ?>
                                <span>·</span>
                                <span>👤 <?= e($fOrg['name']) ?></span>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach ?>
                </div>
            </div>
            <button class="gt-feat-arr gt-feat-arr--next" id="gtFeatNext" aria-label="Next">›</button>
        </div>
        <div class="gt-feat-btn-wrap" id="gtFeatDots"></div>
    </div>
    <?php endif ?>

    <?php if (empty($events)): ?>
    <div class="gt-empty">
        <div style="font-size:52px;margin-bottom:16px">🎭</div>
        <h3 style="font-size:20px;color:#e2e8f0;font-weight:800;margin-bottom:8px">ახლო მომავალში ღონისძიება არ იგეგმება</h3>
        <p>მოგვიახლოვდით მალე!</p>
    </div>

    <?php else: ?>
    <div class="gt-list">
        <div class="gt-section-label"><span>Upcoming Events</span></div>

        <?php foreach ($events as $i => $ev):
            $minPrice  = $tickets->minPriceForEvent((int)$ev['id']);
            $soldOut   = $tickets->isSoldOut((int)$ev['id']);
            $eventUrl  = e($base) . '/' . $eventsSlug . '/' . e($ev['slug']);
            $catSlug   = $ev['category'] ?? 'other';
            $cat       = $getCat($catSlug);
            $catGlow   = $tickets->hexToRgba($cat['accent'], .07);
            $num       = str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT);
            $dateFmt   = e($tickets->formatDate($ev['event_date']));
            $locParts  = array_filter([(string)($ev['venue']??''), (string)($ev['location']??'')]);
            // organizer from DB
            $orgId     = (int)($ev['organizer_id'] ?? 0);
            $org       = ($orgId && isset($orgMap[$orgId])) ? $orgMap[$orgId] : null;
            // fallback to legacy text field
            $orgLegacy = trim((string)($ev['organizer'] ?? ''));
        ?>
        <div class="gt-row" data-cat="<?= e($catSlug) ?>"
             style="--cat-accent:<?= e($cat['accent']) ?>;--cat-glow:<?= $catGlow ?>">

            <div class="gt-row-img">
                <?php if (!empty($ev['image'])): ?>
                <a href="<?= $eventUrl ?>"><img src="<?= e($ev['image']) ?>" alt="<?= e($ev['title']) ?>" loading="lazy"></a>
                <?php else: ?>
                <a href="<?= $eventUrl ?>" style="background:linear-gradient(135deg,<?= e($cat['c1']) ?>,<?= e($cat['c2']) ?>);display:flex;align-items:center;justify-content:center;font-size:72px;width:100%;height:100%;min-height:360px"><?= e($cat['icon']) ?></a>
                <?php endif ?>
                <div class="gt-row-num"><?= $num ?></div>
                <?php if ($soldOut): ?>
                <div class="gt-row-soldout-banner">Sold Out</div>
                <?php endif ?>
            </div>

            <div class="gt-row-body">
                <div class="gt-row-top">
                    <div class="gt-row-tag"><?= e($cat['icon']) ?> <?= e($cat['label']) ?></div>
                    <?php if ($org): ?>
                    <div class="gt-row-organizer">👤 <a href="<?= e($base) ?>/<?= e($eventsSlug) ?>/organizer/<?= e($org['slug']) ?>"><?= e($org['name']) ?></a></div>
                    <?php elseif ($orgLegacy): ?>
                    <div class="gt-row-organizer">👤 <?= e($orgLegacy) ?></div>
                    <?php endif ?>
                </div>

                <a href="<?= $eventUrl ?>" class="gt-row-title"><?= e($ev['title']) ?></a>

                <?php if (!empty($ev['short_description'])): ?>
                <p style="font-size:13.5px;color:#94a3b8;line-height:1.7;margin-bottom:28px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= e($ev['short_description']) ?></p>
                <?php endif ?>

                <div class="gt-row-cta">
                    <div>
                        <?php if ($soldOut): ?>
                        <div class="gt-row-soldout-lbl">Sold Out</div>
                        <?php elseif ($minPrice !== null && $minPrice > 0): ?>
                        <div class="gt-row-price-from">from</div>
                        <div class="gt-row-price"><?= $tickets->formatPrice($minPrice) ?></div>
                        <?php else: ?>
                        <div class="gt-row-free">უფასო ✓</div>
                        <?php endif ?>
                    </div>
                    <?php if (!$soldOut): ?>
                    <a href="<?= $eventUrl ?>" class="gt-row-btn">ბილეთი &nbsp;→</a>
                    <?php else: ?>
                    <span class="gt-row-btn-dis">Unavailable</span>
                    <?php endif ?>
                </div>

                <div class="gt-row-meta">
                    <span>📅 <?= $dateFmt ?></span>
                    <?php if ($locParts): ?>
                    <span class="gt-row-meta-dot">·</span>
                    <span>📍 <?= e(implode(', ', $locParts)) ?></span>
                    <?php endif ?>
                </div>
            </div>

        </div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

</div>
<script>
/* ── Category filter ────────────────────────────────── */
function applyFilter() {
    var cat = (location.hash || '#all').replace('#','') || 'all';
    document.querySelectorAll('.gt-cat-btn').forEach(function(b){
        b.classList.toggle('active', b.dataset.cat === cat);
    });
    document.querySelectorAll('.gt-row').forEach(function(row){
        row.style.display = (cat === 'all' || row.dataset.cat === cat) ? '' : 'none';
    });
}
window.addEventListener('hashchange', applyFilter);
document.addEventListener('DOMContentLoaded', applyFilter);
applyFilter();

/* ── Featured slider ────────────────────────────────── */
(function(){
    var track  = document.getElementById('gtFeatTrack');
    var dotsWr = document.getElementById('gtFeatDots');
    var prev   = document.getElementById('gtFeatPrev');
    var next   = document.getElementById('gtFeatNext');
    if (!track) return;

    var slides  = track.querySelectorAll('.gt-feat-slide');
    var perPage = window.innerWidth < 640 ? 1 : 2;
    var total   = slides.length;
    var pages   = Math.ceil(total / perPage);
    var current = 0;
    var autoId  = null;

    /* dots */
    var dots = [];
    for (var i = 0; i < pages; i++) {
        var d = document.createElement('button');
        d.className = 'gt-feat-dot' + (i === 0 ? ' active' : '');
        d.setAttribute('aria-label', 'Slide ' + (i + 1));
        (function(idx){ d.addEventListener('click', function(){ go(idx); resetAuto(); }); })(i);
        dotsWr.appendChild(d);
        dots.push(d);
    }

    function go(page) {
        current = (page + pages) % pages;
        var slideW = track.parentElement.offsetWidth;
        var gap    = 16;
        var w      = (slideW - gap) / 2;
        track.style.transform = 'translateX(-' + (current * (w + gap) * perPage) + 'px)';
        dots.forEach(function(d, i){ d.classList.toggle('active', i === current); });
    }

    if (prev) prev.addEventListener('click', function(){ go(current - 1); resetAuto(); });
    if (next) next.addEventListener('click', function(){ go(current + 1); resetAuto(); });

    /* hide arrows when only one page */
    if (pages <= 1) {
        if (prev) prev.style.display = 'none';
        if (next) next.style.display = 'none';
        if (dotsWr) dotsWr.style.display = 'none';
    }

    /* auto-play every 5s */
    function startAuto() { autoId = setInterval(function(){ go(current + 1); }, 5000); }
    function resetAuto()  { clearInterval(autoId); startAuto(); }
    if (pages > 1) startAuto();

    /* touch swipe */
    var startX = 0;
    track.addEventListener('touchstart', function(e){ startX = e.touches[0].clientX; }, {passive:true});
    track.addEventListener('touchend', function(e){
        var dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 50) { go(dx < 0 ? current + 1 : current - 1); resetAuto(); }
    });
})();
</script>
