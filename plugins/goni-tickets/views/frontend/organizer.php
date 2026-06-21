<?php
$eventsSlug = $tickets->setting('events_page_slug', 'events');

$catFallbacks = [
    'concert'   =>['c1'=>'#3b0764','c2'=>'#7c3aed','accent'=>'#a78bfa','icon'=>'🎵','label'=>'კონცერტი'],
    'festival'  =>['c1'=>'#7c2d12','c2'=>'#ea580c','accent'=>'#fb923c','icon'=>'🎪','label'=>'ფესტივალი'],
    'theater'   =>['c1'=>'#1e1b4b','c2'=>'#4338ca','accent'=>'#818cf8','icon'=>'🎭','label'=>'თეატრი'],
    'cinema'    =>['c1'=>'#1c0a0a','c2'=>'#991b1b','accent'=>'#f87171','icon'=>'🎬','label'=>'კინო'],
    'sports'    =>['c1'=>'#052e16','c2'=>'#15803d','accent'=>'#4ade80','icon'=>'⚽','label'=>'სპორტი'],
    'art'       =>['c1'=>'#042f2e','c2'=>'#0f766e','accent'=>'#2dd4bf','icon'=>'🎨','label'=>'ხელოვნება'],
    'food'      =>['c1'=>'#451a03','c2'=>'#b45309','accent'=>'#fbbf24','icon'=>'🍽','label'=>'კულინარია'],
    'conference'=>['c1'=>'#0c1445','c2'=>'#1d4ed8','accent'=>'#60a5fa','icon'=>'💼','label'=>'კონფერენცია'],
    'education' =>['c1'=>'#0c2340','c2'=>'#0369a1','accent'=>'#38bdf8','icon'=>'🎓','label'=>'განათლება'],
    'train'     =>['c1'=>'#0f172a','c2'=>'#334155','accent'=>'#94a3b8','icon'=>'🚂','label'=>'მატარებელი'],
    'bus'       =>['c1'=>'#1c1400','c2'=>'#854d0e','accent'=>'#fde047','icon'=>'🚌','label'=>'ავტობუსი'],
    'nature'    =>['c1'=>'#052e16','c2'=>'#166534','accent'=>'#86efac','icon'=>'🌿','label'=>'ბუნება'],
    'other'     =>['c1'=>'#0a0812','c2'=>'#4c1d95','accent'=>'#a78bfa','icon'=>'🎟','label'=>'სხვა'],
];
$getCat = function(string $slug) use ($catMap, $catFallbacks): array {
    $db = $catMap[$slug] ?? null;
    $fb = $catFallbacks[$slug] ?? $catFallbacks['other'];
    if ($db) return ['icon'=>$db['icon']?:$fb['icon'],'label'=>$db['label']?:$fb['label'],'accent'=>$db['accent']?:$fb['accent'],'c1'=>$db['grad_from']?:$fb['c1'],'c2'=>$db['grad_to']?:$fb['c2']];
    return $fb;
};

$hasCover = !empty($organizer['cover']);
$hasLogo  = !empty($organizer['logo']);

$activeSubnav = 'organizers';
include __DIR__ . '/_subnav.php';
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;700;900&display=swap');

/* ── Page ────────────────────────────────────────────────────────────── */
.gt-org-page{background:var(--gt-page-bg,#0a0812);position:relative;min-height:80vh}
.gt-org-page a{text-decoration:none}

.gt-glow{position:absolute;border-radius:50%;filter:blur(160px);pointer-events:none;z-index:0}
.gt-glow-a{width:800px;height:800px;background:rgba(124,58,237,.28);top:-320px;left:50%;transform:translateX(-60%)}
.gt-glow-b{width:600px;height:600px;background:rgba(236,72,153,.16);top:-240px;left:50%;transform:translateX(-5%)}

/* ── Cover photo ─────────────────────────────────────────────────────── */
.gt-org-cover-wrap{
    position:relative;
    z-index:1;
    /* avatar hangs below cover, so we need bottom margin */
    margin-bottom:64px;
}
.gt-org-cover{
    position:relative;
    width:100%;
    height:320px;
    overflow:hidden;
}
.gt-org-cover img{
    width:100%;height:100%;
    object-fit:cover;
    display:block;
}
/* ── Edge-fade overlay ── */
.gt-org-cover-fade{
    position:absolute;
    inset:0;
    /* left+right fade */
    background:
        linear-gradient(to right,
            var(--gt-page-bg,#0a0812)      0%,
            transparent                    16%,
            transparent                    84%,
            var(--gt-page-bg,#0a0812)      100%
        ),
        /* bottom fade (stronger) */
        linear-gradient(to top,
            var(--gt-page-bg,#0a0812)      0%,
            transparent                    38%
        );
    pointer-events:none;
}
/* no cover: use a gradient placeholder */
.gt-org-cover--empty{
    height:200px;
    background:linear-gradient(135deg,rgba(124,58,237,.18),rgba(236,72,153,.1));
}

/* ── Avatar (profile photo) ──────────────────────────────────────────── */
.gt-org-avatar-wrap{
    position:absolute;
    bottom:-52px;
    left:50%;
    transform:translateX(-50%);
    z-index:2;
}
.gt-org-avatar{
    width:104px;height:104px;
    border-radius:50%;
    object-fit:cover;
    border:4px solid var(--gt-page-bg,#0a0812);
    display:block;
    background:var(--gt-page-bg,#0a0812);
    transition:border-color .25s;
}
.gt-org-avatar-ph{
    width:104px;height:104px;
    border-radius:50%;
    background:rgba(139,92,246,.12);
    border:4px solid var(--gt-page-bg,#0a0812);
    display:flex;align-items:center;justify-content:center;
    font-size:44px;
    transition:border-color .25s;
}

/* ── Header info (below cover+avatar) ───────────────────────────────── */
.gt-org-header{
    position:relative;z-index:1;
    max-width:700px;margin:0 auto;
    padding:0 24px 48px;
    text-align:center;
}
.gt-org-badge{display:inline-flex;align-items:center;gap:5px;background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.25);color:#c4b5fd;border-radius:20px;padding:4px 14px;font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;margin-bottom:14px}
.gt-org-name{font-size:clamp(18px,2.2vw,28px);font-weight:900;color:#f1f5f9;line-height:1.25;letter-spacing:-.3px;margin-bottom:12px;font-family:'Noto Sans Georgian',sans-serif;word-break:break-word;overflow-wrap:break-word}
.gt-org-desc{color:#94a3b8;font-size:14px;line-height:1.75;margin-bottom:16px;max-width:520px;margin-left:auto;margin-right:auto}
.gt-org-website{display:inline-flex;align-items:center;gap:6px;background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.2);color:#a78bfa;border-radius:20px;padding:7px 18px;font-size:13px;font-weight:600}
.gt-org-website:hover{background:rgba(139,92,246,.18)}

/* ── Events list ─────────────────────────────────────────────────────── */
.gt-list{position:relative;z-index:1;max-width:1280px;margin:0 auto;padding:0 24px 80px}
.gt-section-label{display:flex;align-items:center;gap:14px;padding:0 0 20px}
.gt-section-label span{font-size:11px;font-weight:800;color:#a78bfa;text-transform:uppercase;letter-spacing:1.5px;white-space:nowrap}
.gt-section-label::before,.gt-section-label::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.06)}

.gt-row{display:grid;grid-template-columns:1fr 1fr;min-height:320px;border-radius:24px 0 24px 0;overflow:hidden;margin-bottom:12px}
.gt-row:nth-child(even) .gt-row-img{order:2}
.gt-row:nth-child(even) .gt-row-body{order:1}
.gt-row-img{position:relative;overflow:hidden}
.gt-row-img img{width:100%;height:100%;object-fit:cover;display:block}
.gt-row-num{position:absolute;top:20px;left:20px;width:36px;height:36px;background:rgba(10,8,18,.6);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#e2e8f0}
.gt-row-soldout-banner{position:absolute;inset:0;background:rgba(10,8,18,.6);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:800;color:#fca5a5;letter-spacing:2px;text-transform:uppercase}
.gt-row-body{background:var(--cat-glow,rgba(255,255,255,.03));border:1px solid rgba(255,255,255,.06);padding:40px 44px;display:flex;flex-direction:column;justify-content:center}
.gt-row-tag{font-size:10.5px;font-weight:800;color:var(--cat-accent,#a78bfa);text-transform:uppercase;letter-spacing:1.2px;margin-bottom:16px}
.gt-row-title{font-size:clamp(20px,2vw,30px);font-weight:900;color:#f1f5f9;line-height:1.2;letter-spacing:-.4px;margin-bottom:16px;display:block}
.gt-row-title:hover{color:#c4b5fd}
.gt-row-cta{display:flex;align-items:flex-end;gap:20px;margin-top:auto}
.gt-row-price-from{font-size:10px;text-transform:uppercase;letter-spacing:.6px;color:#a78bfa;font-weight:700;margin-bottom:2px}
.gt-row-price{font-size:28px;font-weight:900;color:var(--cat-accent,#a78bfa);line-height:1;letter-spacing:-1px}
.gt-row-free{font-size:16px;font-weight:800;color:#34d399;line-height:1}
.gt-row-soldout-lbl{font-size:14px;font-weight:700;color:#fca5a5}
.gt-row-btn{display:inline-flex;align-items:center;background:var(--cat-accent,#7c3aed);color:#0a0812 !important;padding:12px 24px;border-radius:30px;font-size:13px;font-weight:700;white-space:nowrap;margin-left:auto}
.gt-row-btn:hover{opacity:.88}
.gt-row-btn-dis{display:inline-flex;background:rgba(255,255,255,.06);color:#94a3b8;padding:12px 24px;border-radius:30px;font-size:13px;font-weight:700;white-space:nowrap;margin-left:auto;cursor:not-allowed}
.gt-row-meta{margin-top:10px;font-size:12px;color:#3f3f5a;display:flex;align-items:center;gap:6px}

.gt-empty{text-align:center;padding:60px 24px;color:#94a3b8;position:relative;z-index:1}

/* ── Light-mode overrides ────────────────────────────────────────────── */
html.gt-light .gt-org-name{color:#1e1b4b}
html.gt-light .gt-org-badge{background:rgba(124,58,237,.07);border-color:rgba(124,58,237,.18);color:#7c3aed}
html.gt-light .gt-section-label span{color:#7c3aed}
html.gt-light .gt-section-label::before,
html.gt-light .gt-section-label::after{background:rgba(0,0,0,.08)}
html.gt-light .gt-row-body{background:#fff;border-color:rgba(0,0,0,.07)}
html.gt-light .gt-row-title{color:#1e1b4b}
html.gt-light .gt-row-title:hover{color:#7c3aed}
html.gt-light .gt-row-meta{color:#94a3b8}
html.gt-light .gt-row-price-from{color:#7c3aed}
html.gt-light .gt-row-btn{color:#fff !important}
html.gt-light .gt-row-btn-dis{background:rgba(0,0,0,.06);color:#94a3b8}
html.gt-light .gt-row-num{background:rgba(255,255,255,.9);border-color:rgba(0,0,0,.1);color:#1e293b}
html.gt-light .gt-org-website{background:rgba(124,58,237,.07);border-color:rgba(124,58,237,.18);color:#7c3aed}
@media(max-width:720px){
    .gt-org-cover{height:220px}
    .gt-org-cover--empty{height:140px}
    .gt-org-avatar-wrap{bottom:-44px}
    .gt-org-avatar,.gt-org-avatar-ph{width:88px;height:88px;font-size:36px}
    .gt-org-cover-wrap{margin-bottom:56px}
    .gt-org-header{padding:0 16px 36px}
    .gt-row{grid-template-columns:1fr;min-height:unset}
    .gt-row:nth-child(even) .gt-row-img,.gt-row:nth-child(even) .gt-row-body{order:unset}
    .gt-row-img img,.gt-row-img a{min-height:200px}
    .gt-row-body{padding:24px 20px 28px}
}
</style>

<div class="gt-org-page">

    <?php if (!$hasCover): ?>
    <!-- subtle glows only when no cover -->
    <div class="gt-glow gt-glow-a"></div>
    <div class="gt-glow gt-glow-b"></div>
    <?php endif ?>

    <!-- ── Cover + Avatar ─────────────────────────────────────────────── -->
    <div class="gt-org-cover-wrap">
        <div class="gt-org-cover<?= $hasCover ? '' : ' gt-org-cover--empty' ?>">
            <?php if ($hasCover): ?>
            <img src="<?= e($organizer['cover']) ?>" alt="<?= e($organizer['name']) ?> cover">
            <div class="gt-org-cover-fade"></div>
            <?php endif ?>
        </div>

        <!-- profile photo sits on the bottom edge of the cover -->
        <div class="gt-org-avatar-wrap">
            <?php if ($hasLogo): ?>
            <img class="gt-org-avatar" src="<?= e($organizer['logo']) ?>" alt="<?= e($organizer['name']) ?>">
            <?php else: ?>
            <div class="gt-org-avatar-ph">👤</div>
            <?php endif ?>
        </div>
    </div>

    <!-- ── Info ───────────────────────────────────────────────────────── -->
    <div class="gt-org-header">
        <div class="gt-org-badge">👤 Organizer</div>
        <div class="gt-org-name"><?= e($organizer['name']) ?></div>
        <?php if (!empty($organizer['description'])): ?>
        <div class="gt-org-desc"><?= nl2br(e($organizer['description'])) ?></div>
        <?php endif ?>
        <?php if (!empty($organizer['website'])): ?>
        <a href="<?= e($organizer['website']) ?>" class="gt-org-website" target="_blank" rel="noopener">
            🌐 <?= e(parse_url($organizer['website'], PHP_URL_HOST) ?: $organizer['website']) ?>
        </a>
        <?php endif ?>
    </div>

    <!-- ── Events ─────────────────────────────────────────────────────── -->
    <?php if (empty($orgEvents)): ?>
    <div class="gt-empty">
        <div style="font-size:40px;margin-bottom:12px">🎭</div>
        <p style="font-size:15px;color:#e2e8f0">ამ ორგანიზატორს ჯერ ღონისძიება არ აქვს.</p>
    </div>

    <?php else: ?>
    <div class="gt-list">
        <div class="gt-section-label">
            <span><?= e($organizer['name']) ?> — ღონისძიებები (<?= count($orgEvents) ?>)</span>
        </div>

        <?php foreach ($orgEvents as $i => $ev):
            $minPrice = $tickets->minPriceForEvent((int)$ev['id']);
            $soldOut  = $tickets->isSoldOut((int)$ev['id']);
            $eventUrl = e($base) . '/' . $eventsSlug . '/' . e($ev['slug']);
            $catSlug  = $ev['category'] ?? 'other';
            $cat      = $getCat($catSlug);
            $catGlow  = $tickets->hexToRgba($cat['accent'], .07);
            $num      = str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT);
            $dateFmt  = e($tickets->formatDate($ev['event_date']));
            $locParts = array_filter([(string)($ev['venue']??''), (string)($ev['location']??'')]);
        ?>
        <div class="gt-row" data-cat="<?= e($catSlug) ?>"
             style="--cat-accent:<?= e($cat['accent']) ?>;--cat-glow:<?= $catGlow ?>">

            <div class="gt-row-img">
                <?php if (!empty($ev['image'])): ?>
                <a href="<?= $eventUrl ?>"><img src="<?= e($ev['image']) ?>" alt="<?= e($ev['title']) ?>" loading="lazy"></a>
                <?php else: ?>
                <a href="<?= $eventUrl ?>" style="background:linear-gradient(135deg,<?= e($cat['c1']) ?>,<?= e($cat['c2']) ?>);display:flex;align-items:center;justify-content:center;font-size:64px;width:100%;height:100%;min-height:320px"><?= e($cat['icon']) ?></a>
                <?php endif ?>
                <div class="gt-row-num"><?= $num ?></div>
                <?php if ($soldOut): ?>
                <div class="gt-row-soldout-banner">Sold Out</div>
                <?php endif ?>
            </div>

            <div class="gt-row-body">
                <div class="gt-row-tag"><?= e($cat['icon']) ?> <?= e($cat['label']) ?></div>
                <a href="<?= $eventUrl ?>" class="gt-row-title"><?= e($ev['title']) ?></a>

                <?php if (!empty($ev['short_description'])): ?>
                <p style="font-size:13px;color:#94a3b8;line-height:1.7;margin-bottom:24px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= e($ev['short_description']) ?></p>
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
                    <span>·</span>
                    <span>📍 <?= e(implode(', ', $locParts)) ?></span>
                    <?php endif ?>
                </div>
            </div>

        </div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

</div>
