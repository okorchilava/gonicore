<?php
$eventsSlug  = $tickets->setting('events_page_slug', 'events');
$activeSubnav = 'organizers';
include __DIR__ . '/_subnav.php';
?>
<style>
.gt-orgs-page{background:#0a0812;position:relative;min-height:80vh}
.gt-orgs-page a{text-decoration:none}

.gt-glow{position:absolute;border-radius:50%;filter:blur(160px);pointer-events:none;z-index:0}
.gt-glow-a{width:800px;height:800px;background:rgba(124,58,237,.28);top:-320px;left:50%;transform:translateX(-60%)}
.gt-glow-b{width:600px;height:600px;background:rgba(236,72,153,.16);top:-240px;left:50%;transform:translateX(-5%)}

/* heading */
.gt-orgs-heading{position:relative;z-index:1;text-align:center;padding:72px 24px 56px}
.gt-orgs-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.25);color:#c4b5fd;border-radius:20px;padding:5px 16px;font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;margin-bottom:20px}
.gt-orgs-heading h1{font-size:clamp(32px,4.5vw,60px);font-weight:900;color:#fff;line-height:1.35;letter-spacing:4px;margin-bottom:14px}
.gt-orgs-heading h1 em{font-style:normal;background:linear-gradient(135deg,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.gt-orgs-sub{color:#94a3b8;font-size:14px}
.gt-hero-count{display:inline-block;background:rgba(99,102,241,.12);color:#818cf8;border-radius:10px;padding:2px 10px;font-size:12px;font-weight:700;margin-left:6px}

/* grid */
.gt-orgs-grid{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:0 24px 80px;display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}

/* card */
.gt-org-card{
    background:rgba(255,255,255,.03);
    border:1px solid rgba(255,255,255,.07);
    border-radius:20px 0 20px 0;
    padding:28px;
    display:flex;flex-direction:column;gap:16px;
    transition:border-color .2s,background .2s;
}
.gt-org-card:hover{
    background:rgba(124,58,237,.06);
    border-color:rgba(124,58,237,.3);
    text-decoration:none;
}
.gt-org-card-top{display:flex;align-items:center;gap:16px}
.gt-org-card-logo{width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid rgba(139,92,246,.25);flex-shrink:0}
.gt-org-card-logo-ph{width:56px;height:56px;border-radius:50%;background:rgba(139,92,246,.1);border:2px solid rgba(139,92,246,.2);display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0}
.gt-org-card-name{font-size:16px;font-weight:800;color:#f1f5f9;line-height:1.25;letter-spacing:-.3px}
.gt-org-card-meta{font-size:11.5px;color:#94a3b8;margin-top:3px}
.gt-org-card-desc{font-size:13px;color:#3f3f5a;line-height:1.65;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.gt-org-card-footer{display:flex;align-items:center;justify-content:space-between;margin-top:auto;padding-top:12px;border-top:1px solid rgba(255,255,255,.05)}
.gt-org-card-count{font-size:12px;color:#3f3f5a;font-weight:600}
.gt-org-card-link{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;color:#a78bfa}
.gt-org-card:hover .gt-org-card-link{color:#c4b5fd}

/* empty */
.gt-orgs-empty{position:relative;z-index:1;text-align:center;padding:80px 24px;color:#94a3b8;max-width:1100px;margin:0 auto}

@media(max-width:600px){
    .gt-orgs-grid{grid-template-columns:1fr;padding:0 16px 60px}
    .gt-orgs-heading{padding:56px 16px 44px}
}
</style>

<div class="gt-orgs-page">

    <div class="gt-glow gt-glow-a"></div>
    <div class="gt-glow gt-glow-b"></div>

    <div class="gt-orgs-heading">
        <h1><em>ორგანიზატორები</em></h1>
    </div>

    <?php if (empty($organizers)): ?>
    <div class="gt-orgs-empty">
        <div style="font-size:48px;margin-bottom:16px">👤</div>
        <p style="font-size:16px;color:#e2e8f0;font-weight:700;margin-bottom:8px">ორგანიზატორი ჯერ არ არის</p>
        <p>მოგვიახლოვდით მალე!</p>
    </div>

    <?php else: ?>
    <div class="gt-orgs-grid">
        <?php foreach ($organizers as $org): ?>
        <a class="gt-org-card" href="<?= e($base) ?>/<?= e($eventsSlug) ?>/organizer/<?= e($org['slug']) ?>">
            <div class="gt-org-card-top">
                <?php if (!empty($org['logo'])): ?>
                <img class="gt-org-card-logo" src="<?= e($org['logo']) ?>" alt="<?= e($org['name']) ?>">
                <?php else: ?>
                <div class="gt-org-card-logo-ph">👤</div>
                <?php endif ?>
                <div>
                    <div class="gt-org-card-name"><?= e($org['name']) ?></div>
                    <div class="gt-org-card-meta">
                        <?= (int)$org['event_count'] ?> ღონისძიება
                        <?php if (!empty($org['website'])): ?>
                        · <?= e(parse_url($org['website'], PHP_URL_HOST) ?: '') ?>
                        <?php endif ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($org['description'])): ?>
            <div class="gt-org-card-desc"><?= e($org['description']) ?></div>
            <?php endif ?>

            <div class="gt-org-card-footer">
                <span class="gt-org-card-count">🎭 <?= (int)$org['event_count'] ?> event<?= $org['event_count'] !== 1 ? 's' : '' ?></span>
                <span class="gt-org-card-link">ნახვა →</span>
            </div>
        </a>
        <?php endforeach ?>
    </div>
    <?php endif ?>

</div>
